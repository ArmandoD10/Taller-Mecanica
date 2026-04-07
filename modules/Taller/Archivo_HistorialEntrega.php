<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listar_entregas($conexion);
        break;
    case 'obtener_acta':
        obtener_acta($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida en módulo de historial de entregas']);
        break;
}

function listar_entregas($conexion) {
    // Consulta especializada: Une la orden con su historial para buscar exactamente quién y cuándo la entregó.
    $sql = "SELECT 
                o.id_orden,
                CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS cliente,
                CONCAT(mar.nombre, ' ', IFNULL(v.modelo, '')) AS vehiculo,
                v.placa,
                IFNULL(o.monto_total, 0) AS monto_total,
                CONCAT('RD$ ', FORMAT(IFNULL(o.monto_total, 0), 2)) AS monto_total_fmt,
                -- Datos de la auditoría de entrega
                DATE_FORMAT(heo.fecha_cambio, '%d/%m/%Y %h:%i %p') AS fecha_entrega,
                IFNULL(u.username, 'Administrador') AS entregado_por
            FROM orden o
            JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN marca mar ON v.id_marca = mar.id_marca
            JOIN cliente c ON v.id_cliente = c.id_cliente
            JOIN persona per ON c.id_persona = per.id_persona
            -- Cruce crucial: Buscamos el registro en el historial donde el estado pasó a Entregado
            LEFT JOIN historial_estado_orden heo ON o.id_orden = heo.id_orden AND heo.estado_nuevo = 'Entregado'
            LEFT JOIN usuario u ON heo.usuario_creacion = u.id_usuario
            WHERE o.estado != 'eliminado' AND o.estado_orden = 'Entregado'
            ORDER BY heo.fecha_cambio DESC, o.id_orden DESC";
            
    $res = $conexion->query($sql);
    
    if ($res) {
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error SQL: ' . $conexion->error]);
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