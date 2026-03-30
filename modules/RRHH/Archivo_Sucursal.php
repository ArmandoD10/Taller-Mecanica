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

    case 'cargar_provincias':
        cargar_provincias($conexion);
        break;

    case 'cargar_ciudades':
        cargar_ciudades($conexion);
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
    $sql = "SELECT 
                s.id_sucursal,
                s.nombre,
                t.numero AS telefono,
                d.descripcion AS direccion,
                c.id_ciudad,
                c.nombre AS ciudad,
                pr.id_provincia,
                pa.id_pais,
                s.estado
            FROM Sucursal s
            INNER JOIN Direccion d ON s.id_direccion = d.id_direccion
            INNER JOIN Ciudad c ON d.id_ciudad = c.id_ciudad
            INNER JOIN Provincia pr ON c.id_provincia = pr.id_provincia
            INNER JOIN Pais pa ON pr.id_pais = pa.id_pais
            INNER JOIN Telefono t ON s.id_telefono = t.id_telefono
            WHERE s.estado != 'eliminado'";

    $resultado = $conexion->query($sql);
    $data = [];
    while ($fila = $resultado->fetch_assoc()) {
        $data[] = $fila;
    }

    echo json_encode(['success' => true, 'data' => $data]);
}

function cargar_selects($conexion) {
    $data = [];
    $res = $conexion->query("SELECT id_pais, nombre FROM Pais ORDER BY nombre ASC");
    $data['pais'] = $res->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
}

function cargar_provincias($conexion) {
    $id_pais = $_GET['id_pais'];
    $stmt = $conexion->prepare("SELECT id_provincia, nombre FROM Provincia WHERE id_pais=?");
    $stmt->bind_param("i", $id_pais);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

function cargar_ciudades($conexion) {
    $id_provincia = $_GET['id_provincia'];
    $stmt = $conexion->prepare("SELECT id_ciudad, nombre FROM Ciudad WHERE id_provincia=?");
    $stmt->bind_param("i", $id_provincia);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

// --- OPERACIONES CRUD ---

function guardar($conexion) {
    // Nota: id_organizacion es NOT NULL en tu tabla. 
    // Asumiré que la obtienes de la sesión o un valor fijo por ahora.
    $id_organizacion = $_SESSION['id_organizacion'] ?? 1; 
    
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $id_ciudad = $_POST['ciudad'];
    $direccion = $_POST['direccion'];

    if (empty($nombre) || empty($telefono) || empty($id_ciudad)) {
        echo json_encode(['success' => false, 'message' => 'Complete los campos obligatorios']);
        return;
    }

    try {
        $conexion->begin_transaction();

        // 1. Insertar Dirección
        $sqlDir = "INSERT INTO Direccion (id_ciudad, Descripcion, estado) VALUES (?, ?, 'activo')";
        $stmtDir = $conexion->prepare($sqlDir);
        $stmtDir->bind_param("is", $id_ciudad, $direccion);
        $stmtDir->execute();
        $id_direccion = $conexion->insert_id;

        // 2. Insertar Teléfono
        $sqlTel = "INSERT INTO Telefono (numero, estado) VALUES (?, 'activo')";
        $stmtTel = $conexion->prepare($sqlTel);
        $stmtTel->bind_param("s", $telefono);
        $stmtTel->execute();
        $id_telefono = $conexion->insert_id;

        // 3. Insertar Sucursal
        $sqlSuc = "INSERT INTO Sucursal (id_organizacion, nombre, id_direccion, id_telefono, estado) 
                   VALUES (?, ?, ?, ?, 'activo')";
        $stmtSuc = $conexion->prepare($sqlSuc);
        $stmtSuc->bind_param("isii", $id_organizacion, $nombre, $id_direccion, $id_telefono);
        $stmtSuc->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Sucursal registrada con éxito']);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function actualizar($conexion) {
    try {
        // Validar que lleguen los datos mínimos
        if (!isset($_POST['id_sucursal'], $_POST['ciudad'], $_POST['nombre'])) {
            throw new Exception("Faltan datos obligatorios para la actualización.");
        }

        $conexion->begin_transaction();

        $id_sucursal = $_POST['id_sucursal'];
        $nombre = $_POST['nombre'];
        $telefono = $_POST['telefono'];
        $id_ciudad = $_POST['ciudad'];
        $direccion = $_POST['direccion'];
        $estado = $_POST['estado'] ?? 'activo';

        // Obtener IDs relacionados
        $stmtIds = $conexion->prepare("SELECT id_direccion, id_telefono FROM Sucursal WHERE id_sucursal = ?");
        $stmtIds->bind_param("i", $id_sucursal);
        $stmtIds->execute();
        $ids = $stmtIds->get_result()->fetch_assoc();

        if (!$ids) throw new Exception("No se encontró la sucursal.");

        // 1. Actualizar Dirección
        $stmtDir = $conexion->prepare("UPDATE Direccion SET id_ciudad=?, Descripcion=? WHERE id_direccion=?");
        $stmtDir->bind_param("isi", $id_ciudad, $direccion, $ids['id_direccion']);
        $stmtDir->execute();

        // 2. Actualizar Teléfono
        $stmtTel = $conexion->prepare("UPDATE Telefono SET numero=? WHERE id_telefono=?");
        $stmtTel->bind_param("si", $telefono, $ids['id_telefono']);
        $stmtTel->execute();

        // 3. Actualizar Sucursal
        $stmtSuc = $conexion->prepare("UPDATE Sucursal SET nombre=?, estado=? WHERE id_sucursal=?");
        $stmtSuc->bind_param("ssi", $nombre, $estado, $id_sucursal);
        $stmtSuc->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Actualizado correctamente']);

    } catch (Exception $e) {
        if ($conexion->inTransaction()) $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}