<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar': listar_articulos($conexion); break;
    case 'obtener': obtener_articulo($conexion); break;
    case 'guardar': guardar_articulo($conexion); break;
    case 'cargar_marcas':
        $res = $conexion->query("SELECT id_marca_producto, nombre FROM Marca_Producto WHERE estado = 'activo'");
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        break;
    case 'cargar_proveedores':
        // Usamos nombre_comercial para la búsqueda visual
        $res = $conexion->query("SELECT id_proveedor, nombre_comercial FROM Proveedor WHERE estado = 'activo'");
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        break;

    default: echo json_encode(['success' => false, 'message' => 'Acción no válida']); break;
}

function listar_articulos($conexion) {
    $sql = "SELECT a.*, p.nombre_comercial AS nombre_proveedor 
            FROM Repuesto_Articulo a
            LEFT JOIN Proveedor p ON a.id_proveedor = p.id_proveedor
            WHERE a.estado != 'eliminado' ORDER BY a.id_articulo DESC";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function obtener_articulo($conexion) {
    $id = (int)$_GET['id'];
    $res = $conexion->query("SELECT * FROM Repuesto_Articulo WHERE id_articulo = $id");
    echo json_encode(['success' => true, 'data' => $res->fetch_assoc()]);
}

function guardar_articulo($conexion) {
    $id = $_POST['id_articulo'] ?? '';
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $descripcion = $conexion->real_escape_string($_POST['descripcion']);
    $serie = $conexion->real_escape_string($_POST['num_serie']);
    $costo = $_POST['precio_compra'] ?: 0;
    $venta = $_POST['precio_venta'] ?: 0;
    $id_prov = $_POST['id_proveedor'];
    $id_marca = $_POST['id_marca_producto'];
    $estado = $_POST['estado'] ?? 'activo';
    $estado_articulo = $_POST['estado_articulo'] ?? 'nuevo'; // NUEVO CAMPO
    $usuario = $_SESSION['id_usuario'] ?? 1;
    $f_cad = !empty($_POST['fecha_caducidad']) ? $_POST['fecha_caducidad'] : null;

    $conexion->begin_transaction();
    try {
        if (empty($id)) {
            $sql = "INSERT INTO Repuesto_Articulo (nombre, descripcion, num_serie, precio_compra, precio_venta, id_proveedor, id_marca_producto, estado, estado_articulo, fecha_caducidad, usuario_creacion, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssddiisssi", $nombre, $descripcion, $serie, $costo, $venta, $id_prov, $id_marca, $estado, $estado_articulo, $f_cad, $usuario);
            $stmt->execute();
            $id_final = $conexion->insert_id;
        } else {
            $sql = "UPDATE Repuesto_Articulo SET nombre=?, descripcion=?, num_serie=?, precio_compra=?, precio_venta=?, id_proveedor=?, id_marca_producto=?, estado=?, estado_articulo=?, fecha_caducidad=? WHERE id_articulo=?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssddiisssi", $nombre, $descripcion, $serie, $costo, $venta, $id_prov, $id_marca, $estado, $estado_articulo, $f_cad, $id);
            $stmt->execute();
            $id_final = $id;
        }

        if (isset($_FILES['imagen_file']) && $_FILES['imagen_file']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['imagen_file']['name'], PATHINFO_EXTENSION);
            $nuevo_n = "repuesto_" . $id_final . "." . $ext;
            $dir = $_SERVER['DOCUMENT_ROOT'] . "/Taller/Taller-Mecanica/img/";
            if (move_uploaded_file($_FILES['imagen_file']['tmp_name'], $dir . $nuevo_n)) {
                $ruta_db = "/Taller/Taller-Mecanica/img/" . $nuevo_n;
                $conexion->query("UPDATE Repuesto_Articulo SET imagen = '$ruta_db' WHERE id_articulo = $id_final");
            }
        }
        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Guardado con éxito']);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}