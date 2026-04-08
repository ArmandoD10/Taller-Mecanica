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
    // Consulta que busca específicamente el registro de auditoría "Entregado" en la tabla normalizada
    $sql = "SELECT 
                o.id_orden,
                CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS cliente,
                CONCAT(mar.nombre, ' ', IFNULL(v.modelo, '')) AS vehiculo,
                v.placa,
                IFNULL(o.monto_total, 0) AS monto_total,
                CONCAT('RD$ ', FORMAT(IFNULL(o.monto_total, 0), 2)) AS monto_total_fmt,
                
                -- Datos de la auditoría de entrega desde la nueva tabla
                DATE_FORMAT(oe_ent.fecha_creacion, '%d/%m/%Y %h:%i %p') AS fecha_entrega,
                IFNULL(u.username, 'Administrador') AS entregado_por
                
            FROM Orden o
            JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            JOIN Vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN Marca mar ON v.id_marca = mar.id_marca
            JOIN Cliente c ON v.id_cliente = c.id_cliente
            JOIN Persona per ON c.id_persona = per.id_persona
            
            -- Cruce crucial: Buscamos el registro en el historial donde el estado fue Entregado
            INNER JOIN Orden_Estado oe_ent ON o.id_orden = oe_ent.id_orden
            INNER JOIN Estado e_ent ON oe_ent.id_estado = e_ent.id_estado AND e_ent.nombre = 'Entregado'
            LEFT JOIN Usuario u ON oe_ent.usuario_creacion = u.id_usuario
            
            WHERE o.estado != 'eliminado' 
            -- Aseguramos que el estado MÁS RECIENTE de esa orden siga siendo 'Entregado'
            AND (SELECT e2.nombre FROM Orden_Estado oe2 JOIN Estado e2 ON oe2.id_estado = e2.id_estado 
                 WHERE oe2.id_orden = o.id_orden ORDER BY oe2.fecha_creacion DESC LIMIT 1) = 'Entregado'
                 
            ORDER BY oe_ent.fecha_creacion DESC, o.id_orden DESC";
            
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
                
                DATE_FORMAT(oe_ent.fecha_creacion, '%d/%m/%Y %h:%i %p') AS fecha_entrega,
                IFNULL(u.username, 'Administrador (Sistema)') AS entregado_por
                
            FROM Orden o
            JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            JOIN Vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN Marca mar ON v.id_marca = mar.id_marca
            JOIN Cliente c ON v.id_cliente = c.id_cliente
            JOIN Persona per ON c.id_persona = per.id_persona
            
            -- Buscamos quién y cuándo se puso el estado Entregado
            INNER JOIN Orden_Estado oe_ent ON o.id_orden = oe_ent.id_orden
            INNER JOIN Estado e_ent ON oe_ent.id_estado = e_ent.id_estado AND e_ent.nombre = 'Entregado'
            LEFT JOIN Usuario u ON oe_ent.usuario_creacion = u.id_usuario
            
            WHERE o.id_orden = $id_orden LIMIT 1";
            
    $res = $conexion->query($sql);
    
    if($res && $res->num_rows > 0) {
        echo json_encode(['success' => true, 'data' => $res->fetch_assoc()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo generar el acta de entrega de esta orden.']);
    }
}
?>