<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listar_entregas($conexion);
        break;
    case 'procesar_entrega':
        procesar_entrega($conexion);
        break;
    case 'procesar_calidad':
        procesar_calidad($conexion);
        break;
    case 'obtener_acta':
        obtener_acta($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

// =========================================================================
// FUNCIÓN DE SEGURIDAD: VERIFICA CLAVES DEL GRUPO ADMINISTRADOR / SUPERVISOR
// =========================================================================
function verificar_clave_admin($conexion, $username, $password_ingresada) {
    if (empty($username) || empty($password_ingresada)) return false;

    // Busca al usuario asumiendo que los que pueden hacer QC son de nivel Administrador
    $sql = "SELECT u.password_hash 
            FROM Usuario u 
            JOIN Nivel n ON u.id_nivel = n.id_nivel 
            WHERE u.username = ? AND n.nombre = 'Administrador' AND u.estado = 'activo' LIMIT 1";
            
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $hash = $row['password_hash'];
        if (password_verify($password_ingresada, $hash) || $password_ingresada === $hash) {
            return true; 
        }
    }
    return false; 
}
// =========================================================================

function listar_entregas($conexion) {
    $sql = "SELECT 
                o.id_orden, 
                o.descripcion, 
                IFNULL(o.monto_total, 0) AS monto_total,
                CONCAT('RD$ ', FORMAT(IFNULL(o.monto_total, 0), 2)) AS monto_total_fmt,
                
                (SELECT e.nombre FROM Orden_Estado oe JOIN Estado e ON oe.id_estado = e.id_estado 
                 WHERE oe.id_orden = o.id_orden ORDER BY oe.fecha_creacion DESC LIMIT 1) AS estado_orden,
                
                CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS cliente,
                CONCAT(mar.nombre, ' ', IFNULL(v.modelo, ''), ' [', v.placa, ']') AS vehiculo,
                IFNULL(fc.estado_pago, 'Sin Facturar') AS estado_pago,
                DATE(o.fecha_creacion) as fecha_orden
            FROM Orden o
            JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            JOIN Vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN Marca mar ON v.id_marca = mar.id_marca
            JOIN Cliente c ON v.id_cliente = c.id_cliente
            JOIN Persona per ON c.id_persona = per.id_persona
            LEFT JOIN Factura_Central fc ON o.id_orden = fc.id_orden
            WHERE o.estado != 'eliminado' 
            HAVING (estado_orden IN ('Control Calidad', 'Listo') OR (estado_orden = 'Entregado' AND fecha_orden = CURDATE()))
            ORDER BY 
                CASE estado_orden 
                    WHEN 'Listo' THEN 1 
                    WHEN 'Control Calidad' THEN 2 
                    WHEN 'Entregado' THEN 3 
                    ELSE 4 
                END, 
                o.id_orden ASC";
                
    $res = $conexion->query($sql);
    
    if ($res) {
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error SQL: ' . $conexion->error]);
    }
}

