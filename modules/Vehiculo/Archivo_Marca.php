<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

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
    case 'get_paises':
        get_paises($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
        break;
}

function cargar($conexion) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
    $offset = ($page - 1) * $limit;

    $count_sql = "SELECT COUNT(*) as total FROM Marca";
    $count_result = $conexion->query($count_sql);
    $total_rows = $count_result ? $count_result->fetch_assoc()['total'] : 0;

    $marcas = [];
    
    // Hacemos JOIN con la tabla Pais para mostrar el nombre del país en la tabla
    $sql = "SELECT 
                m.id_marca, 
                m.nombre AS marca_nombre, 
                m.id_pais, 
                p.nombre AS pais_nombre, 
                m.correo, 
                m.estado 
            FROM Marca m
            INNER JOIN Pais p ON m.id_pais = p.id_pais
            ORDER BY m.id_marca DESC
            LIMIT $limit OFFSET $offset";

    $resultado = $conexion->query($sql);

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $marcas[] = $fila;
        }
    }

    echo json_encode([
        'data' => $marcas,
        'total_records' => (int)$total_rows,
        'page' => $page,
        'limit' => $limit
    ]);
}

function guardar($conexion) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = $conexion->real_escape_string(trim($_POST['nombre'] ?? ''));
        $id_pais = (int)($_POST['id_pais'] ?? 0);
        $correo = $conexion->real_escape_string(trim($_POST['correo'] ?? ''));

        if (empty($nombre) || $id_pais === 0) {
            echo json_encode(['success' => false, 'message' => 'El nombre de la marca y el país son obligatorios.']);
            exit;
        }

        // Validar que la marca no exista ya
        $check = "SELECT id_marca FROM Marca WHERE nombre = '$nombre'";
        $result = $conexion->query($check);
        if ($result && $result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Esta Marca ya está registrada.']);
            exit;
        }

        $sql = "INSERT INTO Marca (nombre, id_pais, correo, estado)
                VALUES ('$nombre', $id_pais, '$correo', 'activo')";

        if ($conexion->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Marca registrada con éxito.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $conexion->error]);
        }
    }
}

function actualizar($conexion) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $id_marca = (int)($_POST['id_marca'] ?? 0);
        $nombre = $conexion->real_escape_string(trim($_POST['nombre'] ?? ''));
        $id_pais = (int)($_POST['id_pais'] ?? 0);
        $correo = $conexion->real_escape_string(trim($_POST['correo'] ?? ''));

        if (!$id_marca) {
            echo json_encode(['success' => false, 'message' => 'ID no proporcionado.']);
            exit;
        }

        $sql = "UPDATE Marca 
                SET nombre='$nombre', id_pais=$id_pais, correo='$correo' 
                WHERE id_marca=$id_marca";
        
        if ($conexion->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Marca actualizada correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $conexion->error]);
        }
    }
}

function cambiar_estado($conexion) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_marca = (int)($_POST['id_marca'] ?? 0);
        $nuevo_estado = $conexion->real_escape_string($_POST['estado'] ?? 'inactivo');
        
        if ($id_marca > 0) {
            $sql = "UPDATE Marca SET estado = '$nuevo_estado' WHERE id_marca = $id_marca";
            if ($conexion->query($sql)) {
                $mensaje = $nuevo_estado == 'activo' ? 'Marca reactivada con éxito.' : 'Marca desactivada correctamente.';
                echo json_encode(['success' => true, 'message' => $mensaje]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al cambiar estado: ' . $conexion->error]);
            }
        }
    }
}

// Llenar el Select de Países
function get_paises($conexion) {
    $paises = [];
    $res = $conexion->query("SELECT id_pais, nombre FROM Pais WHERE estado = 'activo' ORDER BY nombre ASC");
    if($res) {
        while($row = $res->fetch_assoc()) {
            $paises[] = $row;
        }
    }
    echo json_encode(['paises' => $paises]);
}
?>