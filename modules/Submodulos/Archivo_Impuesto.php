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
    // Usamos el nombre de tabla que definimos: Config_Impuestos
    $sql = "SELECT id_impuesto, nombre_impuesto, porcentaje, estado, fecha_creacion 
            FROM Impuestos 
            WHERE estado != 'eliminado' 
            ORDER BY id_impuesto DESC";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function guardar($conexion) {
    $id = $_POST['id_config_impuesto'] ?? '';
    $nombre = $_POST['nombre_impuesto'] ?? '';
    $porcentaje = $_POST['porcentaje_impuesto'] ?? 0;
    $estado = $_POST['estado_impuesto'] ?? 'activo';
    
    // Tomamos el usuario de la sesión, igual que en tu ejemplo de Maquinaria
    $usuario = $_SESSION['id_usuario'] ?? 1; 
    $fecha = date('Y-m-d H:i:s');

    try {
        if ($id == '') {
            // INSERT: Agregamos fecha y usuario
            $sql = "INSERT INTO Impuestos (nombre_impuesto, porcentaje, estado, fecha_creacion, usuario_creacion) VALUES (?, ?, ?, NOW(), ?)";
            $stmt = $conexion->prepare($sql);
            // s = string, d = decimal/double, s = string, s = string, i = integer
            $stmt->bind_param("sdsi", $nombre, $porcentaje, $estado, $usuario);
        } else {
            // UPDATE: No solemos cambiar el usuario de creación ni la fecha original
            $sql = "UPDATE Impuestos SET nombre_impuesto=?, porcentaje=?, estado=? WHERE id_impuesto=?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sdsi", $nombre, $porcentaje, $estado, $id);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Impuesto guardado correctamente']);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtener($conexion) {
    $id = (int)$_GET['id_impuesto'];
    $res = $conexion->query("SELECT * FROM Impuestos WHERE id_impuesto = $id");
    echo json_encode(['success' => true, 'data' => $res->fetch_assoc()]);
}

function eliminar($conexion) {
    $id = (int)$_POST['id_config_impuesto'];
    // Borrado lógico
    $sql = "UPDATE Impuestos SET estado = 'eliminado' WHERE id_impuesto = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Impuesto eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
    }
}
?>