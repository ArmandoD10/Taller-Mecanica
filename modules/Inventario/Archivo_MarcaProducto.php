<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listar($conexion);
        break;
    case 'cargar_dependencias':
        cargar_dependencias($conexion);
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
    $sql = "SELECT m.id_marca_producto, m.nombre, m.correo, m.estado, p.nombre AS pais_origen
            FROM marca_producto m
            JOIN pais p ON m.id_pais = p.id_pais
            WHERE m.estado != 'eliminado'
            ORDER BY m.id_marca_producto DESC";
            
    $res = $conexion->query($sql);
    
    echo json_encode([
        'success' => true, 
        'data' => $res->fetch_all(MYSQLI_ASSOC)
    ]);
}

function cargar_dependencias($conexion) {
    $sql = "SELECT id_pais, nombre FROM pais WHERE estado = 'activo' ORDER BY nombre ASC";
    $res = $conexion->query($sql);
    
    echo json_encode([
        'success' => true, 
        'data' => $res->fetch_all(MYSQLI_ASSOC)
    ]);
}

function guardar($conexion) {
    $id_marca = $_POST['id_marca_producto'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $id_pais = (int)($_POST['id_pais'] ?? 0);
    $correo = trim($_POST['correo'] ?? '');
    $estado = $_POST['estado'] ?? 'activo';
    
    $usuario = $_SESSION['id_usuario'] ?? 1;
    $fecha_actual = date('Y-m-d H:i:s');

    // --- VALIDACIÓN ANTIDUPLICADOS ---
    // Evitar que registren la misma marca dos veces
    $id_check = $id_marca ?: 0;
    $sql_val = "SELECT id_marca_producto FROM marca_producto WHERE nombre = ? AND estado != 'eliminado' AND id_marca_producto != ?";
    $stmt_val = $conexion->prepare($sql_val);
    $stmt_val->bind_param("si", $nombre, $id_check);
    $stmt_val->execute();
    
    if ($stmt_val->get_result()->num_rows > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Error: Ya existe una marca registrada con ese nombre.'
        ]);
        return;
    }

    try {
        if ($id_marca == '') {
            // INSERTAR NUEVA MARCA
            $sql = "INSERT INTO marca_producto (nombre, id_pais, correo, estado, fecha_creacion, usuario_creacion) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sisssi", $nombre, $id_pais, $correo, $estado, $fecha_actual, $usuario);
        } else {
            // ACTUALIZAR MARCA EXISTENTE
            $sql = "UPDATE marca_producto 
                    SET nombre = ?, id_pais = ?, correo = ?, estado = ? 
                    WHERE id_marca_producto = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sissi", $nombre, $id_pais, $correo, $estado, $id_marca);
        }
        
        $stmt->execute();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Marca guardada correctamente.'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Error en base de datos: ' . $e->getMessage()
        ]);
    }
}

function obtener($conexion) {
    $id = (int)$_GET['id_marca_producto'];
    
    $sql = "SELECT * FROM marca_producto WHERE id_marca_producto = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    echo json_encode([
        'success' => true, 
        'data' => $stmt->get_result()->fetch_assoc()
    ]);
}

function eliminar($conexion) {
    $id = (int)$_POST['id_marca_producto'];
    
    // Eliminado lógico
    $sql = "UPDATE marca_producto SET estado = 'eliminado' WHERE id_marca_producto = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Marca eliminada del catálogo.'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al eliminar la marca.'
        ]);
    }
}
?>