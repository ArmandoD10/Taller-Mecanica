<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'cargar_deudores':
        cargar_deudores($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function cargar_deudores($conexion) {
    // Agrupamos los créditos por cliente para saber cuánto debe cada uno en total
    $sql = "SELECT 
                c.id_cliente, 
                IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', p.apellido_p)) AS nombre_cliente,
                p.cedula,
                IFNULL((SELECT t.numero 
                 FROM Telefono t 
                 JOIN Cliente_Telefono ct ON t.id_telefono = ct.id_telefono 
                 WHERE ct.id_cliente = c.id_cliente AND ct.estado = 'activo' LIMIT 1), 'S/N') AS telefono,
                SUM(cr.saldo_pendiente) AS total_adeudado,
                MIN(cr.fecha_vencimiento) AS vencimiento_mas_antiguo,
                COUNT(cr.id_credito) AS cantidad_creditos
            FROM Cliente c
            JOIN Persona p ON c.id_persona = p.id_persona
            JOIN Credito cr ON c.id_cliente = cr.id_cliente
            WHERE cr.saldo_pendiente > 0 AND cr.estado_credito IN ('Activo', 'Vencido')
            GROUP BY c.id_cliente, p.tipo_persona, p.nombre, p.apellido_p, p.cedula
            ORDER BY total_adeudado DESC";

    $resultado = $conexion->query($sql);
    $data = [];
    $gran_total_deuda = 0;

    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $data[] = $fila;
            $gran_total_deuda += (float)$fila['total_adeudado'];
        }
    }

    echo json_encode([
        'success' => true, 
        'data' => $data,
        'gran_total_deuda' => $gran_total_deuda
    ]);
}
?>