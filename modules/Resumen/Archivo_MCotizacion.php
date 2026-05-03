<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'datos_cotizaciones':
        // 1. Cotización de monto más alto (Top 5 para el gráfico)
        $sqlTop = "SELECT nombre_cliente as nombre, monto_total as monto 
                   FROM cotizacion 
                   WHERE estado != 'Rechazada' 
                   ORDER BY monto_total DESC LIMIT 5";
        $resTop = $conexion->query($sqlTop)->fetch_all(MYSQLI_ASSOC);

        // 2. Incremento por Mes (Año actual)
        $sqlMes = "SELECT DATE_FORMAT(fecha_creacion, '%M') as mes, COUNT(id_cotizacion) as total 
                   FROM cotizacion 
                   WHERE YEAR(fecha_creacion) = YEAR(CURDATE()) 
                   GROUP BY MONTH(fecha_creacion) ORDER BY MONTH(fecha_creacion) ASC";
        $resMes = $conexion->query($sqlMes)->fetch_all(MYSQLI_ASSOC);

        // 3. Sucursal que más cotiza
        $sqlSuc = "SELECT s.nombre, COUNT(c.id_cotizacion) as total 
                   FROM cotizacion c 
                   JOIN Sucursal s ON c.id_sucursal = s.id_sucursal 
                   GROUP BY s.id_sucursal ORDER BY total DESC";
        $resSuc = $conexion->query($sqlSuc)->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'tops' => $resTop,
            'historico' => $resMes,
            'sucursales' => $resSuc
        ]);
        break;

    case 'listar_tabla_cotizaciones':
        // Tabla dinámica solicitada
        $sqlTabla = "SELECT c.id_cotizacion, c.nombre_cliente, u.username as usuario, 
                            DATE_FORMAT(c.fecha_creacion, '%d/%m/%Y %h:%i %p') as fecha, 
                            s.nombre as sucursal, c.monto_total, c.estado
                     FROM cotizacion c
                     JOIN usuario u ON c.usuario_creacion = u.id_usuario
                     JOIN Sucursal s ON c.id_sucursal = s.id_sucursal
                     ORDER BY c.fecha_creacion DESC LIMIT 50";
        $res = $conexion->query($sqlTabla);
        echo json_encode(['data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
        break;
}