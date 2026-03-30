<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
session_start();

switch ($action) {
    case 'cargar_selects':
        cargar_selects($conexion);
        break;

    case 'cargar':
        cargar($conexion);
        break;

    case 'guardar':
        guardar($conexion);
        break;

    case 'actualizar':
        actualizar($conexion);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

// --- FUNCIONES DE CARGA ---

function cargar($conexion) {
    if (ob_get_length()) ob_clean();

    // Hacemos un JOIN con Departamento para mostrar el nombre en la tabla
    $sql = "SELECT 
                p.id_puesto, 
                p.nombre, 
                d.nombre AS departamento, 
                p.id_departamento,
                p.fecha_creacion, 
                p.estado 
            FROM Puesto p
            INNER JOIN Departamento d ON p.id_departamento = d.id_departamento
            WHERE p.estado != 'eliminado'
            ORDER BY p.id_puesto DESC";

    $resultado = $conexion->query($sql);
    $data = [];
    while ($fila = $resultado->fetch_assoc()) {
        $data[] = $fila;
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function cargar_selects($conexion) {
    if (ob_get_length()) ob_clean();
    
    $data = [];
    // Cargamos solo departamentos activos para el select
    $res = $conexion->query("SELECT id_departamento, nombre FROM Departamento WHERE estado = 'activo' ORDER BY nombre ASC");
    $data['departamentos'] = $res->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// --- OPERACIONES CRUD ---

function guardar($conexion) {
    try {
        // Validación de sesión para auditoría (id_usuario que inserta)
        $id_usuario = $_SESSION['id_usuario'] ?? null;
        
        $nombre = $_POST['nombre'] ?? '';
        $id_departamento = $_POST['departamento'] ?? '';

        if (empty($nombre) || empty($id_departamento)) {
            throw new Exception("Complete todos los campos obligatorios.");
        }

        $conexion->begin_transaction();

        // En tu tabla no tienes columna 'usuario_creacion', pero si la agregas en el futuro, 
        // aquí usarías la variable $id_usuario de la sesión.
        $sql = "INSERT INTO Puesto (id_departamento, nombre, estado, fecha_creacion) 
                VALUES (?, ?, 'activo', NOW())";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("is", $id_departamento, $nombre);
        $stmt->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Puesto registrado con éxito']);

    } catch (Exception $e) {
        if ($conexion->inTransaction()) $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

function actualizar($conexion) {
    try {
        if (!isset($_POST['id_puesto'])) {
            throw new Exception("ID de puesto no proporcionado.");
        }

        $id_puesto = $_POST['id_puesto'];
        $nombre = $_POST['nombre'];
        $id_departamento = $_POST['departamento'];
        $estado = $_POST['estado'] ?? 'activo';

        $conexion->begin_transaction();

        $sql = "UPDATE Puesto SET nombre = ?, id_departamento = ?, estado = ? WHERE id_puesto = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sisi", $nombre, $id_departamento, $estado, $id_puesto);
        $stmt->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Puesto actualizado correctamente']);

    } catch (Exception $e) {
        if ($conexion->inTransaction()) $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}