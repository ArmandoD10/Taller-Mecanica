<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        // Agregamos DATE(a.fecha_creacion) AS fecha_raw para el filtro exacto en JavaScript
        $sql = "SELECT a.id_abono, a.monto, a.metodo_pago, IFNULL(a.referencia, 'N/A') AS referencia,
                    DATE_FORMAT(a.fecha_creacion, '%d/%m/%Y %h:%i %p') AS fecha,
                    DATE(a.fecha_creacion) AS fecha_raw,
                    f.id_factura, f.id_orden, CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS cliente,
                    IFNULL(u.username, 'Caja') AS cajero
                FROM Abono_Factura a
                JOIN Factura_Central f ON a.id_factura = f.id_factura
                JOIN Cliente c ON f.id_cliente = c.id_cliente
                JOIN Persona per ON c.id_persona = per.id_persona
                LEFT JOIN Usuario u ON a.usuario_creacion = u.id_usuario
                WHERE a.estado = 'activo'
                ORDER BY a.fecha_creacion DESC";
        $res = $conexion->query($sql);
        echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
        break;

    case 'obtener_recibo':
        $id_abono = (int)$_GET['id_abono'];
        $sql = "SELECT a.id_abono, a.monto, a.metodo_pago, a.referencia, DATE_FORMAT(a.fecha_creacion, '%d/%m/%Y %h:%i %p') AS fecha,
                    f.id_factura, f.id_orden, f.monto_total, CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS cliente,
                    IFNULL(u.username, 'Caja') AS cajero,
                    (f.monto_total - (SELECT SUM(monto) FROM Abono_Factura WHERE id_factura = f.id_factura AND id_abono <= a.id_abono AND estado='activo')) AS balance_restante
                FROM Abono_Factura a
                JOIN Factura_Central f ON a.id_factura = f.id_factura
                JOIN Cliente c ON f.id_cliente = c.id_cliente
                JOIN Persona per ON c.id_persona = per.id_persona
                LEFT JOIN Usuario u ON a.usuario_creacion = u.id_usuario
                WHERE a.id_abono = $id_abono";
        $res = $conexion->query($sql);
        echo json_encode(['success' => true, 'data' => $res ? $res->fetch_assoc() : null]);
        break;
}
?>