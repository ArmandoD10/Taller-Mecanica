<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
session_start();

switch ($action) {
    case 'cargar_datos_iniciales':
        cargar_datos_iniciales($conexion);
        break;
    case 'cargar_vehiculos_cliente':
        cargar_vehiculos_cliente($conexion);
        break;
    case 'cargar_datos_orden':
        cargar_datos_orden($conexion);
        break;
    case 'guardar':
        guardar($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function cargar_datos_iniciales($conexion) {
    $data = [];
    $resClientes = $conexion->query("SELECT c.id_cliente, IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, ''))) AS nombre_cliente, p.cedula FROM persona p JOIN cliente c ON p.id_persona = c.id_persona WHERE c.estado = 'activo'");
    $data['clientes'] = $resClientes ? $resClientes->fetch_all(MYSQLI_ASSOC) : [];

    $resEmpleados = $conexion->query("SELECT e.id_empleado, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) AS nombre_empleado FROM empleado e JOIN persona p ON e.id_persona = p.id_persona WHERE e.estado = 'activo'");
    $data['empleados'] = $resEmpleados ? $resEmpleados->fetch_all(MYSQLI_ASSOC) : [];

    echo json_encode(['success' => true, 'data' => $data]);
}

function cargar_datos_orden($conexion) {
    $id_orden = (int)($_GET['id_orden'] ?? 0);
    // CORRECCIÓN APLICADA: Vehículo -> Cliente -> Persona
    $sql = "SELECT o.id_orden, o.descripcion, v.id_cliente, v.sec_vehiculo as id_vehiculo, 
                   IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, ''))) AS nombre_cliente,
                   CONCAT(m.nombre, ' ', v.modelo, ' [', v.placa, ']') as vehiculo_desc
            FROM orden o
            JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN cliente c ON v.id_cliente = c.id_cliente
            JOIN persona p ON c.id_persona = p.id_persona
            JOIN marca m ON v.id_marca = m.id_marca
            WHERE o.id_orden = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_orden);
    $stmt->execute();
    $res = $stmt->get_result();
    echo json_encode(['success' => true, 'data' => $res->fetch_assoc()]);
}

