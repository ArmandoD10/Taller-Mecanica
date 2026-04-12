<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
// Asegúrate de filtrar por la sucursal del usuario para ver movimientos locales
$id_sucursal_actual = $_SESSION['id_sucursal'] ?? 1;

switch ($action) {
    case 'listar_movimientos':
        $inicio = !empty($_GET['f_inicio']) ? $_GET['f_inicio'] : null;
        $fin = !empty($_GET['f_fin']) ? $_GET['f_fin'] : null;

        $where = " WHERE a.id_sucursal = $id_sucursal_actual ";
        if ($inicio && $fin) {
            $where .= " AND DATE(m.fecha_movimiento) BETWEEN '$inicio' AND '$fin' ";
        }

        // Unimos con Almacen y Sucursal para traer el nombre real
        $sql = "SELECT m.sec_movimiento_invent as id, 
                       m.fecha_movimiento, 
                       tm.nombre as tipo,
                       a.nombre as almacen_destino,
                       s.nombre as sucursal_nombre,
                       m.estado
                FROM Movimiento_Inventario_Almacen m
                INNER JOIN Tipo_Movimiento_Inv tm ON m.id_tipo_movimiento = tm.id_tipo_movimiento
                INNER JOIN Gondola g ON m.id_gondola = g.id_gondola
                INNER JOIN Almacen a ON g.id_almacen = a.id_almacen
                INNER JOIN Sucursal s ON a.id_sucursal = s.id_sucursal
                $where
                ORDER BY m.fecha_movimiento DESC";
        
        $res = $conexion->query($sql);
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        break;

    case 'detalle_movimiento':
        $id = (int)$_GET['id'];
        
        // CORRECCIÓN: El artículo está en Detalle_Compra (dc), no en Compra (c)
        $sql = "SELECT ra.nombre, dc.cantidad_recibida as cantidad, ra.imagen
                FROM Movimiento_Inventario_Almacen m
                INNER JOIN Compra c ON m.id_compra = c.id_compra
                INNER JOIN Detalle_Compra dc ON c.id_compra = dc.id_compra
                INNER JOIN Repuesto_Articulo ra ON dc.id_articulo = ra.id_articulo
                WHERE m.sec_movimiento_invent = $id";
        
        $res = $conexion->query($sql);
        if (!$res) {
            echo json_encode(['success' => false, 'message' => $conexion->error]);
        } else {
            echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        }
        break;
}