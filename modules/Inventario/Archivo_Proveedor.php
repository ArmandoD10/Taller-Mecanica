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
    $sql = "SELECT p.id_proveedor, p.nombre_comercial, p.RNC, p.correo, p.estado, 
                   CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS representante,
                   per.tipo_persona
            FROM proveedor p
            JOIN persona per ON p.representante = per.id_persona
            WHERE p.estado != 'eliminado'
            ORDER BY p.id_proveedor DESC";
            
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function cargar_dependencias($conexion) {
    $data = [];
    $data['paises'] = $conexion->query("SELECT id_pais, nombre FROM pais WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    
    // Traemos el id_pais para filtrar las provincias con Javascript
    $data['provincias'] = $conexion->query("SELECT id_provincia, nombre, id_pais FROM provincia WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    
    // Traemos el id_provincia para filtrar las ciudades
    $data['ciudades'] = $conexion->query("SELECT id_ciudad, nombre, id_provincia FROM ciudad WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
}

function guardar($conexion) {
    $id_proveedor = $_POST['id_proveedor'] ?? '';
    $id_persona = $_POST['id_persona'] ?? '';
    $id_direccion = $_POST['id_direccion'] ?? '';
    
    $tipo_persona = $_POST['tipo_persona'] ?? 'Fisica';
    $nombre = $_POST['nombre'] ?? '';
    $apellido_p = $_POST['apellido_p'] ?? '';
    $cedula = $_POST['cedula'] ?? '';
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? date('Y-m-d');
    $nacionalidad = $_POST['nacionalidad'] ?? ''; 
    
    $nombre_comercial = trim($_POST['nombre_comercial'] ?? '');
    $rnc = $_POST['RNC'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $estado = $_POST['estado'] ?? 'activo';
    
    if ($tipo_persona === 'Fisica') {
        if (empty($nombre_comercial)) {
            $nombre_comercial = trim($nombre . ' ' . $apellido_p);
        }
        if (!empty($rnc)) {
            $cedula = $rnc;
        }
    }
    
    // Guardamos la ciudad final (el país y la provincia ya están implícitos en la ciudad)
    $id_ciudad = $_POST['id_ciudad'] ?? '';
    $descripcion_dir = $_POST['descripcion_dir'] ?? '';
    
    $usuario = $_SESSION['id_usuario'] ?? 1;

    try {
        $conexion->begin_transaction();

        if ($id_proveedor == '') {
            $sqlDir = "INSERT INTO direccion (id_ciudad, Descripcion, estado) VALUES (?, ?, 'activo')";
            $stmtDir = $conexion->prepare($sqlDir);
            $stmtDir->bind_param("is", $id_ciudad, $descripcion_dir);
            $stmtDir->execute();
            $new_id_direccion = $conexion->insert_id;

            $sqlPer = "INSERT INTO persona (tipo_persona, nombre, apellido_p, cedula, fecha_nacimiento, id_direccion, nacionalidad, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'activo')";
            $stmtPer = $conexion->prepare($sqlPer);
            $stmtPer->bind_param("sssssii", $tipo_persona, $nombre, $apellido_p, $cedula, $fecha_nacimiento, $new_id_direccion, $nacionalidad);
            $stmtPer->execute();
            $new_id_persona = $conexion->insert_id;

            $sqlProv = "INSERT INTO proveedor (nombre_comercial, representante, correo, RNC, id_direccion, estado, usuario_creacion) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtProv = $conexion->prepare($sqlProv);
            $stmtProv->bind_param("sissisi", $nombre_comercial, $new_id_persona, $correo, $rnc, $new_id_direccion, $estado, $usuario);
            $stmtProv->execute();

        } else {
            $sqlDir = "UPDATE direccion SET id_ciudad = ?, Descripcion = ? WHERE id_direccion = ?";
            $stmtDir = $conexion->prepare($sqlDir);
            $stmtDir->bind_param("isi", $id_ciudad, $descripcion_dir, $id_direccion);
            $stmtDir->execute();

            $sqlPer = "UPDATE persona SET tipo_persona = ?, nombre = ?, apellido_p = ?, cedula = ?, fecha_nacimiento = ?, nacionalidad = ? WHERE id_persona = ?";
            $stmtPer = $conexion->prepare($sqlPer);
            $stmtPer->bind_param("sssssii", $tipo_persona, $nombre, $apellido_p, $cedula, $fecha_nacimiento, $nacionalidad, $id_persona);
            $stmtPer->execute();

            $sqlProv = "UPDATE proveedor SET nombre_comercial = ?, correo = ?, RNC = ?, estado = ? WHERE id_proveedor = ?";
            $stmtProv = $conexion->prepare($sqlProv);
            $stmtProv->bind_param("ssssi", $nombre_comercial, $correo, $rnc, $estado, $id_proveedor);
            $stmtProv->execute();
        }

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Proveedor guardado correctamente.']);
    } catch (Exception $e) {
        $conexion->rollback(); 
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function obtener($conexion) {
    $id = (int)$_GET['id_proveedor'];
    
    // Hacemos JOIN hasta país para poder llenar la "cascada" en el frontend al editar
    $sql = "SELECT p.*, 
                   per.id_persona, per.tipo_persona, per.nombre, per.apellido_p, per.cedula, per.fecha_nacimiento, per.nacionalidad,
                   dir.id_direccion, dir.id_ciudad, dir.Descripcion as descripcion_dir,
                   c.id_provincia, prov.id_pais as id_pais_dir
            FROM proveedor p
            JOIN persona per ON p.representante = per.id_persona
            JOIN direccion dir ON p.id_direccion = dir.id_direccion
            LEFT JOIN ciudad c ON dir.id_ciudad = c.id_ciudad
            LEFT JOIN provincia prov ON c.id_provincia = prov.id_provincia
            WHERE p.id_proveedor = ?";
            
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function eliminar($conexion) {
    $id = (int)$_POST['id_proveedor'];
    $conexion->query("UPDATE proveedor SET estado = 'eliminado' WHERE id_proveedor = $id");
    echo json_encode(['success' => true, 'message' => 'Proveedor eliminado.']);
}
?>