function procesar_calidad($conexion) {
    $id_orden = $_POST['id_orden_calidad'] ?? '';
    $decision = $_POST['decision_calidad'] ?? '';
    $admin_user = $_POST['admin_username'] ?? '';
    $admin_pass = $_POST['admin_password'] ?? '';
    $usuario_sesion = $_SESSION['id_usuario'] ?? 1;

    if (empty($id_orden) || empty($decision)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos para evaluar la orden.']); return;
    }

    if (!verificar_clave_admin($conexion, $admin_user, $admin_pass)) {
        echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas o el usuario no tiene permisos de Administrador/Supervisor para realizar Control de Calidad.']);
        return;
    }

    $conexion->begin_transaction();

    try {
        // Determinar cuál es el nuevo estado a insertar
        $nombre_estado_nuevo = ($decision === 'Aprobado') ? 'Listo' : 'Reparación';
        
        $resEst = $conexion->query("SELECT id_estado FROM Estado WHERE nombre = '$nombre_estado_nuevo' LIMIT 1");
        if($resEst->num_rows == 0) throw new Exception("No existe el estado '$nombre_estado_nuevo' en la BD.");
        
        $id_estado_nuevo = $resEst->fetch_assoc()['id_estado'];

        // Guardamos el nuevo estado usando el ID del usuario de la sesión (o se puede buscar el ID del admin, usamos el de sesión por auditoría)
        $stmtInsert = $conexion->prepare("INSERT INTO Orden_Estado (id_orden, id_estado, usuario_creacion) VALUES (?, ?, ?)");
        $stmtInsert->bind_param("iii", $id_orden, $id_estado_nuevo, $usuario_sesion);
        $stmtInsert->execute();

        $conexion->commit();
        
        if ($decision === 'Aprobado') {
            echo json_encode(['success' => true, 'message' => 'Control de Calidad Aprobado. El vehículo está ahora Listo para entregar.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Control de Calidad Rechazado. El vehículo ha regresado a la cola de Reparación de los mecánicos.']);
        }

    } catch (Exception $e) {
        $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function procesar_entrega($conexion) {
    $id_orden = $_POST['id_orden_entrega'] ?? '';
    $usuario = $_SESSION['id_usuario'] ?? 1;

    if (empty($id_orden)) {
        echo json_encode(['success' => false, 'message' => 'Falta el ID de la orden.']); return;
    }

    $conexion->begin_transaction();

    try {
        $sqlCheck = "SELECT e.nombre FROM Orden_Estado oe JOIN Estado e ON oe.id_estado = e.id_estado WHERE oe.id_orden = $id_orden ORDER BY oe.fecha_creacion DESC LIMIT 1";
        $resCheck = $conexion->query($sqlCheck);
        if($resCheck && $resCheck->num_rows > 0) {
            $estadoActual = $resCheck->fetch_assoc()['nombre'];
            if($estadoActual === 'Entregado') {
                throw new Exception("Esta orden ya había sido marcada como Entregada.");
            }
            if($estadoActual !== 'Listo') {
                throw new Exception("El vehículo debe estar en estado 'Listo' para poder entregarse.");
            }
        }

        $resEst = $conexion->query("SELECT id_estado FROM Estado WHERE nombre = 'Entregado' LIMIT 1");
        if($resEst->num_rows == 0) throw new Exception("No existe el estado 'Entregado' en la configuración de la BD.");
        
        $id_estado_entregado = $resEst->fetch_assoc()['id_estado'];

        $stmtInsert = $conexion->prepare("INSERT INTO Orden_Estado (id_orden, id_estado, usuario_creacion) VALUES (?, ?, ?)");
        $stmtInsert->bind_param("iii", $id_orden, $id_estado_entregado, $usuario);
        $stmtInsert->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Vehículo marcado como Entregado en el historial de la orden.']);

    } catch (Exception $e) {
        $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtener_acta($conexion) {
    $id_orden = (int)($_GET['id_orden'] ?? 0);
    
    if($id_orden === 0) {
        echo json_encode(['success' => false, 'message' => 'ID de orden no válido.']); return;
    }

    $sql = "SELECT 
                o.id_orden, 
                DATE_FORMAT(o.fecha_creacion, '%d/%m/%Y %h:%i %p') AS fecha_ingreso,
                CONCAT('RD$ ', FORMAT(IFNULL(o.monto_total, 0), 2)) AS monto_total_fmt,
                CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, ''), ' ', IFNULL(per.apellido_m, '')) AS cliente,
                v.placa, 
                v.vin_chasis,
                CONCAT(mar.nombre, ' ', IFNULL(v.modelo, ''), ' (', IFNULL(v.anio, 'N/A'), ')') AS vehiculo,
                
                DATE_FORMAT(oe_entrega.fecha_creacion, '%d/%m/%Y %h:%i %p') AS fecha_entrega,
                IFNULL(u.username, 'Administrador (Sistema)') AS entregado_por
                
            FROM Orden o
            JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            JOIN Vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN Marca mar ON v.id_marca = mar.id_marca
            JOIN Cliente c ON v.id_cliente = c.id_cliente
            JOIN Persona per ON c.id_persona = per.id_persona
            
            LEFT JOIN Orden_Estado oe_entrega ON o.id_orden = oe_entrega.id_orden 
            LEFT JOIN Estado e_entrega ON oe_entrega.id_estado = e_entrega.id_estado AND e_entrega.nombre = 'Entregado'
            LEFT JOIN Usuario u ON oe_entrega.usuario_creacion = u.id_usuario
            
            WHERE o.id_orden = $id_orden 
            ORDER BY oe_entrega.fecha_creacion DESC LIMIT 1";
            
    $res = $conexion->query($sql);
    
    if($res && $res->num_rows > 0) {
        echo json_encode(['success' => true, 'data' => $res->fetch_assoc()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo generar el acta de entrega.']);
    }
}
?>