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
    // IMPORTANTE: Esta es la única forma de leer un JSON enviado por fetch POST
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Verificación de seguridad
    if (!$data || empty($data['items'])) {
        // Si entra aquí, es porque el JSON llegó vacío o mal formado
        echo json_encode([
            'success' => false, 
            'message' => 'No hay artículos para recibir. El servidor recibió: ' . $json
        ]);
        return;
    }

    $id_compra = (int)$data['id_compra'];
    $num_conduze_input = $data['num_conduze'] ?? 0;
    $usuario = $_SESSION['id_usuario'] ?? 1;

    try {
        $conexion->begin_transaction();

        // Obtener id_proveedor
        $res_p = $conexion->query("SELECT id_proveedor FROM Compra WHERE id_compra = $id_compra");
        if($res_p->num_rows == 0) throw new Exception("La compra #$id_compra no existe.");
        
        $prov = $res_p->fetch_assoc();
        $id_prov = $prov['id_proveedor'];

        // Tomamos el almacén del primer item para la cabecera
        $id_alm_header = $data['items'][0]['id_almacen'];

        // 1. Insertar Maestro Recepción
        $sql_ins = "INSERT INTO Recepcion_Compra (id_proveedor, id_compra, num_conduze, id_almacen, monto_total, id_moneda, usuario_recepcion, estado) 
                    VALUES (?, ?, ?, ?, 0.00, 1, ?, 'activo')";
        $stmt_ins = $conexion->prepare($sql_ins);
        $stmt_ins->bind_param("iiiii", $id_prov, $id_compra, $num_conduze_input, $id_alm_header, $usuario);
        $stmt_ins->execute();

        // 2. Procesar cada artículo del array 'items'
        foreach ($data['items'] as $item) {
            $id_art = (int)$item['id_articulo'];
            $cant = (int)$item['cantidad'];
            $id_alm = (int)$item['id_almacen'];

            // A. Detalle Recepción
            $sql_det = "INSERT INTO Detalle_Recepcion (id_proveedor, num_conduze, id_articulo, cantidad, fecha_entrega) 
                        VALUES (?, ?, ?, ?, NOW())";
            $stmt_det = $conexion->prepare($sql_det);
            $stmt_det->bind_param("iiii", $id_prov, $num_conduze_input, $id_art, $cant);
            $stmt_det->execute();

            // B. Actualizar lo recibido en la Orden de Compra
            $sql_upd_c = "UPDATE Detalle_Compra SET cantidad_recibida = cantidad_recibida + ? 
                          WHERE id_compra = ? AND id_articulo = ?";
            $stmt_upd_c = $conexion->prepare($sql_upd_c);
            $stmt_upd_c->bind_param("iii", $cant, $id_compra, $id_art);
            $stmt_upd_c->execute();

            // C. Sumar al Inventario (Góndola 1 = Recepción)
            $id_gondola_recibo = 1; 
            $sql_inv = "INSERT INTO Inventario (id_articulo, id_gondola, cantidad, estado) 
                        VALUES (?, ?, ?, 'activo') 
                        ON DUPLICATE KEY UPDATE cantidad = cantidad + ?";
            $stmt_inv = $conexion->prepare($sql_inv);
            $stmt_inv->bind_param("iiii", $id_art, $id_gondola_recibo, $cant, $cant);
            $stmt_inv->execute();
        }

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Stock actualizado correctamente.']);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => 'Error en DB: ' . $e->getMessage()]);
    }
}