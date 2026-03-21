<?php
// Incluye la conexión a la base de datos una sola vez al inicio del script
include("../../controller/conexion.php");

// Establece el encabezado para indicar que la respuesta es JSON
header('Content-Type: application/json');

// Obtiene la acción solicitada desde el parámetro 'action' en la URL
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'cargar':
        cargar($conexion);
        break;

    case 'guardar':
        guardar($conexion);
        break;

    case 'modulos':
        modulos($conexion);
        break;

    case 'obtener_permisos':
        obtenerPermisos($conexion);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
        break;
}

/**
 * Función para cargar los movimientos desde la base de datos.
 * Incluye la lógica de paginación.
 */
function cargar($conexion) {

    // Parámetros de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
$offset = ($page - 1) * $limit;

// 1. Contar el total de registros de la tabla 'sala'
$count_sql = "SELECT COUNT(*) as total FROM nivel WHERE estado='activo'";
$count_result = $conexion->query($count_sql);
$total_rows = $count_result->fetch_assoc()['total'];

// 2. Obtener los registros de la página actual de la tabla 'sala'
$niveles  = [];
$sql = "SELECT id_nivel, nombre from nivel WHERE estado = 'activo' LIMIT $limit OFFSET $offset";

$resultado = $conexion->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $niveles [] = $fila;
    }
}

// Cierra la conexión a la base de datos


// Devuelve los datos paginados y el total de registros
echo json_encode([
    'data' => $niveles ,
    'total_records' => (int)$total_rows,
    'page' => $page,
    'limit' => $limit
]);
}

/**
 * Función para guardar un nuevo movimiento en la base de datos.
 */
function guardar($conexion) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $id = $_POST['id_nivel'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $modulos = $_POST['modulos'] ?? [];

        if (empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'Nombre vacío']);
            exit;
        }

        // 🔥 1. INSERTAR O ACTUALIZAR NIVEL
        if (empty($id)) {

            $sql = "INSERT INTO nivel (nombre, estado) VALUES (?, 'Activo')";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("s", $nombre);
            $stmt->execute();

            $id = $conexion->insert_id;

        } else {

            $sql = "UPDATE nivel SET nombre=? WHERE id_nivel=?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("si", $nombre, $id);
            $stmt->execute();
        }

        // 🔥 2. OBTENER TODOS LOS MÓDULOS
        $sqlMod = "SELECT id_modulo FROM modulo";
        $resMod = $conexion->query($sqlMod);

        while ($mod = $resMod->fetch_assoc()) {

            $id_modulo = $mod['id_modulo'];

            // ✔ Determinar estado
            $estado = in_array($id_modulo, $modulos) ? 'Activo' : 'Inactivo';

            // 🔥 INSERT O UPDATE
            $sqlPerm = "INSERT INTO permiso_nivel (id_nivel, id_modulo, estado)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE estado=?";

            $stmt = $conexion->prepare($sqlPerm);
            $stmt->bind_param("iiss", $id, $id_modulo, $estado, $estado);
            $stmt->execute();
        }

        echo json_encode(['success' => true]);
        exit;
    }
}

/**
 * Función para actualizar un movimiento existente.
 */
function modulos($conexion){

    $sql = "SELECT id_modulo, nombre FROM modulo";
    $res = $conexion->query($sql);

    $data = [];

    while($fila = $res->fetch_assoc()){
        $data[] = $fila;
    }

    echo json_encode($data);
}

/**
 * Función para desactivar (eliminar lógicamente) un movimiento.
 */
function obtenerPermisos($conexion){

    $id = $_GET['id_nivel'];

    $sql = "SELECT id_modulo, estado 
            FROM permiso_nivel 
            WHERE id_nivel=?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $res = $stmt->get_result();

    $data = [];

    while($fila = $res->fetch_assoc()){
        $data[] = $fila;
    }

    echo json_encode($data);
}