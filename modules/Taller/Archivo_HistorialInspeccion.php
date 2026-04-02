<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar_historial':
        listar_historial($conexion);
        break;
    case 'obtener_detalle':
        obtener_detalle($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function listar_historial($conexion) {
    // Traemos el listado general combinando las tablas necesarias
    $sql = "SELECT 
            i.id_inspeccion,
            DATE_FORMAT(i.fecha_inspeccion, '%d/%m/%Y %h:%i %p') AS fecha_formateada,
            IF(p_cli.tipo_persona = 'Juridica', 
            p_cli.nombre, 
            CONCAT(p_cli.nombre, ' ', IFNULL(p_cli.apellido_p, ''))
            ) AS cliente,
            -- Usamos v.modelo directamente en lugar de mo.nombre
            CONCAT(m.nombre, ' ', v.modelo, ' (', IFNULL(v.placa, v.vin_chasis), ')') AS vehiculo,
            CONCAT(p_emp.nombre, ' ', IFNULL(p_emp.apellido_p, '')) AS asesor,
            i.estado,
            v.modelo -- Traído directamente de la tabla Vehiculo
        FROM Inspeccion i
        JOIN Vehiculo v ON i.id_vehiculo = v.sec_vehiculo
        JOIN Marca m ON v.id_marca = m.id_marca
        JOIN Cliente c ON v.id_cliente = c.id_cliente
        JOIN Persona p_cli ON c.id_persona = p_cli.id_persona
        JOIN Empleado e ON i.id_empleado = e.id_empleado
        JOIN Persona p_emp ON e.id_persona = p_emp.id_persona
        WHERE i.estado != 'eliminado'
        ORDER BY i.id_inspeccion DESC;";

    $resultado = $conexion->query($sql);
    $data = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $data[] = $fila;
        }
    }
    echo json_encode(['success' => true, 'data' => $data]);
}

function obtener_detalle($conexion) {
    $id_inspeccion = (int)($_GET['id_inspeccion'] ?? 0);

    if ($id_inspeccion === 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    $sql = "SELECT 
            i.id_inspeccion,
            DATE_FORMAT(i.fecha_inspeccion, '%Y-%m-%d') AS fecha,
            DATE_FORMAT(i.fecha_inspeccion, '%H:%i') AS hora,
            i.kilometraje_recepcion,
            i.nivel_combustible,
            i.observacion AS motivo_visita,
            IF(p_cli.tipo_persona = 'Juridica', 
            p_cli.nombre, 
            CONCAT(p_cli.nombre, ' ', IFNULL(p_cli.apellido_p, ''))
            ) AS cliente,
            p_cli.cedula AS documento_cliente,
            -- Cambiamos mo.nombre por v.modelo directamente
            CONCAT(m.nombre, ' ', v.modelo) AS vehiculo_desc,
            IFNULL(v.placa, 'S/P') AS placa,
            v.vin_chasis,
            v.modelo, -- Este es el campo VARCHAR(50) de tu tabla Vehiculo
            col.nombre AS color,
            CONCAT(p_emp.nombre, ' ', IFNULL(p_emp.apellido_p, '')) AS asesor
        FROM Inspeccion i
        JOIN Vehiculo v ON i.id_vehiculo = v.sec_vehiculo
        JOIN Color col ON v.id_color = col.id_color
        JOIN Marca m ON v.id_marca = m.id_marca
        JOIN Cliente c ON v.id_cliente = c.id_cliente
        JOIN Persona p_cli ON c.id_persona = p_cli.id_persona
        JOIN Empleado e ON i.id_empleado = e.id_empleado
        JOIN Persona p_emp ON e.id_persona = p_emp.id_persona
        WHERE i.id_inspeccion = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_inspeccion);
    $stmt->execute();
    $detalle = $stmt->get_result()->fetch_assoc();

    if ($detalle) {
        echo json_encode(['success' => true, 'data' => $detalle]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Inspección no encontrada']);
    }
}
?>