<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_sucursal = $_SESSION['id_sucursal'] ?? 1;

switch ($action) {
    case 'listar_historial':
        listar_historial($conexion, $id_sucursal);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function listar_historial($conexion, $id_sucursal) {
    // Filtros de fecha opcionales
    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Por defecto, el mes actual
    $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

    // Usamos la misma lógica robusta de COALESCE para identificar clientes y vehículos
    $sql = "SELECT ol.id_orden_lavado, 
                   IFNULL(ol.id_orden, 'Express') as origen_orden, 
                   DATE_FORMAT(ol.fecha_creacion, '%d/%m/%Y %h:%i %p') as fecha_fmt,
                   tl.nombre as tipo_lavado, 
                   ol.monto_total, 
                   ol.estado_lavado,
                   IF(ol.id_orden IS NULL, 1, 0) as es_express,
                   
                   COALESCE(
                       ol.vehiculo_ocasional,
                       CONCAT(m.nombre, ' ', v.modelo, ' [', v.placa, ']'),
                       CONCAT(mo.nombre, ' ', vo.modelo, ' [', vo.placa, ']')
                   ) AS vehiculo,
                   
                   COALESCE(
                       ol.nombre_ocasional,
                       CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')),
                       CONCAT(po.nombre, ' ', IFNULL(po.apellido_p, ''))
                   ) AS cliente,

                   -- Buscamos si tiene factura express asociada
                   (SELECT id_factura_lavado FROM factura_lavado fl WHERE fl.id_orden_lavado = ol.id_orden_lavado LIMIT 1) as id_factura_express

            FROM orden_lavado ol
            JOIN tipo_lavado tl ON ol.id_tipo_lavado = tl.id_tipo
            
            -- Para clientes registrados directos
            LEFT JOIN vehiculo v ON ol.vin_chasis = v.vin_chasis AND ol.placa = v.placa
            LEFT JOIN marca m ON v.id_marca = m.id_marca
            LEFT JOIN cliente c ON ol.id_cliente = c.id_cliente
            LEFT JOIN persona p ON c.id_persona = p.id_persona
            
            -- Para vehículos del Taller
            LEFT JOIN orden o ON ol.id_orden = o.id_orden
            LEFT JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            LEFT JOIN vehiculo vo ON i.id_vehiculo = vo.sec_vehiculo
            LEFT JOIN marca mo ON vo.id_marca = mo.id_marca
            LEFT JOIN cliente co ON vo.id_cliente = co.id_cliente
            LEFT JOIN persona po ON co.id_persona = po.id_persona
            
            WHERE ol.id_sucursal = $id_sucursal 
            AND ol.estado = 'activo'
            AND DATE(ol.fecha_creacion) BETWEEN '$fecha_inicio' AND '$fecha_fin'
            ORDER BY ol.id_orden_lavado DESC";
            
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
}
?>