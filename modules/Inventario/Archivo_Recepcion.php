<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'buscar_compra':
        buscar_compra($conexion);
        break;
    case 'obtener_detalle':
        obtener_detalle($conexion);
        break;
    case 'get_almacenes':
        get_almacenes($conexion);
        break;
    case 'guardar_recepcion':
        guardar_recepcion($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

// ==========================================
// FUNCIONES DE RECEPCIÓN
// ==========================================

function buscar_compra($conexion) {
    // Eliminamos el filtrado obligatorio. Ahora carga todas las activas con pendientes.
    $sql = "SELECT DISTINCT c.id_compra, p.nombre_comercial as proveedor, 
                   DATE_FORMAT(c.fecha_creacion, '%d/%m/%Y %H:%i') as fecha,
                   (SELECT COUNT(*) FROM Detalle_Compra WHERE id_compra = c.id_compra) as total_items
            FROM Compra c
            INNER JOIN Proveedor p ON c.id_proveedor = p.id_proveedor
            INNER JOIN Detalle_Compra dc ON c.id_compra = dc.id_compra
            WHERE dc.cantidad_recibida < dc.cantidad_pedida 
            AND c.estado != 'eliminado'
            ORDER BY c.id_compra DESC";
    
    $res = $conexion->query($sql);
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}

function obtener_detalle($conexion) {
    $id = (int)($_GET['id'] ?? 0);
    
    // 1. Cabecera de la compra
    $sql_m = "SELECT c.id_compra, p.nombre_comercial as proveedor, c.monto 
              FROM Compra c 
              INNER JOIN Proveedor p ON c.id_proveedor = p.id_proveedor 
              WHERE c.id_compra = ?";
    $stmt_m = $conexion->prepare($sql_m);
    $stmt_m->bind_param("i", $id);
    $stmt_m->execute();
    $compra = $stmt_m->get_result()->fetch_assoc();

    if (!$compra) {
        echo json_encode(['success' => false, 'message' => 'Compra no encontrada']);
        return;
    }

    // 2. Artículos con Faltantes + Imagen + Precio + Subtotal
    // Nota: Calculamos el subtotal directamente en el SQL para mayor precisión
    $sql_d = "SELECT dc.id_articulo, ra.nombre, ra.imagen, 
                     dc.cantidad_pedida, dc.cantidad_recibida, 
                     ra.precio_compra,
                     (dc.cantidad_pedida * ra.precio_compra) as subtotal
              FROM Detalle_Compra dc
              INNER JOIN Repuesto_Articulo ra ON dc.id_articulo = ra.id_articulo
              WHERE dc.id_compra = ? AND dc.cantidad_recibida < dc.cantidad_pedida";
              
    $stmt_d = $conexion->prepare($sql_d);
    $stmt_d->bind_param("i", $id);
    $stmt_d->execute();
    $articulos = $stmt_d->get_result()->fetch_all(MYSQLI_ASSOC);

    $compra['articulos'] = $articulos;
    
    echo json_encode([
        'success' => true, 
        'data' => $compra
    ]);
}

function get_almacenes($conexion) {
    $sql = "SELECT id_almacen, nombre FROM Almacen WHERE estado = 'activo'";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function guardar_recepcion($conexion) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data || empty($data['items'])) {
        echo json_encode(['success' => false, 'message' => 'Sin datos para procesar.']);
        return;
    }

    $id_compra = (int)$data['id_compra'];
    $num_conduze_input = trim((string)$data['num_conduze']);
    $usuario = $_SESSION['id_usuario'] ?? 1;

    // --- NUEVA VALIDACIÓN DE CONDUCE DUPLICADO ---
    // Verificamos si ya existe una recepción con este número de conduce
    $sql_check = "SELECT id_recepcion FROM Recepcion_Compra WHERE num_conduze = ? LIMIT 1";
    $stmt_check = $conexion->prepare($sql_check);
    $stmt_check->bind_param("s", $num_conduze_input);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        echo json_encode([
            'success' => false, 
            'message' => "El número de conduce '$num_conduze_input' ya ha sido registrado previamente. Por favor, verifique el documento."
        ]);
        return;
    }
    // ---------------------------------------------

    try {
        $conexion->begin_transaction();

        // Obtener proveedor y preparar la cabecera
        $res_p = $conexion->query("SELECT id_proveedor FROM Compra WHERE id_compra = $id_compra");
        $prov = $res_p->fetch_assoc();
        $id_prov = (int)$prov['id_proveedor'];
        $id_alm_header = (int)$data['items'][0]['id_almacen'];

        // 1. Maestro
        $sql_ins = "INSERT INTO Recepcion_Compra (id_proveedor, id_compra, num_conduze, id_almacen, monto_total, id_moneda, usuario_recepcion, estado) 
                    VALUES (?, ?, ?, ?, 0.00, 3, ?, 'activo')";
        $stmt_ins = $conexion->prepare($sql_ins);
        $stmt_ins->bind_param("iisis", $id_prov, $id_compra, $num_conduze_input, $id_alm_header, $usuario);
        $stmt_ins->execute();

        // 2. Preparar Statements para el bucle
        $stmt_det = $conexion->prepare("INSERT INTO Detalle_Recepcion (id_proveedor, num_conduze, id_articulo, cantidad, fecha_entrega) VALUES (?, ?, ?, ?, NOW())");
        $stmt_upd_c = $conexion->prepare("UPDATE Detalle_Compra SET cantidad_recibida = cantidad_recibida + ? WHERE id_compra = ? AND id_articulo = ?");
        $stmt_inv = $conexion->prepare("INSERT INTO Inventario (id_articulo, id_gondola, cantidad, estado) VALUES (?, 1, ?, 'activo') ON DUPLICATE KEY UPDATE cantidad = cantidad + ?");
        $stmt_mov = $conexion->prepare("INSERT INTO Movimiento_Inventario_Almacen (id_compra, id_gondola, id_tipo_movimiento, fecha_movimiento, estado) VALUES (?, ?, 1, NOW(), 'activo')");

        // 3. Ejecutar bucle
        foreach ($data['items'] as $item) {
            $id_art = (int)$item['id_articulo'];
            $cant  = (int)$item['cantidad'];

            $stmt_det->bind_param("isii", $id_prov, $num_conduze_input, $id_art, $cant);
            $stmt_det->execute();

            $stmt_upd_c->bind_param("iii", $cant, $id_compra, $id_art);
            $stmt_upd_c->execute();

            $stmt_inv->bind_param("iii", $id_art, $cant, $cant);
            $stmt_inv->execute();
            
            $id_gondola_recibo = 1;
            $stmt_mov->bind_param("ii", $id_compra, $id_gondola_recibo);
            $stmt_mov->execute();
        }

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => '¡Recepción guardada con éxito! Inventario actualizado.']);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}