<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'datos_facturas':
        // 1. Facturas de monto más alto (Eje X: Número de Factura)
        $sqlTop = "SELECT CONCAT('FAC-', id_factura) as id, monto_total as monto 
                   FROM factura_central 
                   WHERE estado = 'activo' 
                   ORDER BY monto_total DESC LIMIT 5";
        $resTop = $conexion->query($sqlTop)->fetch_all(MYSQLI_ASSOC);

        // 2. Crecimiento Mensual de Ingresos (Año actual)
        $sqlCrecimiento = "SELECT DATE_FORMAT(fecha_emision, '%M') as mes, SUM(monto_total) as total 
                           FROM factura_central 
                           WHERE YEAR(fecha_emision) = YEAR(CURDATE()) AND estado = 'activo'
                           GROUP BY MONTH(fecha_emision) ORDER BY MONTH(fecha_emision) ASC";
        $resCrecimiento = $conexion->query($sqlCrecimiento)->fetch_all(MYSQLI_ASSOC);

        // 3. Ventas por Sucursal
        $sqlSuc = "SELECT s.nombre, SUM(f.monto_total) as total 
                   FROM factura_central f 
                   JOIN Sucursal s ON f.id_sucursal = s.id_sucursal 
                   WHERE f.estado = 'activo'
                   GROUP BY s.id_sucursal ORDER BY total DESC";
        $resSuc = $conexion->query($sqlSuc)->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'tops' => $resTop,
            'historico' => $resCrecimiento,
            'sucursales' => $resSuc
        ]);
        break;

    case 'listar_tabla_facturas':
        // Tabla dinámica: ID, Origen, Usuario, Fecha, Sucursal, Total
        $sqlTabla = "SELECT f.id_factura, 
                            CASE 
                                WHEN f.id_orden IS NOT NULL THEN 'Taller' 
                                ELSE 'POS (Venta Directa)' 
                            END as origen,
                            u.username as usuario, 
                            DATE_FORMAT(f.fecha_emision, '%d/%m/%Y %h:%i %p') as fecha, 
                            s.nombre as sucursal, f.monto_total
                     FROM factura_central f
                     JOIN usuario u ON f.usuario_creacion = u.id_usuario
                     JOIN Sucursal s ON f.id_sucursal = s.id_sucursal
                     WHERE f.estado = 'activo'
                     ORDER BY f.fecha_emision DESC LIMIT 50";
        $res = $conexion->query($sqlTabla);
        echo json_encode(['data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
        break;
}