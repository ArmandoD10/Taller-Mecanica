<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar_almacenes':
        listar_almacenes($conexion);
        break;
    case 'cargar_sucursales':
        cargar_sucursales($conexion);
        break;
    case 'guardar_almacen':
        guardar_almacen($conexion);
        break;
    case 'obtener_almacen':
        obtener_almacen($conexion);
        break;
    case 'eliminar_almacen':
        eliminar_almacen($conexion);
        break;
    case 'listar_gondolas':
        listar_gondolas($conexion);
        break;
    case 'guardar_gondola':
        guardar_gondola($conexion);
        break;
    case 'eliminar_gondola':
        eliminar_gondola($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

// ==========================================
// FUNCIONES DE ALMACÉN
// ==========================================
function listar_almacenes($conexion) {
    $sql = "SELECT a.id_almacen, a.nombre, a.estado, s.nombre AS sucursal,
                   (SELECT COUNT(*) FROM gondola g WHERE g.id_almacen = a.id_almacen AND g.estado != 'eliminado') as total_gondolas
            FROM almacen a
            JOIN sucursal s ON a.id_sucursal = s.id_sucursal
            WHERE a.estado != 'eliminado'
            ORDER BY a.id_almacen DESC";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function cargar_sucursales($conexion) {
    $sql = "SELECT id_sucursal, nombre FROM sucursal WHERE estado = 'activo'";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function guardar_almacen($conexion) {
    $id = $_POST['id_almacen'] ?? '';
    $id_sucursal = $_POST['id_sucursal'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $estado = $_POST['estado'] ?? 'activo';
    $usuario = $_SESSION['id_usuario'] ?? 1;
    $fecha_actual = date('Y-m-d H:i:s'); 

    // --- VALIDACIÓN ANTIDUPLICADOS ---
    // Verificamos si ya existe un almacén con el mismo nombre en la MISMA sucursal (ignora el registro actual si estamos editando)
    $id_check = $id ?: 0;
    $sql_val = "SELECT id_almacen FROM almacen WHERE nombre = ? AND id_sucursal = ? AND estado != 'eliminado' AND id_almacen != ?";
    $stmt_val = $conexion->prepare($sql_val);
    $stmt_val->bind_param("sii", $nombre, $id_sucursal, $id_check);
    $stmt_val->execute();
    if ($stmt_val->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Error: Ya existe un almacén con ese nombre en esta sucursal.']);
        return;
    }

    try {
        if ($id == '') {
            $sql = "INSERT INTO almacen (id_sucursal, nombre, estado, fecha_creacion, usuario_creacion) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("isssi", $id_sucursal, $nombre, $estado, $fecha_actual, $usuario);
        } else {
            $sql = "UPDATE almacen SET id_sucursal = ?, nombre = ?, estado = ? WHERE id_almacen = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("issi", $id_sucursal, $nombre, $estado, $id);
        }
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Almacén guardado correctamente.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function obtener_almacen($conexion) {
    $id = (int)$_GET['id_almacen'];
    $res = $conexion->query("SELECT * FROM almacen WHERE id_almacen = $id");
    echo json_encode(['success' => true, 'data' => $res->fetch_assoc()]);
}

function eliminar_almacen($conexion) {
    $id = (int)$_POST['id_almacen'];
    $conexion->query("UPDATE almacen SET estado = 'eliminado' WHERE id_almacen = $id");
    $conexion->query("UPDATE gondola SET estado = 'eliminado' WHERE id_almacen = $id");
    echo json_encode(['success' => true, 'message' => 'Almacén y sus góndolas eliminados.']);
}

// ==========================================
// FUNCIONES DE GÓNDOLA
// ==========================================
function listar_gondolas($conexion) {
    $id_almacen = (int)$_GET['id_almacen'];
    $sql = "SELECT * FROM gondola WHERE id_almacen = $id_almacen AND estado != 'eliminado' ORDER BY numero ASC";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function guardar_gondola($conexion) {
    $id_almacen = $_POST['id_almacen'] ?? '';
    $numero = $_POST['numero'] ?? '';
    $niveles = $_POST['niveles'] ?? 1; // Recibimos los niveles
    $estado = $_POST['estado'] ?? 'activo';

    // --- VALIDACIÓN ANTIDUPLICADOS ---
    // Verificamos que el número de góndola no exista en este mismo almacén
    $val = $conexion->query("SELECT id_gondola FROM gondola WHERE id_almacen = $id_almacen AND numero = $numero AND estado != 'eliminado'");
    if($val->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Error: El número de góndola ya existe en este almacén.']);
        return;
    }

    try {
        $sql = "INSERT INTO gondola (id_almacen, numero, niveles, estado) VALUES (?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iiis", $id_almacen, $numero, $niveles, $estado);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Góndola agregada correctamente.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function eliminar_gondola($conexion) {
    $id = (int)$_POST['id_gondola'];
    $conexion->query("UPDATE gondola SET estado = 'eliminado' WHERE id_gondola = $id");
    echo json_encode(['success' => true, 'message' => 'Góndola eliminada.']);
}
?>