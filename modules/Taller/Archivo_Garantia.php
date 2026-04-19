<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$action = $_GET['action'] ?? '';
$id_usuario_sesion = $_SESSION['id_usuario'] ?? 1;

switch ($action) {
    case 'listar':
        listar_garantias($conexion);
        break;
    case 'ver_detalle':
        ver_detalle_garantia($conexion);
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
    $sql = "SELECT u.password_hash FROM usuario u JOIN nivel n ON u.id_nivel = n.id_nivel WHERE u.username = ? AND n.nombre = 'Administrador' AND u.estado = 'activo' LIMIT 1";
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
    try {
        // Se añadió DATE(g.fecha_creacion) as fecha_db para el filtro por rango
        $sql = "SELECT 
                    g.id_garantia, g.codigo_certificado, 
                    DATE_FORMAT(g.fecha_creacion, '%d/%m/%Y') as fecha_emision,
                    DATE(g.fecha_creacion) as fecha_db,
                    g.estado, o.id_orden,
                    CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) as cliente,
                    CONCAT(m.nombre, ' ', IFNULL(v.modelo, ''), ' [', v.placa, ']') as vehiculo,
                    (SELECT COUNT(*) FROM orden_servicio WHERE id_orden = o.id_orden AND id_politica IS NOT NULL) +
                    (SELECT COUNT(*) FROM orden_repuesto WHERE id_orden = o.id_orden AND id_politica IS NOT NULL) as items_amparados
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
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function ver_detalle_garantia($conexion) {
    try {
        $id_orden = (int)$_GET['id_orden'];
        
        $sql = "SELECT 
                    'Servicio' as tipo, ts.nombre as descripcion, pg.nombre as politica, 
                    DATE_FORMAT(os.fecha_vencimiento, '%d/%m/%Y') as vence_fecha,
                    IFNULL(os.kilometraje_vencimiento, 'Ilimitado') as vence_km,
                    CASE 
                        WHEN os.fecha_vencimiento < CURDATE() THEN 'Vencida'
                        ELSE 'Activa'
                    END as estado_linea
                FROM orden_servicio os
                JOIN tipo_servicio ts ON os.id_tipo_servicio = ts.id_tipo_servicio
                JOIN politica_garantia pg ON os.id_politica = pg.id_politica
                WHERE os.id_orden = $id_orden AND os.id_politica IS NOT NULL
                UNION ALL
                SELECT 
                    'Repuesto' as tipo, ra.nombre as descripcion, pg.nombre as politica, 
                    DATE_FORMAT(or_p.fecha_vencimiento, '%d/%m/%Y') as vence_fecha,
                    IFNULL(or_p.kilometraje_vencimiento, 'Ilimitado') as vence_km,
                    CASE 
                        WHEN or_p.fecha_vencimiento < CURDATE() THEN 'Vencida'
                        ELSE 'Activa'
                    END as estado_linea
                FROM orden_repuesto or_p
                JOIN repuesto_articulo ra ON or_p.id_articulo = ra.id_articulo
                JOIN politica_garantia pg ON or_p.id_politica = pg.id_politica
                WHERE or_p.id_orden = $id_orden AND or_p.id_politica IS NOT NULL";
                
        $res = $conexion->query($sql);
        echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function anular_garantia($conexion, $id_usuario_sesion) {
    $id_garantia = (int)($_POST['id_garantia'] ?? 0);
    $id_orden = (int)($_POST['id_orden'] ?? 0);
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

        $fecha_anulacion = date('Y-m-d', strtotime('-1 day'));
        $conexion->query("UPDATE orden_servicio SET fecha_vencimiento = '$fecha_anulacion' WHERE id_orden = $id_orden AND id_politica IS NOT NULL");
        $conexion->query("UPDATE orden_repuesto SET fecha_vencimiento = '$fecha_anulacion' WHERE id_orden = $id_orden AND id_politica IS NOT NULL");

        $log_motivo = "\n\n[ANULADA EL " . date('d/m/Y H:i') . " por $admin_user. Motivo: $motivo]";
        $sql_log = "UPDATE garantia_servicio SET terminos_condiciones = CONCAT(IFNULL(terminos_condiciones,''), ?) WHERE id_garantia = ?";
        $stmt_log = $conexion->prepare($sql_log);
        $stmt_log->bind_param("si", $log_motivo, $id_garantia);
        $stmt_log->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Garantía y coberturas anuladas correctamente por nivel administrativo.']);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => 'Error de sistema: ' . $e->getMessage()]);
    }
}
?>