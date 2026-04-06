<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listar($conexion);
        break;
    case 'guardar':
        guardar($conexion);
        break;
    case 'obtener':
        obtener($conexion);
        break;
    case 'eliminar':
        eliminar($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function listar($conexion) {
    $sql = "SELECT id_tipo_servicio, nombre, descripcion, tiempo_estimado, precio, estado 
        FROM tipo_servicio 
        WHERE estado != 'eliminado' 
        ORDER BY id_tipo_servicio DESC";
            
    $res = $conexion->query($sql);
    $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode(['success' => true, 'data' => $data]);
}

function guardar($conexion) {
    $id = $_POST['id_tipo_servicio'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $tiempo_estimado = $_POST['tiempo_estimado'] ?? '01:00';
    $precio = $_POST['precio_estimado'] ?? 0; // Nuevo
    $estado = $_POST['estado'] ?? 'activo';
    $usuario = $_SESSION['id_usuario'] ?? 1;

    if (strlen($tiempo_estimado) == 5) { $tiempo_estimado .= ':00'; }

    try {
        if ($id == '') {
            $sql = "INSERT INTO tipo_servicio (nombre, descripcion, tiempo_estimado, precio, estado, usuario_creacion) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssdsi", $nombre, $descripcion, $tiempo_estimado, $precio, $estado, $usuario);
        } else {
            $sql = "UPDATE tipo_servicio SET nombre = ?, descripcion = ?, tiempo_estimado = ?, precio= ?, estado = ? WHERE id_tipo_servicio = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssdsi", $nombre, $descripcion, $tiempo_estimado, $precio, $estado, $id);
        }
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Servicio Guardado exitosamente.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtener($conexion) {
    $id = (int)($_GET['id_tipo_servicio'] ?? 0);
    $sql = "SELECT * FROM tipo_servicio WHERE id_tipo_servicio = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function eliminar($conexion) {
    $id = (int)($_POST['id_tipo_servicio'] ?? 0);
    $sql = "UPDATE tipo_servicio SET estado = 'eliminado' WHERE id_tipo_servicio = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Servicio eliminado.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar.']);
    }
}
?>