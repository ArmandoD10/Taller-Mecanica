<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? 1;

switch ($action) {
    case 'listar':
        listar_tipos($conexion);
        break;
    case 'guardar':
        guardar_tipo($conexion, $id_usuario);
        break;
    case 'obtener':
        obtener_tipo($conexion);
        break;
    case 'cambiar_estado':
        cambiar_estado($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function listar_tipos($conexion) {
    $sql = "SELECT id_tipo, nombre, estado, DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha FROM tipo_lavado ORDER BY id_tipo DESC";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
}

function obtener_tipo($conexion) {
    $id_tipo = (int)$_GET['id_tipo'];
    $sql = "SELECT id_tipo, nombre FROM tipo_lavado WHERE id_tipo = $id_tipo";
    $res = $conexion->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false]);
    }
}

function guardar_tipo($conexion, $id_usuario) {
    $id_tipo = !empty($_POST['id_tipo']) ? (int)$_POST['id_tipo'] : 0;
    $nombre = trim($_POST['nombre_tipo']);

    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
        return;
    }

    try {
        if ($id_tipo > 0) {
            // Actualizar existente
            $stmt = $conexion->prepare("UPDATE tipo_lavado SET nombre = ? WHERE id_tipo = ?");
            $stmt->bind_param("si", $nombre, $id_tipo);
            $msg = "Tipo de lavado actualizado.";
        } else {
            // Crear nuevo
            $stmt = $conexion->prepare("INSERT INTO tipo_lavado (nombre, estado, usuario_creacion) VALUES (?, 'activo', ?)");
            $stmt->bind_param("si", $nombre, $id_usuario);
            $msg = "Tipo de lavado creado exitosamente.";
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            throw new Exception("Error al guardar.");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function cambiar_estado($conexion) {
    $id_tipo = (int)$_POST['id_tipo'];
    $nuevo_estado = $_POST['estado']; // 'activo' o 'inactivo'
    
    $stmt = $conexion->prepare("UPDATE tipo_lavado SET estado = ? WHERE id_tipo = ?");
    $stmt->bind_param("si", $nuevo_estado, $id_tipo);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al cambiar estado']);
    }
}
?>