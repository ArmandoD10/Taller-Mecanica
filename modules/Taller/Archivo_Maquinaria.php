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
    $sql = "SELECT m.id_maquinaria, m.nombre, m.funcionamiento, m.estado_maquina, m.estado, 
                   DATE_FORMAT(m.fecha_ingreso, '%d/%m/%Y') AS fecha_ingreso,
                   s.nombre AS sucursal, c.nombre AS categoria
            FROM maquinaria m
            JOIN sucursal s ON m.id_sucursal = s.id_sucursal
            LEFT JOIN categoria_maquinaria c ON m.id_categoria = c.id_categoria
            WHERE m.estado != 'eliminado'
            ORDER BY m.id_maquinaria DESC";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function cargar_dependencias($conexion) {
    $data = [];
    $data['sucursales'] = $conexion->query("SELECT id_sucursal, nombre FROM sucursal WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    $data['categorias'] = $conexion->query("SELECT id_categoria, nombre FROM categoria_maquinaria WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
}

function guardar($conexion) {
    $id = $_POST['id_maquinaria'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $id_sucursal = $_POST['id_sucursal'] ?? '';
    $id_categoria = $_POST['id_categoria'] ?? '';
    $funcionamiento = $_POST['funcionamiento'] ?? '';
    $estado_maquina = $_POST['estado_maquina'] ?? 'Nuevo';
    $fecha_ingreso = $_POST['fecha_ingreso'] ?? date('Y-m-d');
    $estado = $_POST['estado'] ?? 'activo';
    $usuario = $_SESSION['id_usuario'] ?? 1;

    // Ajustar la fecha para que MySQL la acepte como timestamp completo
    if (strlen($fecha_ingreso) == 10) { $fecha_ingreso .= " 00:00:00"; }

    try {
        if ($id == '') {
            $sql = "INSERT INTO maquinaria (nombre, id_sucursal, id_categoria, funcionamiento, estado_maquina, fecha_ingreso, estado, usuario_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("siissssi", $nombre, $id_sucursal, $id_categoria, $funcionamiento, $estado_maquina, $fecha_ingreso, $estado, $usuario);
        } else {
            // Pasamos fecha_ingreso para evitar que el ON UPDATE CURRENT_TIMESTAMP la modifique al actualizar
            $sql = "UPDATE maquinaria SET nombre=?, id_sucursal=?, id_categoria=?, funcionamiento=?, estado_maquina=?, fecha_ingreso=?, estado=? WHERE id_maquinaria=?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("siissssi", $nombre, $id_sucursal, $id_categoria, $funcionamiento, $estado_maquina, $fecha_ingreso, $estado, $id);
        }
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Recurso guardado correctamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtener($conexion) {
    $id = (int)$_GET['id_maquinaria'];
    $res = $conexion->query("SELECT * FROM maquinaria WHERE id_maquinaria = $id");
    $data = $res->fetch_assoc();
    
    // Formatear la fecha para que el input type="date" de HTML la pueda leer (YYYY-MM-DD)
    if(isset($data['fecha_ingreso'])) {
        $data['fecha_ingreso_formato'] = date('Y-m-d', strtotime($data['fecha_ingreso']));
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function eliminar($conexion) {
    $id = (int)$_POST['id_maquinaria'];
    $conexion->query("UPDATE maquinaria SET estado = 'eliminado' WHERE id_maquinaria = $id");
    echo json_encode(['success' => true, 'message' => 'Recurso eliminado']);
}
?>