<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'datos_stock':
        // 1. Gráfico de Pie: Distribución General de Stock
        $sqlPie = "SELECT nombre, SUM(i.cantidad) as total 
                   FROM Repuesto_Articulo ra 
                   JOIN Inventario i ON ra.id_articulo = i.id_articulo 
                   WHERE ra.estado = 'activo' GROUP BY ra.id_articulo";
        $resPie = $conexion->query($sqlPie)->fetch_all(MYSQLI_ASSOC);

        // 2. Gráfico de Barras: Top 5 Más Vendidos
        $sqlBarras = "SELECT ra.nombre, SUM(df.cantidad) as vendidos 
                      FROM Detalle_Factura df 
                      JOIN Repuesto_Articulo ra ON df.id_articulo = ra.id_articulo 
                      GROUP BY ra.id_articulo ORDER BY vendidos DESC LIMIT 5";
        $resBarras = $conexion->query($sqlBarras)->fetch_all(MYSQLI_ASSOC);

        // 3. Comparativo: Más Caro vs Más Barato
        $sqlPrecios = "(SELECT nombre, precio_venta, 'Caro' as tipo FROM Repuesto_Articulo WHERE estado = 'activo' ORDER BY precio_venta DESC LIMIT 1)
                       UNION
                       (SELECT nombre, precio_venta, 'Barato' as tipo FROM Repuesto_Articulo WHERE estado = 'activo' ORDER BY precio_venta ASC LIMIT 1)";
        $resPrecios = $conexion->query($sqlPrecios)->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'pie' => $resPie,
            'barras' => $resBarras,
            'precios' => $resPrecios
        ]);
        break;

    case 'distribucion_sucursales':
        // Detalle por sucursal para las cards individuales
        $sqlDist = "SELECT ra.nombre as articulo, ra.imagen, s.nombre as sucursal, i.cantidad 
                    FROM Inventario i 
                    JOIN Repuesto_Articulo ra ON i.id_articulo = ra.id_articulo 
                    JOIN Gondola g ON i.id_gondola = g.id_gondola 
                    JOIN Almacen a ON g.id_almacen = a.id_almacen 
                    JOIN Sucursal s ON a.id_sucursal = s.id_sucursal 
                    WHERE ra.estado = 'activo' ORDER BY ra.nombre";
        $resDist = $conexion->query($sqlDist)->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['data' => $resDist]);
        break;
}