function cargar_vehiculos_cliente($conexion) {
    $id_cliente = (int)($_GET['id_cliente'] ?? 0);
    $sql = "SELECT v.sec_vehiculo AS id_vehiculo, v.placa, v.vin_chasis, m.nombre AS marca, v.modelo AS modelo 
            FROM vehiculo v 
            LEFT JOIN marca m ON v.id_marca = m.id_marca 
            WHERE v.id_cliente = ? AND v.estado = 'activo'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $res = $stmt->get_result();
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function guardar($conexion) {
    $usuario_creacion = $_SESSION['id_usuario'] ?? 0;
    $id_vehiculo = $_POST['id_vehiculo'] ?? '';
    $id_empleado = $_POST['id_empleado'] ?? '';
    $id_orden_previa = (int)($_POST['id_orden'] ?? 0);
    $kilometraje = $_POST['kilometraje_recepcion'] ?? 0;
    $combustible = $_POST['nivel_combustible'] ?? '';
    $motivo_visita = $_POST['motivo_visita'] ?? '';

    if ($usuario_creacion == 0) {
        echo json_encode(['success' => false, 'message' => 'Sesión expirada.']);
        return;
    }

    try {
        $conexion->begin_transaction();

        $sql_s = "SELECT es.id_sucursal FROM empleado_usuario eu
                  INNER JOIN empleado_sucursal es ON eu.id_empleado = es.id_empleado
                  WHERE eu.id_usuario = ? AND es.estado = 'activo' LIMIT 1";
        $stmt_s = $conexion->prepare($sql_s);
        $stmt_s->bind_param("i", $usuario_creacion);
        $stmt_s->execute();
        $res_s = $stmt_s->get_result()->fetch_assoc();
        $id_sucursal_actual = $res_s['id_sucursal'] ?? 0;

        if ($id_sucursal_actual == 0) {
            throw new Exception("El usuario no tiene una sucursal activa asignada.");
        }

        if ($id_orden_previa > 0) {
            // LÓGICA DE TRANSICIÓN: Actualizar inspección fantasma de la cotización
            $sqlGetIns = "SELECT id_inspeccion FROM orden WHERE id_orden = ?";
            $stmtGet = $conexion->prepare($sqlGetIns);
            $stmtGet->bind_param("i", $id_orden_previa);
            $stmtGet->execute();
            $id_inspeccion = $stmtGet->get_result()->fetch_assoc()['id_inspeccion'];

            $updIns = "UPDATE inspeccion SET id_empleado = ?, kilometraje_recepcion = ?, nivel_combustible = ?, observacion = ?, estado = 'activo' WHERE id_inspeccion = ?";
            $stmtUpd = $conexion->prepare($updIns);
            $stmtUpd->bind_param("iissi", $id_empleado, $kilometraje, $combustible, $motivo_visita, $id_inspeccion);
            $stmtUpd->execute();
            
            // Avanzar el estado de la orden a "Reparación"
            $resEst = $conexion->query("SELECT id_estado FROM estado WHERE nombre = 'Reparación' LIMIT 1");
            if($resEst->num_rows > 0) {
                $id_est_rep = $resEst->fetch_assoc()['id_estado'];
                $conexion->query("INSERT INTO orden_estado (id_orden, id_estado, usuario_creacion) VALUES ($id_orden_previa, $id_est_rep, $usuario_creacion)");
            }
            $msg = "Inspección completada. La Orden ORD-$id_orden_previa ha pasado a Reparación.";
        } else {
            // LÓGICA DIRECTA: Crear desde cero
            $sql = "INSERT INTO inspeccion (id_vehiculo, id_empleado, id_sucursal, kilometraje_recepcion, nivel_combustible, observacion, estado, usuario_creacion) 
                    VALUES (?, ?, ?, ?, ?, ?, 'activo', ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("iiiissi", $id_vehiculo, $id_empleado, $id_sucursal_actual, $kilometraje, $combustible, $motivo_visita, $usuario_creacion);
            $stmt->execute();
            
            $id_inspeccion = $conexion->insert_id;

            $sqlOrd = "INSERT INTO orden (id_inspeccion, id_sucursal, descripcion, estado, usuario_creacion) VALUES (?, ?, ?, 'activo', ?)";
            $stmtOrd = $conexion->prepare($sqlOrd);
            $stmtOrd->bind_param("iisi", $id_inspeccion, $id_sucursal_actual, $motivo_visita, $usuario_creacion);
            $stmtOrd->execute();
            
            $msg = "Inspección guardada exitosamente en sucursal ID: " . $id_sucursal_actual;
        }

        // Guardar el Checklist Dinámico
        $items_interior = ['Beeper', 'Pito/Bocina', 'Luces int.', 'Aire Cond.', 'Radio', 'Cristales', 'Seguros', 'Retrovisor'];
        $items_ext = ['Goma Rep.', 'Gato', 'Herram.', 'Llave Rueda', 'Luces Tras.', 'Tapa Comb.', 'Botiquín', 'Triángulo'];
        $items_mot = ['Varilla Aceite', 'Tapón Aceite', 'Radiador', 'Batería', 'Agua L/V', 'Filtro Aire', 'Correas', 'Tapas'];

        $sqlDet = "INSERT INTO inspeccion_detalle (id_inspeccion, categoria, elemento, estado) VALUES (?, ?, ?, ?)";
        $stmtDet = $conexion->prepare($sqlDet);

        $insertarCategoria = function($prefijo, $items, $categoria) use ($stmtDet, $id_inspeccion) {
            foreach ($items as $i => $item) {
                if (isset($_POST[$prefijo . '_' . $i])) {
                    $estado = $_POST[$prefijo . '_' . $i];
                    $stmtDet->bind_param("isss", $id_inspeccion, $categoria, $item, $estado);
                    $stmtDet->execute();
                }
            }
        };

        $insertarCategoria('int', $items_interior, 'Interior');
        $insertarCategoria('ext', $items_ext, 'Exterior');
        $insertarCategoria('mot', $items_mot, 'Motor');

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => $msg]);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>