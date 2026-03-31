<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
session_start();

switch ($action) {
    case 'cargar':
        cargar($conexion);
        break;
    case 'guardar':
        guardar($conexion);
        break;
    case 'actualizar':
        actualizar($conexion);
        break;
    case 'cambiar_estado':
        cambiar_estado($conexion);
        break;
    case 'get_marcas':
        get_marcas($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
        break;
}

function cargar($conexion) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
    $offset = ($page - 1) * $limit;

    $count_sql = "SELECT COUNT(*) as total FROM Modelo WHERE estado != 'eliminado'";
    $count_result = $conexion->query($count_sql);
    $total_rows = $count_result->fetch_assoc()['total'];

    $sql = "SELECT 
                m.id_modelo, 
                m.nombre AS modelo_nombre, 
                m.id_marca, 
                ma.nombre AS marca_nombre, 
                m.fecha_lanzamiento, 
                m.estado 
            FROM Modelo m
            INNER JOIN Marca ma ON m.id_marca = ma.id_marca
            WHERE m.estado != 'eliminado'
            ORDER BY m.id_modelo DESC
            LIMIT $limit OFFSET $offset";

    $resultado = $conexion->query($sql);
    $modelos = [];
    while ($fila = $resultado->fetch_assoc()) {
        $modelos[] = $fila;
    }

    echo json_encode([
        'data' => $modelos,
        'total_records' => (int)$total_rows,
        'page' => $page,
        'limit' => $limit
    ]);
}

function guardar($conexion) {
    $id_usuario = $_SESSION['id_usuario'] ?? null;
    $nombre = $conexion->real_escape_string(trim($_POST['nombre'] ?? ''));
    $id_marca = (int)($_POST['id_marca'] ?? 0);
    $fecha = $_POST['fecha_lanzamiento'] ?? null;

    if (empty($nombre) || $id_marca === 0) {
        echo json_encode(['success' => false, 'message' => 'Nombre y Marca son obligatorios.']);
        exit;
    }

    $sql = "INSERT INTO Modelo (id_marca, nombre, fecha_lanzamiento, fecha_creacion, usuario_creacion, estado)
            VALUES ($id_marca, '$nombre', '$fecha', NOW(), '$id_usuario', 'activo')";

    if ($conexion->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Modelo registrado con éxito.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conexion->error]);
    }
}

function cambiar_estado($conexion) {
    $id = (int)$_POST['id_modelo'];
    $estado = $conexion->real_escape_string($_POST['estado']);
    
    $sql = "UPDATE Modelo SET estado = '$estado' WHERE id_modelo = $id";
    if ($conexion->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Estado actualizado.']);
    } else {
        echo json_encode(['success' => false, 'message' => $conexion->error]);
    }
}

function get_marcas($conexion) {
    $res = $conexion->query("SELECT id_marca, nombre FROM Marca WHERE estado = 'activo' ORDER BY nombre ASC");
    $marcas = [];
    while($row = $res->fetch_assoc()) { $marcas[] = $row; }
    echo json_encode(['marcas' => $marcas]);
}

function actualizar($conexion) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Aseguramos que los IDs sean enteros y los textos estén limpios
        $id_modelo = (int)($_POST['id_modelo'] ?? 0);
        $nombre = $conexion->real_escape_string(trim($_POST['nombre'] ?? ''));
        $id_marca = (int)($_POST['id_marca'] ?? 0);
        $fecha = $conexion->real_escape_string($_POST['fecha_lanzamiento'] ?? '');

        if (!$id_modelo) {
            echo json_encode(['success' => false, 'message' => 'ID de modelo no proporcionado.']);
            exit;
        }

        if (empty($nombre) || $id_marca === 0) {
            echo json_encode(['success' => false, 'message' => 'El nombre y la marca son obligatorios.']);
            exit;
        }

        // SQL de actualización
        $sql = "UPDATE Modelo 
                SET nombre = '$nombre', 
                    id_marca = $id_marca, 
                    fecha_lanzamiento = " . ($fecha ? "'$fecha'" : "NULL") . " 
                WHERE id_modelo = $id_modelo";
        
        if ($conexion->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Modelo actualizado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $conexion->error]);
        }
    }
    exit;
}
?>