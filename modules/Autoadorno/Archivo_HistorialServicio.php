<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_sucursal = $_SESSION['id_sucursal'] ?? 1;

switch ($action) {
    // 1. LISTAR TODAS LAS ORDENES DE AUTOADORNO
   case 'listar_ordenes':
        $sql = "SELECT o.id_orden, o.descripcion, o.monto_total, o.fecha_creacion, e.nombre as estado
                FROM orden o
                JOIN Orden_Estado oe ON o.id_orden = oe.id_orden
                JOIN Estado e ON oe.id_estado = e.id_estado
                WHERE o.id_sucursal = $id_sucursal 
                AND o.descripcion LIKE 'ORDEN AUTOADORNO%'
                AND oe.sec_orden_estado = (SELECT MAX(sec_orden_estado) FROM Orden_Estado WHERE id_orden = o.id_orden)
                ORDER BY o.id_orden DESC LIMIT 10";
        $res = $conexion->query($sql);
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        break;

    // 2. FILTRAR POR FECHAS O ID
    // Dentro de Archivo_HistorialServicio.php
case 'filtrar_historial':
    // Limpieza de ID: Si es "null" o vacío, que sea null real
    $id_o = (!empty($_GET['id_orden']) && $_GET['id_orden'] !== 'null') ? (int)$_GET['id_orden'] : null;
    $inicio = !empty($_GET['f_inicio']) ? $_GET['f_inicio'] : null;
    $fin = !empty($_GET['f_fin']) ? $_GET['f_fin'] : null;

    $where = " WHERE o.id_sucursal = $id_sucursal AND o.descripcion LIKE 'ORDEN AUTOADORNO%' ";

    if ($id_o) {
        $where .= " AND o.id_orden = $id_o ";
    } elseif ($inicio && $fin) {
        // Aseguramos el rango usando DATE() para ignorar las horas si es necesario
        $where .= " AND DATE(o.fecha_creacion) BETWEEN '$inicio' AND '$fin' ";
    }

    $sql = "SELECT o.id_orden, o.descripcion, o.monto_total, 
                   DATE_FORMAT(o.fecha_creacion, '%d/%m/%Y %h:%i %p') as fecha_formateada,
                   e.nombre as estado
            FROM orden o
            INNER JOIN Orden_Estado oe ON o.id_orden = oe.id_orden
            INNER JOIN Estado e ON oe.id_estado = e.id_estado
            $where
            AND oe.sec_orden_estado = (SELECT MAX(sec_orden_estado) FROM Orden_Estado WHERE id_orden = o.id_orden)
            ORDER BY o.id_orden DESC";
    
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
    break;

    // Dentro del switch ($action) en Archivo_HistorialServicio.php
case 'reporte_pdf':
    reporte_pdf($conexion, $id_sucursal);
    break;

    // 3. DETALLE COMPLETO PARA EL MODAL (Servicios y Repuestos con Imagen)
    case 'detalle_orden_historial':
        $id_o = (int)$_GET['id_orden'];
        
        $sql_r = "SELECT r.cantidad, r.precio_base as precio, ra.nombre, ra.imagen 
                  FROM orden_repuesto r 
                  INNER JOIN repuesto_articulo ra ON r.id_articulo = ra.id_articulo 
                  WHERE r.id_orden = $id_o";
        $repuestos = $conexion->query($sql_r)->fetch_all(MYSQLI_ASSOC);

        $sql_t = "SELECT monto_total FROM orden WHERE id_orden = $id_o";
        $total = $conexion->query($sql_t)->fetch_assoc();

        echo json_encode([
            'success' => true, 
            'repuestos' => $repuestos,
            'total' => $total['monto_total']
        ]);
        break;
}

// Función al final del archivo
function reporte_pdf($conexion, $id_sucursal) {
    $sql = "SELECT o.id_orden, o.descripcion, o.monto_total, 
                   DATE_FORMAT(o.fecha_creacion, '%d/%m/%Y') as fecha_fmt,
                   e.nombre as estado
            FROM orden o
            JOIN Orden_Estado oe ON o.id_orden = oe.id_orden
            JOIN Estado e ON oe.id_estado = e.id_estado
            WHERE o.id_sucursal = $id_sucursal 
            AND o.descripcion LIKE 'ORDEN AUTOADORNO%'
            AND oe.sec_orden_estado = (SELECT MAX(sec_orden_estado) FROM Orden_Estado WHERE id_orden = o.id_orden)
            ORDER BY o.id_orden DESC";
            
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
    exit;
}