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
    case 'obtener_acta':
        obtener_acta($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida en módulo de entregas']);
        break;
}

function listar_entregas($conexion) {
    $sql = "SELECT 
                o.id_orden, 
                o.descripcion, 
                IFNULL(o.monto_total, 0) AS monto_total,
                CONCAT('RD$ ', FORMAT(IFNULL(o.monto_total, 0), 2)) AS monto_total_fmt,
                o.estado_orden,
                CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS cliente,
                CONCAT(mar.nombre, ' ', IFNULL(v.modelo, ''), ' [', v.placa, ']') AS vehiculo,
                IFNULL(fc.estado_pago, 'Sin Facturar') AS estado_pago,
                DATE(o.fecha_creacion) as fecha_orden
            FROM orden o
            JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN marca mar ON v.id_marca = mar.id_marca
            JOIN cliente c ON v.id_cliente = c.id_cliente
            JOIN persona per ON c.id_persona = per.id_persona
            LEFT JOIN factura_central fc ON o.id_orden = fc.id_orden
            WHERE o.estado != 'eliminado' 
              AND (
                  o.estado_orden IN ('Control Calidad', 'Listo') 
                  OR (o.estado_orden = 'Entregado' AND DATE(o.fecha_creacion) = CURDATE())
              )
            GROUP BY o.id_orden
            ORDER BY 
                CASE o.estado_orden 
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

function procesar_entrega($conexion) {
    $id_orden = $_POST['id_orden_entrega'] ?? '';
    $estado_anterior = $_POST['estado_anterior'] ?? 'Listo'; 
    $usuario = $_SESSION['id_usuario'] ?? 1;

    if (empty($id_orden)) {
        echo json_encode(['success' => false, 'message' => 'Falta el ID de la orden.']);
        return;
    }

    $conexion->begin_transaction();

    try {
        $resCheck = $conexion->query("SELECT estado_orden FROM orden WHERE id_orden = $id_orden");
        if($resCheck && $resCheck->num_rows > 0) {
            $rowCheck = $resCheck->fetch_assoc();
            if($rowCheck['estado_orden'] === 'Entregado') {
                throw new Exception("Esta orden ya había sido marcada como Entregada anteriormente.");
            }
            if($rowCheck['estado_orden'] != '') {
                $estado_anterior = $rowCheck['estado_orden'];
            }
        } else {
            throw new Exception("La orden no existe o fue eliminada.");
        }

        $sqlUpdate = "UPDATE orden SET estado_orden = 'Entregado' WHERE id_orden = ?";
        $stmtUpdate = $conexion->prepare($sqlUpdate);
        $stmtUpdate->bind_param("i", $id_orden);
        $stmtUpdate->execute();

        $sqlHistorial = "INSERT INTO historial_estado_orden (id_orden, estado_anterior, estado_nuevo, estado, usuario_creacion) 
                         VALUES (?, ?, 'Entregado', 'activo', ?)";
        $stmtHistorial = $conexion->prepare($sqlHistorial);
        $stmtHistorial->bind_param("isi", $id_orden, $estado_anterior, $usuario);
        $stmtHistorial->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'El rastro de auditoría ha sido guardado.']);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtener_acta($conexion) {
    $id_orden = (int)($_GET['id_orden'] ?? 0);
    
    if($id_orden === 0) {
        echo json_encode(['success' => false, 'message' => 'ID de orden no válido.']);
        return;
    }

    $sql = "SELECT 
                o.id_orden, 
                DATE_FORMAT(o.fecha_creacion, '%d/%m/%Y %h:%i %p') AS fecha_ingreso,
                CONCAT('RD$ ', FORMAT(IFNULL(o.monto_total, 0), 2)) AS monto_total_fmt,
                CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, ''), ' ', IFNULL(per.apellido_m, '')) AS cliente,
                v.placa, 
                v.vin_chasis,
                CONCAT(mar.nombre, ' ', IFNULL(v.modelo, ''), ' (', IFNULL(v.anio, 'N/A'), ')') AS vehiculo,
                DATE_FORMAT(heo.fecha_cambio, '%d/%m/%Y %h:%i %p') AS fecha_entrega,
                IFNULL(u.username, 'Administrador (Sistema)') AS entregado_por
            FROM orden o
            JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN marca mar ON v.id_marca = mar.id_marca
            JOIN cliente c ON v.id_cliente = c.id_cliente
            JOIN persona per ON c.id_persona = per.id_persona
            LEFT JOIN historial_estado_orden heo ON o.id_orden = heo.id_orden AND heo.estado_nuevo = 'Entregado'
            LEFT JOIN usuario u ON heo.usuario_creacion = u.id_usuario
            WHERE o.id_orden = $id_orden LIMIT 1";
            
    $res = $conexion->query($sql);
    
    if($res && $res->num_rows > 0) {
        echo json_encode(['success' => true, 'data' => $res->fetch_assoc()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo generar el acta de entrega de esta orden.']);
    }
}
?>