<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

// Validación de la sucursal del empleado logueado
$sqlSucursal = "SELECT es.id_sucursal 
                FROM Empleado_Sucursal es
                INNER JOIN Empleado_Usuario eu ON es.id_empleado = eu.id_empleado
                WHERE eu.id_usuario = ? 
                AND es.estado = 'activo' 
                AND es.fecha_fin IS NULL 
                LIMIT 1";

$stmt = $conexion->prepare($sqlSucursal);
$stmt->bind_param("i", $_SESSION['id_usuario']); // Validación de sesión activa
$stmt->execute();
$id_sucursal_actual = $stmt->get_result()->fetch_assoc()['id_sucursal'] ?? 0;

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

    case 'obtener': 
        obtener_articulo_con_stock($conexion); 
        break;

    default: echo json_encode(['success' => false, 'message' => 'Acción no válida']); break;
}

function listar_articulos($conexion) {
    $id_usuario = $_SESSION['id_usuario'] ?? 0;

    // 1. Obtener el NOMBRE de la sucursal (para mostrar en el encabezado)
    $sqlSuc = "SELECT s.nombre 
               FROM Sucursal s
               INNER JOIN Empleado_Sucursal es ON s.id_sucursal = es.id_sucursal
               INNER JOIN Empleado_Usuario eu ON es.id_empleado = eu.id_empleado
               WHERE eu.id_usuario = ? AND es.estado = 'activo' AND es.fecha_fin IS NULL LIMIT 1";
    
    $stmtS = $conexion->prepare($sqlSuc);
    $stmtS->bind_param("i", $id_usuario);
    $stmtS->execute();
    $resSuc = $stmtS->get_result()->fetch_assoc();
    $nombre_sucursal = $resSuc['nombre'] ?? 'Sin sucursal asignada';

    // 2. Obtener los artículos
    $sqlArt = "SELECT a.*, p.nombre_comercial AS nombre_proveedor 
               FROM Repuesto_Articulo a
               LEFT JOIN Proveedor p ON a.id_proveedor = p.id_proveedor
               WHERE a.estado != 'eliminado' ORDER BY a.id_articulo DESC";
    $resArt = $conexion->query($sqlArt);
    $articulos = $resArt->fetch_all(MYSQLI_ASSOC);

    // 3. ENVIAR TODO EN EL JSON
    echo json_encode([
        'success' => true, 
        'data' => $articulos,
        'sucursal_nombre' => $nombre_sucursal // Este es el dato que le falta al JS
    ]);
}

