<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$usuario = $_SESSION['id_usuario'] ?? 1;

switch ($action) {
    case 'listar_activos':
        // Carga solo los activos para el formulario de inspección
        $res = $conexion->query("SELECT id_trabajo, descripcion FROM trabajo_solicitado WHERE estado = 'activo' ORDER BY descripcion ASC");
        echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
        break;

    case 'listar_todos':
        // Carga todos para la tabla de mantenimiento
        $res = $conexion->query("SELECT id_trabajo, descripcion, estado, DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha FROM trabajo_solicitado WHERE estado != 'eliminado' ORDER BY id_trabajo DESC");
        echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
        break;

    case 'guardar':
        $desc = trim($_POST['descripcion'] ?? '');
        if(empty($desc)) {
            echo json_encode(['success' => false, 'message' => 'La descripción es obligatoria.']);
            exit;
        }
        $stmt = $conexion->prepare("INSERT INTO trabajo_solicitado (descripcion, estado, usuario_creacion) VALUES (?, 'activo', ?)");
        $stmt->bind_param("si", $desc, $usuario);
        if($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Trabajo agregado al catálogo.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $conexion->error]);
        }
        break;

    case 'actualizar':
        $id = (int)$_POST['id_trabajo'];
        $desc = trim($_POST['descripcion'] ?? '');
        $estado = $_POST['estado'] ?? 'activo';
        
        $stmt = $conexion->prepare("UPDATE trabajo_solicitado SET descripcion = ?, estado = ? WHERE id_trabajo = ?");
        $stmt->bind_param("ssi", $desc, $estado, $id);
        if($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Trabajo actualizado.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $conexion->error]);
        }
        break;

    case 'eliminar':
        $id = (int)$_POST['id_trabajo'];
        $conexion->query("UPDATE trabajo_solicitado SET estado = 'eliminado' WHERE id_trabajo = $id");
        echo json_encode(['success' => true, 'message' => 'Trabajo eliminado del catálogo.']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>