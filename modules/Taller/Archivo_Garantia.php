<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_usuario_sesion = $_SESSION['id_usuario'] ?? 1;

switch ($action) {
    case 'listar':
        listar_garantias($conexion);
        break;
    case 'anular':
        anular_garantia($conexion, $id_usuario_sesion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function verificar_acceso_admin($conexion, $user, $pass) {
    if (empty($user) || empty($pass)) return false;
    
    $sql = "SELECT u.password_hash 
            FROM usuario u 
            JOIN nivel n ON u.id_nivel = n.id_nivel 
            WHERE u.username = ? AND n.nombre = 'Administrador' AND u.estado = 'activo' LIMIT 1";
            
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        return (password_verify($pass, $row['password_hash']) || $pass === $row['password_hash']);
    }
    return false;
}

function listar_garantias($conexion) {
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
            JOIN orden o ON g.id_orden = o.id_orden
            JOIN vehiculo v ON g.id_vehiculo = v.sec_vehiculo
            JOIN marca m ON v.id_marca = m.id_marca
            JOIN cliente c ON v.id_cliente = c.id_cliente
            JOIN persona p ON c.id_persona = p.id_persona
            WHERE g.estado != 'eliminado'
            ORDER BY g.fecha_creacion DESC";
            
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
}

function anular_garantia($conexion, $id_usuario_sesion) {
    $id_garantia = (int)($_POST['id_garantia'] ?? 0);
    $admin_user = $_POST['admin_user'] ?? '';
    $admin_pass = $_POST['admin_pass'] ?? '';
    $motivo = $_POST['motivo'] ?? '';

    if (!verificar_acceso_admin($conexion, $admin_user, $admin_pass)) {
        echo json_encode(['success' => false, 'message' => 'Credenciales de Administrador incorrectas o cuenta inactiva.']);
        return;
    }

    $conexion->begin_transaction();
    try {
        $sql = "UPDATE garantia_servicio SET estado = 'inactivo' WHERE id_garantia = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_garantia);
        $stmt->execute();

        $log_motivo = "\n\n[ANULADA EL " . date('d/m/Y H:i') . " por $admin_user. Motivo: $motivo]";
        $sql_log = "UPDATE garantia_servicio SET terminos_condiciones = CONCAT(IFNULL(terminos_condiciones,''), ?) WHERE id_garantia = ?";
        $stmt_log = $conexion->prepare($sql_log);
        $stmt_log->bind_param("si", $log_motivo, $id_garantia);
        $stmt_log->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Garantía anulada correctamente por nivel administrativo.']);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => 'Error de sistema: ' . $e->getMessage()]);
    }
}
?>