function obtener_articulo($conexion) {
    if (!isset($_SESSION['id_usuario'])) {
        echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
        return;
    }

    $id_articulo = (int)$_GET['id'];
    $id_usuario = $_SESSION['id_usuario'];

    // 1. Obtener datos básicos del artículo
    $resArt = $conexion->query("SELECT * FROM Repuesto_Articulo WHERE id_articulo = $id_articulo");
    $articulo = $resArt->fetch_assoc();

    if (!$articulo) {
        echo json_encode(['success' => false, 'message' => 'Artículo no encontrado']);
        return;
    }

    // 2. NUEVO: Obtener el desglose de stock por sucursal para la lista detallada
    $sqlLista = "SELECT 
                    s.nombre as sucursal, 
                    SUM(i.cantidad) as cantidad,
                    ra.imagen,
                    ra.nombre as producto
                 FROM Inventario i
                 INNER JOIN Gondola g ON i.id_gondola = g.id_gondola
                 INNER JOIN Almacen a ON g.id_almacen = a.id_almacen
                 INNER JOIN Sucursal s ON a.id_sucursal = s.id_sucursal
                 INNER JOIN Repuesto_Articulo ra ON i.id_articulo = ra.id_articulo
                 WHERE i.id_articulo = ? AND i.cantidad > 0 AND i.estado = 'activo'
                 GROUP BY s.id_sucursal";
    
    $stmtL = $conexion->prepare($sqlLista);
    $stmtL->bind_param("i", $id_articulo);
    $stmtL->execute();
    $stockLista = $stmtL->get_result()->fetch_all(MYSQLI_ASSOC);

    // 3. Obtener nombre y stock de la sucursal del empleado logueado
    $sqlSucEmp = "SELECT s.id_sucursal, s.nombre 
                  FROM Sucursal s
                  INNER JOIN Empleado_Sucursal es ON s.id_sucursal = es.id_sucursal
                  INNER JOIN Empleado_Usuario eu ON es.id_empleado = eu.id_empleado
                  WHERE eu.id_usuario = ? AND es.estado = 'activo' AND es.fecha_fin IS NULL LIMIT 1";
    
    $stmtS = $conexion->prepare($sqlSucEmp);
    $stmtS->bind_param("i", $id_usuario);
    $stmtS->execute();
    $resS = $stmtS->get_result()->fetch_assoc();
    $id_sucursal_emp = $resS['id_sucursal'] ?? 0;
    $nombre_sucursal_emp = $resS['nombre'] ?? 'Mi Sucursal';

    $stockSucursal = 0;
    $stockGeneral = 0;

    // Calculamos los totales a partir de la lista para ser más eficientes
    foreach ($stockLista as $fila) {
        $stockGeneral += $fila['cantidad'];
        if ($fila['sucursal'] === $nombre_sucursal_emp) {
            $stockSucursal = $fila['cantidad'];
        }
    }

    // 4. Respuesta final
    echo json_encode([
        'success' => true, 
        'data' => $articulo,
        'stock_lista' => $stockLista, // Esta es la lista que usará tu modal de ubicación
        'stock' => [
            'general' => (int)$stockGeneral,
            'sucursal' => (int)$stockSucursal,
            'nombre_sucursal' => $nombre_sucursal_emp
        ]
    ]);
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

// Nueva función o reemplazo de la anterior
function obtener_articulo_con_stock($conexion) {
    $id_articulo = (int)$_GET['id'];
    $id_usuario = $_SESSION['id_usuario'] ?? 0;

    // 1. Obtener datos básicos del artículo
    $resArt = $conexion->query("SELECT * FROM Repuesto_Articulo WHERE id_articulo = $id_articulo");
    $articulo = $resArt->fetch_assoc();

    if (!$articulo) {
        echo json_encode(['success' => false, 'message' => 'Artículo no encontrado']);
        return;
    }

    // 2. Obtener Stock General (Suma de todas las góndolas/almacenes)
    $resGeneral = $conexion->query("SELECT SUM(cantidad) as total FROM Inventario WHERE id_articulo = $id_articulo AND estado = 'activo'");
    $stockGeneral = $resGeneral->fetch_assoc()['total'] ?? 0;

    // 3. Obtener Sucursal del Empleado Logueado (Usando tu lógica de Empleado_Usuario)
    $sqlSuc = "SELECT es.id_sucursal 
               FROM Empleado_Sucursal es
               INNER JOIN Empleado_Usuario eu ON es.id_empleado = eu.id_empleado
               WHERE eu.id_usuario = ? AND es.estado = 'activo' AND es.fecha_fin IS NULL LIMIT 1";
    $stmtSuc = $conexion->prepare($sqlSuc);
    $stmtSuc->bind_param("i", $id_usuario);
    $stmtSuc->execute();
    $id_sucursal = $stmtSuc->get_result()->fetch_assoc()['id_sucursal'] ?? 0;

    // 4. Obtener Stock en la Sucursal específica
    $stockSucursal = 0;
    if ($id_sucursal > 0) {
        $sqlStockSuc = "SELECT SUM(i.cantidad) as total 
                        FROM Inventario i
                        INNER JOIN Gondola g ON i.id_gondola = g.id_gondola
                        INNER JOIN Almacen a ON g.id_almacen = a.id_almacen
                        WHERE i.id_articulo = ? AND a.id_sucursal = ? AND i.estado = 'activo'";
        $stmtStk = $conexion->prepare($sqlStockSuc);
        $stmtStk->bind_param("ii", $id_articulo, $id_sucursal);
        $stmtStk->execute();
        $stockSucursal = $stmtStk->get_result()->fetch_assoc()['total'] ?? 0;
    }

    echo json_encode([
        'success' => true, 
        'data' => $articulo,
        'stock' => [
            'general' => $stockGeneral,
            'sucursal' => $stockSucursal
        ]
    ]);
}