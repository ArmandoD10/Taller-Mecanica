<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'cargar':
        cargar($conexion);
        break;
    case 'departamentos':
        listarDepartamentos($conexion);
        break;
    case 'obtener_asignados':
        obtenerAsignados($conexion);
        break;
    case 'guardar':
        guardar($conexion);
        break;
}

function cargar($conexion) {
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 6);
    $offset = ($page - 1) * $limit;

    $total = $conexion->query("SELECT COUNT(*) as t FROM sucursal WHERE estado='activo'")->fetch_assoc()['t'];
    
    $res = $conexion->query("SELECT id_sucursal, nombre FROM sucursal WHERE estado='activo' LIMIT $limit OFFSET $offset");
    $data = [];
    while($f = $res->fetch_assoc()) $data[] = $f;

    echo json_encode(['data' => $data, 'total_records' => (int)$total, 'page' => $page, 'limit' => $limit]);
}

function listarDepartamentos($conexion) {
    $res = $conexion->query("SELECT id_departamento, nombre FROM departamento"); // Asumiendo que esta tabla existe
    $data = [];
    while($f = $res->fetch_assoc()) $data[] = $f;
    echo json_encode($data);
}

function obtenerAsignados($conexion) {
    $id = $_GET['id_sucursal'];
    $res = $conexion->query("SELECT id_departamento, estado FROM departamento_sucursal WHERE id_sucursal = $id");
    $data = [];
    while($f = $res->fetch_assoc()) $data[] = $f;
    echo json_encode($data);
}

function guardar($conexion) {
    $id = $_POST['id_sucursal'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $deptosSeleccionados = $_POST['deptos'] ?? []; // IDs de los deptos marcados

    if (empty($id)) {
        $stmt = $conexion->prepare("INSERT INTO sucursal (nombre, estado) VALUES (?, 'activo')");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $id = $conexion->insert_id;
    } else {
        $stmt = $conexion->prepare("UPDATE sucursal SET nombre=? WHERE id_sucursal=?");
        $stmt->bind_param("si", $nombre, $id);
        $stmt->execute();
    }

    // LOGICA DE CHECKBOXES: Recorremos todos los departamentos para actualizar la tabla intermedia
    $resTodosDeptos = $conexion->query("SELECT id_departamento FROM departamento");
    while ($row = $resTodosDeptos->fetch_assoc()) {
        $id_dep = $row['id_departamento'];
        $estado = in_array($id_dep, $deptosSeleccionados) ? 'activo' : 'inactivo';

        $stmtPerf = $conexion->prepare("INSERT INTO departamento_sucursal (id_sucursal, id_departamento, estado) 
                                        VALUES (?, ?, ?) 
                                        ON DUPLICATE KEY UPDATE estado=?");
        $stmtPerf->bind_param("iiss", $id, $id_dep, $estado, $estado);
        $stmtPerf->execute();
    }

    echo json_encode(['success' => true]);
}