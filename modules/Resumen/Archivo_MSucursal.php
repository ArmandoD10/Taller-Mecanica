<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'datos_sucursales':
        // 1. Sucursal con más Ventas (Monto Total FAC)
        $sqlVentas = "SELECT s.nombre, SUM(f.monto_total) as total 
                      FROM factura_central f 
                      JOIN Sucursal s ON f.id_sucursal = s.id_sucursal 
                      WHERE f.estado = 'activo'
                      GROUP BY s.id_sucursal ORDER BY total DESC";
        $resVentas = $conexion->query($sqlVentas)->fetch_all(MYSQLI_ASSOC);

        // 2. Sucursal con más Órdenes de Servicio
        $sqlOrdenes = "SELECT s.nombre, COUNT(o.id_orden) as total 
                       FROM Orden o 
                       JOIN Sucursal s ON o.id_sucursal = s.id_sucursal 
                       WHERE o.estado != 'eliminado'
                       GROUP BY s.id_sucursal ORDER BY total DESC";
        $resOrdenes = $conexion->query($sqlOrdenes)->fetch_all(MYSQLI_ASSOC);

        // 3. Ticket Promedio (Monto Total / Cantidad de Facturas)
        $sqlPromedio = "SELECT s.nombre, ROUND(AVG(f.monto_total), 2) as promedio 
                        FROM factura_central f 
                        JOIN Sucursal s ON f.id_sucursal = s.id_sucursal 
                        WHERE f.estado = 'activo'
                        GROUP BY s.id_sucursal";
        $resPromedio = $conexion->query($sqlPromedio)->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'ventas' => $resVentas,
            'ordenes' => $resOrdenes,
            'promedios' => $resPromedio
        ]);
        break;

    case 'listar_tabla_ranking':
        // Tabla de rendimiento comparativo
        $sqlRanking = "SELECT s.nombre as sucursal, 
                              COUNT(DISTINCT f.id_factura) as total_facturas,
                              SUM(f.monto_total) as ingresos_totales,
                              (SELECT COUNT(*) FROM Orden o2 WHERE o2.id_sucursal = s.id_sucursal) as total_ordenes
                       FROM Sucursal s
                       LEFT JOIN factura_central f ON s.id_sucursal = f.id_sucursal AND f.estado = 'activo'
                       GROUP BY s.id_sucursal 
                       ORDER BY ingresos_totales DESC";
        $res = $conexion->query($sqlRanking);
        echo json_encode(['data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
        break;
}