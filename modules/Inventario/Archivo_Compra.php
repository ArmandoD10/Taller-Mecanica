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
    $sql = "SELECT c.id_compra, c.monto, c.cantidad_articulo, c.fecha_creacion, c.estado,
                   p.nombre_comercial, mo.codigo_ISO as moneda
            FROM compra c
            JOIN proveedor p ON c.id_proveedor = p.id_proveedor
            JOIN moneda mo ON c.id_moneda = mo.id_moneda
            WHERE c.estado != 'eliminado'
            ORDER BY c.id_compra DESC";
            
    $res = $conexion->query($sql);
    
    echo json_encode([
        'success' => true, 
        'data' => $res->fetch_all(MYSQLI_ASSOC)
    ]);
}

function cargar_dependencias($conexion) {
    $data = [];
    $data['proveedores'] = $conexion->query("SELECT id_proveedor, nombre_comercial, RNC FROM proveedor WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    $data['metodos'] = $conexion->query("SELECT id_metodo, nombre FROM metodo_pago WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    $data['monedas'] = $conexion->query("SELECT id_moneda, codigo_ISO, nombre FROM moneda WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    
    // Traemos los artículos con su precio de compra para autocompletar en el frontend
    $sql_art = "SELECT a.id_articulo, a.nombre, a.num_serie, a.precio_compra 
                FROM repuesto_articulo a 
                WHERE a.estado = 'activo'";
    $data['articulos'] = $conexion->query($sql_art)->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'data' => $data
    ]);
}

function guardar($conexion) {
    // Al recibir un JSON estructurado desde JS, usamos file_get_contents
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'No se recibieron datos válidos.']);
        return;
    }

    $id_compra = $data['id_compra'] ?? '';
    $id_proveedor = $data['id_proveedor'] ?? '';
    $id_metodo = $data['id_metodo'] ?? '';
    $id_moneda = $data['id_moneda'] ?? '';
    $detalle_compra = $data['detalle'] ?? '';
    $estado = $data['estado'] ?? 'activo';
    
    $detalles_articulos = $data['detalles_articulos'] ?? []; // Arreglo del carrito
    $usuario = $_SESSION['id_usuario'] ?? 1;

    // Validar que existan artículos
    if (count($detalles_articulos) == 0) {
        echo json_encode(['success' => false, 'message' => 'Debe agregar al menos un artículo a la compra.']);
        return;
    }

    // Calcular Totales por seguridad en el backend
    $monto_total = 0;
    $cantidad_total = 0;
    foreach ($detalles_articulos as $item) {
        $monto_total += ($item['precio'] * $item['cantidad']);
        $cantidad_total += $item['cantidad'];
    }

    try {
        $conexion->begin_transaction();

        if ($id_compra == '') {
            // 1. CREAR CABECERA (COMPRA)
            $sqlCompra = "INSERT INTO compra (id_proveedor, monto, cantidad_articulo, id_metodo, id_moneda, detalle, estado, usuario_creacion) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtCompra = $conexion->prepare($sqlCompra);
            $stmtCompra->bind_param("idiisssi", $id_proveedor, $monto_total, $cantidad_total, $id_metodo, $id_moneda, $detalle_compra, $estado, $usuario);
            $stmtCompra->execute();
            
            $new_id_compra = $conexion->insert_id;

            // 2. INSERTAR DETALLES
            $sqlDetalle = "INSERT INTO detalle_compra (id_compra, id_articulo, cantidad_pedida, cantidad_recibida, precio, subtotal, estado) 
                           VALUES (?, ?, ?, 0, ?, ?, 'espera')"; // cantidad_recibida en 0 y estado 'espera' por defecto
            $stmtDetalle = $conexion->prepare($sqlDetalle);
            
            foreach ($detalles_articulos as $item) {
                $subtotal = $item['precio'] * $item['cantidad'];
                $stmtDetalle->bind_param("iiidd", $new_id_compra, $item['id_articulo'], $item['cantidad'], $item['precio'], $subtotal);
                $stmtDetalle->execute();
            }

        } else {
            // ACTUALIZAR CABECERA
            $sqlUpdate = "UPDATE compra SET id_proveedor = ?, monto = ?, cantidad_articulo = ?, id_metodo = ?, id_moneda = ?, detalle = ?, estado = ? 
                          WHERE id_compra = ?";
            $stmtUpdate = $conexion->prepare($sqlUpdate);
            $stmtUpdate->bind_param("idiisssi", $id_proveedor, $monto_total, $cantidad_total, $id_metodo, $id_moneda, $detalle_compra, $estado, $id_compra);
            $stmtUpdate->execute();

            // ACTUALIZAR DETALLES (Borrando lógicamente todo y volviendo a insertar)
            // OJO: En un entorno estricto no se borra la orden enviada. Pero para un mantenimiento básico de edición:
            $conexion->query("DELETE FROM detalle_compra WHERE id_compra = $id_compra"); // Borrado físico temporal para recrear el carrito
            
            $sqlDetalle = "INSERT INTO detalle_compra (id_compra, id_articulo, cantidad_pedida, cantidad_recibida, precio, subtotal, estado) 
                           VALUES (?, ?, ?, 0, ?, ?, 'espera')";
            $stmtDetalle = $conexion->prepare($sqlDetalle);
            
            foreach ($detalles_articulos as $item) {
                $subtotal = $item['precio'] * $item['cantidad'];
                $stmtDetalle->bind_param("iiidd", $id_compra, $item['id_articulo'], $item['cantidad'], $item['precio'], $subtotal);
                $stmtDetalle->execute();
            }
        }
        
        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Orden de compra guardada con éxito.']);
        
    } catch (Exception $e) {
        $conexion->rollback(); 
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function obtener($conexion) {
    $id = (int)$_GET['id_compra'];
    
    // Obtener Cabecera
    $sql = "SELECT * FROM compra WHERE id_compra = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $compra = $stmt->get_result()->fetch_assoc();
    
    // Obtener Detalles
    $sql_detalles = "SELECT d.id_articulo, a.nombre, d.cantidad_pedida as cantidad, d.precio, d.subtotal 
                     FROM detalle_compra d
                     JOIN repuesto_articulo a ON d.id_articulo = a.id_articulo
                     WHERE d.id_compra = ?";
    $stmt2 = $conexion->prepare($sql_detalles);
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $detalles = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Unir todo para enviar al Frontend
    $compra['detalles_articulos'] = $detalles;
    
    echo json_encode([
        'success' => true, 
        'data' => $compra
    ]);
}

function eliminar($conexion) {
    $id = (int)$_POST['id_compra'];
    
    try {
        $conexion->begin_transaction();
        
        // Eliminado Lógico de la cabecera
        $conexion->query("UPDATE compra SET estado = 'eliminado' WHERE id_compra = $id");
        
        // El detalle_compra no tiene columna estado 'eliminado' (tiene recibido/espera), 
        // pero podemos asumir que si la cabecera está eliminada, todo lo está.
        
        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Orden de compra anulada.']);
    } catch(Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al anular.']);
    }
}
?>