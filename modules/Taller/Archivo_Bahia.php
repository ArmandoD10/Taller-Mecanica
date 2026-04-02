<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
session_start();

switch ($action) {
    case 'listar':
        listar($conexion);
        break;
    case 'cargar_sucursales':
        cargar_sucursales($conexion);
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
    $sql = "SELECT b.id_bahia, b.descripcion, b.estado_bahia, b.estado, s.nombre AS sucursal 
            FROM bahia b 
            JOIN sucursal s ON b.id_sucursal = s.id_sucursal 
            WHERE b.estado != 'eliminado' 
            ORDER BY b.id_bahia DESC";
            
    $res = $conexion->query($sql);
    $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode(['success' => true, 'data' => $data]);
}

function cargar_sucursales($conexion) {
    $sql = "SELECT id_sucursal, nombre FROM sucursal WHERE estado = 'activo'";
    $res = $conexion->query($sql);
    $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode(['success' => true, 'data' => $data]);
}

function guardar($conexion) {
    $id_bahia = $_POST['id_bahia'] ?? '';
    $id_sucursal = $_POST['id_sucursal'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $estado_bahia = $_POST['estado_bahia'] ?? 'Disponible';
    $estado = $_POST['estado'] ?? 'activo'; // Recibimos el estado del registro
    $usuario_creacion = $_SESSION['id_usuario'] ?? 1;

    try {
        if ($id_bahia == '') {
            $sql = "INSERT INTO bahia (id_sucursal, descripcion, estado_bahia, estado, usuario_creacion) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("isssi", $id_sucursal, $descripcion, $estado_bahia, $estado, $usuario_creacion);
        } else {
            $sql = "UPDATE bahia SET id_sucursal = ?, descripcion = ?, estado_bahia = ?, estado = ? WHERE id_bahia = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("isssi", $id_sucursal, $descripcion, $estado_bahia, $estado, $id_bahia);
        }

        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Bahía guardada correctamente.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function obtener($conexion) {
    $id_bahia = (int)($_GET['id_bahia'] ?? 0);
    $sql = "SELECT * FROM bahia WHERE id_bahia = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_bahia);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function eliminar($conexion) {
    $id_bahia = (int)($_POST['id_bahia'] ?? 0);
    $sql = "UPDATE bahia SET estado = 'eliminado' WHERE id_bahia = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_bahia);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Bahía eliminada.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar.']);
    }
}
?>