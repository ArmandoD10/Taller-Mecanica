<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listar($conexion);
        break;
    case 'obtener_detalles':
        obtener_detalles($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function listar($conexion) {
    // Consulta avanzada: Trae la orden, calcula el total pagado y verifica si hay recepciones
    $sql = "SELECT c.id_compra, c.fecha_creacion, c.monto as total_orden, c.estado,
                   p.nombre_comercial, mo.codigo_ISO as moneda,
                   IFNULL((SELECT SUM(monto_pagado) 
                           FROM pago_compra 
                           WHERE id_compra = c.id_compra AND estado = 'activo'), 0) as total_pagado,
                   (SELECT COUNT(*) 
                    FROM recepcion_compra 
                    WHERE id_compra = c.id_compra AND estado = 'activo') as cantidad_recepciones
            FROM compra c
            JOIN proveedor p ON c.id_proveedor = p.id_proveedor
            JOIN moneda mo ON c.id_moneda = mo.id_moneda
            ORDER BY c.id_compra DESC";
            
    $res = $conexion->query($sql);
    
    if (!$res) {
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $conexion->error]);
        return;
    }
    
    echo json_encode([
        'success' => true, 
        'data' => $res->fetch_all(MYSQLI_ASSOC)
    ]);
}

function obtener_detalles($conexion) {
    $id_compra = (int)$_GET['id_compra'];
    
    // Traemos los artículos que componen la orden
    $sql = "SELECT d.id_articulo, a.nombre, a.num_serie, 
                   d.cantidad_pedida, d.cantidad_recibida, 
                   d.precio, d.subtotal
            FROM detalle_compra d
            JOIN repuesto_articulo a ON d.id_articulo = a.id_articulo
            WHERE d.id_compra = ?";
            
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_compra);
    $stmt->execute();
    
    $detalles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'data' => $detalles
    ]);
}
?>