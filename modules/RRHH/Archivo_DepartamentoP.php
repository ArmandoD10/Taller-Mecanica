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

    case 'reporte_pdf':
    $sql = "SELECT nombre, dias_lab, 
                   TIME_FORMAT(hora_ini, '%h:%i %p') as hora_entrada, 
                   TIME_FORMAT(hora_fin, '%h:%i %p') as hora_salida, 
                   estado 
            FROM Departamento 
            WHERE estado != 'eliminado'
            ORDER BY nombre ASC";
            
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
    break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

// --- FUNCIONES ---

function cargar($conexion) {
    if (ob_get_length()) ob_clean();

    $sql = "SELECT id_departamento, nombre, dias_lab, hora_ini, hora_fin, estado 
            FROM Departamento 
            WHERE estado != 'eliminado' 
            ORDER BY id_departamento DESC";

    $resultado = $conexion->query($sql);
    $data = [];
    while ($fila = $resultado->fetch_assoc()) {
        $data[] = $fila;
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function guardar($conexion) {
    try {
        $nombre = $_POST['nombre'] ?? '';
        $dias_lab = $_POST['cantidad_dias'] ?? 0;
        $hora_ini = $_POST['hora_entrada'] ?? '';
        $hora_fin = $_POST['hora_salida'] ?? '';

        if (empty($nombre) || empty($dias_lab) || empty($hora_ini) || empty($hora_fin)) {
            throw new Exception("Por favor complete todos los campos obligatorios.");
        }

        // Asegurar formato HH:MM:SS para la base de datos
        if (strlen($hora_ini) == 5) $hora_ini .= ":00";
        if (strlen($hora_fin) == 5) $hora_fin .= ":00";

        $conexion->begin_transaction();

        $stmt = $conexion->prepare("INSERT INTO Departamento (nombre, dias_lab, hora_ini, hora_fin, estado) VALUES (?, ?, ?, ?, 'activo')");
        $stmt->bind_param("siss", $nombre, $dias_lab, $hora_ini, $hora_fin);
        $stmt->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Departamento registrado correctamente']);

    } catch (Exception $e) {
        if ($conexion->inTransaction()) $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

function actualizar($conexion) {
    try {
        if (!isset($_POST['id_departamento'])) {
            throw new Exception("ID de departamento no proporcionado.");
        }

        $id_departamento = $_POST['id_departamento'];
        $nombre = $_POST['nombre'];
        $dias_lab = $_POST['cantidad_dias'];
        $hora_ini = $_POST['hora_entrada'];
        $hora_fin = $_POST['hora_salida'];
        $estado = $_POST['estado'] ?? 'activo';

        // Formateo de horas
        if (strlen($hora_ini) == 5) $hora_ini .= ":00";
        if (strlen($hora_fin) == 5) $hora_fin .= ":00";

        $conexion->begin_transaction();

        $stmt = $conexion->prepare("UPDATE Departamento SET nombre=?, dias_lab=?, hora_ini=?, hora_fin=?, estado=? WHERE id_departamento=?");
        $stmt->bind_param("sisssi", $nombre, $dias_lab, $hora_ini, $hora_fin, $estado, $id_departamento);
        $stmt->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Departamento actualizado correctamente']);

    } catch (Exception $e) {
        if ($conexion->inTransaction()) $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}