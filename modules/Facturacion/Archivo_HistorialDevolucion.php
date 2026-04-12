<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar_devoluciones':
        listar_devoluciones($conexion);
        break;
    case 'obtener_detalle_completo':
        obtener_detalle_completo($conexion);
        break;
}

function listar_devoluciones($conexion) {
    // Recibimos las fechas pero pueden venir vacías
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';

    try {
        $sql = "SELECT d.id_devolucion, 
                       d.id_factura, 
                       DATE_FORMAT(d.fecha_devolucion, '%d/%m/%Y %h:%i %p') as fecha,
                       d.motivo, 
                       d.estado_producto,
                       d.monto_devuelto,
                       u.username as admin_autorizo,
                       IFNULL(IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, ''))), 'Cliente Contado') as cliente
                FROM Devolucion d
                JOIN Factura_Central f ON d.id_factura = f.id_factura
                JOIN Usuario u ON d.id_usuario_admin = u.id_usuario
                LEFT JOIN Cliente c ON f.id_cliente = c.id_cliente
                LEFT JOIN Persona p ON c.id_persona = p.id_persona
                WHERE 1=1"; // Base para agregar filtros dinámicos

        // Solo agregamos el filtro si AMBAS fechas tienen valor
        if (!empty($fecha_inicio) && !empty($fecha_fin)) {
            $sql .= " AND DATE(d.fecha_devolucion) >= '$fecha_inicio' AND DATE(d.fecha_devolucion) <= '$fecha_fin'";
        }

        $sql .= " ORDER BY d.id_devolucion DESC";

        $res = $conexion->query($sql);
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}