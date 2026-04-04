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
    $detalles_articulos = $data['detalles_articulos'] ?? []; 
    $usuario = $_SESSION['id_usuario'] ?? 1;

    if (count($detalles_articulos) == 0) {
        echo json_encode(['success' => false, 'message' => 'Debe agregar al menos un artículo a la compra.']);
        return;
    }

    $monto_total = 0;
    $cantidad_total = 0;
    
    foreach ($detalles_articulos as $item) {
        $monto_total += ($item['precio'] * $item['cantidad']);
        $cantidad_total += $item['cantidad'];
    }

    try {
        $conexion->begin_transaction();

        if ($id_compra == '') {
            // INSERTAR NUEVA ORDEN
            $sqlCompra = "INSERT INTO compra (id_proveedor, monto, cantidad_articulo, id_metodo, id_moneda, detalle, estado, usuario_creacion) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtCompra = $conexion->prepare($sqlCompra);
            $stmtCompra->bind_param("idiisssi", $id_proveedor, $monto_total, $cantidad_total, $id_metodo, $id_moneda, $detalle_compra, $estado, $usuario);
            $stmtCompra->execute();
            
            $new_id_compra = $conexion->insert_id;

            $sqlDetalle = "INSERT INTO detalle_compra (id_compra, id_articulo, cantidad_pedida, cantidad_recibida, precio, subtotal, estado) 
                           VALUES (?, ?, ?, 0, ?, ?, 'espera')";
            $stmtDetalle = $conexion->prepare($sqlDetalle);
            
            foreach ($detalles_articulos as $item) {
                $subtotal = $item['precio'] * $item['cantidad'];
                $stmtDetalle->bind_param("iiidd", $new_id_compra, $item['id_articulo'], $item['cantidad'], $item['precio'], $subtotal);
                $stmtDetalle->execute();
            }
            
        } else {
            // ACTUALIZAR ORDEN EXISTENTE
            
            // --- VALIDACIÓN DE DOBLE BLOQUEO (PAGOS O RECEPCIONES) ---
            $sql_check = "SELECT 
                            IFNULL((SELECT SUM(monto_pagado) FROM pago_compra WHERE id_compra = ? AND estado = 'activo'), 0) as pagado,
                            (SELECT COUNT(*) FROM recepcion_compra WHERE id_compra = ? AND estado = 'activo') as recepciones";
            $stmt_check = $conexion->prepare($sql_check);
            $stmt_check->bind_param("ii", $id_compra, $id_compra);
            $stmt_check->execute();
            $bloqueos = $stmt_check->get_result()->fetch_assoc();
            
            if ($bloqueos['pagado'] > 0) {
                throw new Exception("ESTRICTO: No se puede modificar una orden que ya tiene pagos registrados en contabilidad.");
            }
            if ($bloqueos['recepciones'] > 0) {
                throw new Exception("ESTRICTO: No se puede modificar una orden porque ya se han recibido artículos en el almacén.");
            }
            // --------------------------------------

            $sqlUpdate = "UPDATE compra 
                          SET id_proveedor = ?, monto = ?, cantidad_articulo = ?, id_metodo = ?, id_moneda = ?, detalle = ?, estado = ? 
                          WHERE id_compra = ?";
            $stmtUpdate = $conexion->prepare($sqlUpdate);
            $stmtUpdate->bind_param("idiisssi", $id_proveedor, $monto_total, $cantidad_total, $id_metodo, $id_moneda, $detalle_compra, $estado, $id_compra);
            $stmtUpdate->execute();

            $conexion->query("DELETE FROM detalle_compra WHERE id_compra = $id_compra"); 
            
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
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtener($conexion) {
    $id = (int)$_GET['id_compra'];
    
    // Obtenemos cabecera y revisamos si tiene pagos o recepciones para avisarle al frontend
    $sql = "SELECT c.*, 
            IFNULL((SELECT SUM(monto_pagado) FROM pago_compra WHERE id_compra = c.id_compra AND estado = 'activo'), 0) as total_pagado,
            (SELECT COUNT(*) FROM recepcion_compra WHERE id_compra = c.id_compra AND estado = 'activo') as total_recepciones
            FROM compra c 
            WHERE c.id_compra = ?";
            
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $compra = $stmt->get_result()->fetch_assoc();
    
    $sql_detalles = "SELECT d.id_articulo, a.nombre, d.cantidad_pedida as cantidad, d.precio, d.subtotal 
                     FROM detalle_compra d 
                     JOIN repuesto_articulo a ON d.id_articulo = a.id_articulo 
                     WHERE d.id_compra = ?";
                     
    $stmt2 = $conexion->prepare($sql_detalles);
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    
    $compra['detalles_articulos'] = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'data' => $compra
    ]);
}

function eliminar($conexion) {
    $id = (int)$_POST['id_compra'];
    $id_usuario = $_SESSION['id_usuario'] ?? 0;
    
    try {
        $conexion->begin_transaction();
        
        // 1. VALIDACIÓN DE SEGURIDAD: COMPROBAR ROL DE ADMINISTRADOR
        $sql_admin = "SELECT n.nombre 
                      FROM usuario u 
                      JOIN nivel n ON u.id_nivel = n.id_nivel 
                      WHERE u.id_usuario = ?";
        $stmt_admin = $conexion->prepare($sql_admin);
        $stmt_admin->bind_param("i", $id_usuario);
        $stmt_admin->execute();
        $resultado_rol = $stmt_admin->get_result()->fetch_assoc();
        $nombre_rol = $resultado_rol['nombre'] ?? '';
        
        if (stripos($nombre_rol, 'Admin') === false) {
            throw new Exception("ACCESO DENEGADO: Solo un usuario con nivel de Administrador puede anular órdenes de compra.");
        }
        
        // 2. VALIDACIÓN DE DOBLE BLOQUEO (PAGOS O RECEPCIONES)
        $sql_check = "SELECT 
                        IFNULL((SELECT SUM(monto_pagado) FROM pago_compra WHERE id_compra = ? AND estado = 'activo'), 0) as pagado,
                        (SELECT COUNT(*) FROM recepcion_compra WHERE id_compra = ? AND estado = 'activo') as recepciones";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("ii", $id, $id);
        $stmt_check->execute();
        $bloqueos = $stmt_check->get_result()->fetch_assoc();
        
        if ($bloqueos['pagado'] > 0) {
            throw new Exception("No se puede anular una orden que tiene pagos registrados. Anule el pago primero.");
        }
        if ($bloqueos['recepciones'] > 0) {
            throw new Exception("No se puede anular una orden porque ya se han recibido artículos de ella en el almacén.");
        }

        // 3. ANULACIÓN LÓGICA
        $conexion->query("UPDATE compra SET estado = 'eliminado' WHERE id_compra = $id");
        
        $conexion->commit();
        
        echo json_encode(['success' => true, 'message' => 'Orden de compra anulada correctamente por el Administrador.']);
        
    } catch(Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>