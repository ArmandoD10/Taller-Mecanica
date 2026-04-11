<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? 1;

switch ($action) {
    case 'listar':
        listar_garantias($conexion);
        break;
    case 'anular':
        anular_garantia($conexion, $id_usuario);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function listar_garantias($conexion) {
    // Calculamos el "estado_real" dinámicamente cruzando el estado de la BD con la fecha de hoy
    $sql = "SELECT 
                g.id_garantia, g.codigo_certificado, 
                DATE_FORMAT(g.fecha_creacion, '%d/%m/%Y') as fecha_emision,
                DATE_FORMAT(g.fecha_vencimiento, '%d/%m/%Y') as fecha_vence_fmt,
                g.fecha_vencimiento, g.kilometraje_limite, g.estado, o.id_orden,
                CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) as cliente,
                CONCAT(m.nombre, ' ', IFNULL(v.modelo, ''), ' [', v.placa, ']') as vehiculo,
                CASE 
                    WHEN g.estado = 'inactivo' THEN 'Anulada'
                    WHEN g.fecha_vencimiento < CURDATE() THEN 'Vencida'
                    ELSE 'Activa'
                END as estado_real
            FROM garantia_servicio g
            JOIN Orden o ON g.id_orden = o.id_orden
            JOIN Vehiculo v ON g.id_vehiculo = v.sec_vehiculo
            JOIN Marca m ON v.id_marca = m.id_marca
            JOIN Cliente c ON v.id_cliente = c.id_cliente
            JOIN Persona p ON c.id_persona = p.id_persona
            WHERE g.estado != 'eliminado'
            ORDER BY g.fecha_creacion DESC";
            
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
}

function anular_garantia($conexion, $id_usuario) {
    $id_garantia = (int)($_POST['id_garantia'] ?? 0);
    $motivo = $_POST['motivo'] ?? 'Anulada por administración';

    if ($id_garantia === 0) {
        echo json_encode(['success' => false, 'message' => 'ID de garantía no válido.']);
        return;
    }

    $conexion->begin_transaction();
    try {
        // Pasamos la garantía a inactiva
        $sql = "UPDATE garantia_servicio SET estado = 'inactivo' WHERE id_garantia = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_garantia);
        $stmt->execute();

        // Opcional: Podrías guardar el motivo de la anulación en una tabla de auditoría aquí

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Garantía anulada correctamente.']);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al anular: ' . $e->getMessage()]);
    }
}
?>