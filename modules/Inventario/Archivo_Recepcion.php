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
    case 'buscar_empleado':
        buscar_empleado($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

// ==========================================
// FUNCIONES DE RECEPCIÓN
// ==========================================

function buscar_compra($conexion) {
    $sql = "SELECT DISTINCT c.id_compra, p.nombre_comercial as proveedor, 
                   DATE_FORMAT(c.fecha_creacion, '%d/%m/%Y %H:%i') as fecha,
                   (SELECT COUNT(*) FROM detalle_compra WHERE id_compra = c.id_compra) as total_items
            FROM compra c
            INNER JOIN proveedor p ON c.id_proveedor = p.id_proveedor
            INNER JOIN detalle_compra dc ON c.id_compra = dc.id_compra
            WHERE dc.cantidad_recibida < dc.cantidad_pedida 
            AND c.estado != 'eliminado'
            ORDER BY c.id_compra DESC";
    
    $res = $conexion->query($sql);
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}

function buscar_empleado($conexion) {
    $q = $conexion->real_escape_string($_GET['q'] ?? '');
    
    // CORRECCIÓN 1: Unimos nombre y apellido para una mejor búsqueda
    // CORRECCIÓN 2: Quitamos el requerimiento estricto de la sucursal para evitar que se oculten en pruebas
    $sql = "SELECT e.id_empleado, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) as nombre 
            FROM empleado e
            INNER JOIN persona p ON e.id_persona = p.id_persona
            WHERE (p.nombre LIKE ? OR IFNULL(p.apellido_p, '') LIKE ? OR e.id_empleado LIKE ?) 
            AND e.estado = 'activo'
            LIMIT 10";
            
    $stmt = $conexion->prepare($sql);
    $term = "%$q%";
    // Pasamos el término de búsqueda para nombre, apellido e ID
    $stmt->bind_param("sss", $term, $term, $term);
    $stmt->execute();
    
    $res = $stmt->get_result();
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}

function obtener_detalle($conexion) {
    $id = (int)($_GET['id'] ?? 0);
    
    $sql_m = "SELECT c.id_compra, p.nombre_comercial as proveedor, c.monto 
              FROM compra c 
              INNER JOIN proveedor p ON c.id_proveedor = p.id_proveedor 
              WHERE c.id_compra = ?";
    $stmt_m = $conexion->prepare($sql_m);
    $stmt_m->bind_param("i", $id);
    $stmt_m->execute();
    $compra = $stmt_m->get_result()->fetch_assoc();

    if (!$compra) {
        echo json_encode(['success' => false, 'message' => 'Compra no encontrada']);
        return;
    }

    $sql_d = "SELECT dc.id_articulo, ra.nombre, ra.imagen, 
                     dc.cantidad_pedida, dc.cantidad_recibida, 
                     ra.precio_compra,
                     (dc.cantidad_pedida * ra.precio_compra) as subtotal
              FROM detalle_compra dc
              INNER JOIN repuesto_articulo ra ON dc.id_articulo = ra.id_articulo
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
    $sql = "SELECT id_almacen, nombre FROM almacen WHERE estado = 'activo'";
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
    $id_empleado = (int)($data['id_empleado'] ?? 0);
    $num_conduze_input = trim((string)$data['num_conduze']);
    $usuario_sistema = $_SESSION['id_usuario'] ?? 1;

    // CORRECCIÓN 3: Relajamos la validación del empleado para que solo verifique que existe y está activo
    $sql_v_emp = "SELECT id_empleado FROM empleado WHERE id_empleado = ? AND estado = 'activo' LIMIT 1";
    $stmt_v = $conexion->prepare($sql_v_emp);
    $stmt_v->bind_param("i", $id_empleado);
    $stmt_v->execute();

    if ($stmt_v->get_result()->num_rows === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Error: El empleado seleccionado no existe o no está activo.'
        ]);
        return;
    }

    // Verificar si el número de conduce ya existe
    $sql_check = "SELECT id_recepcion FROM recepcion_compra WHERE num_conduze = ? LIMIT 1";
    $stmt_check = $conexion->prepare($sql_check);
    $stmt_check->bind_param("s", $num_conduze_input);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "El número de conduce '$num_conduze_input' ya existe."]);
        return;
    }

    try {
        $conexion->begin_transaction();

        // Obtener datos de la compra original
        $res_p = $conexion->query("SELECT id_proveedor, id_moneda FROM compra WHERE id_compra = $id_compra");
        $prov = $res_p->fetch_assoc();
        $id_prov = (int)$prov['id_proveedor'];
        $id_moneda = (int)$prov['id_moneda'];
        $id_alm_header = (int)$data['items'][0]['id_almacen'];

        // Calcular monto total de lo que se recibe
        $monto_total_recibido = 0;
        foreach ($data['items'] as $item) {
            $id_art = (int)$item['id_articulo'];
            $cant = (int)$item['cantidad'];
            
            $sql_precio = "SELECT precio FROM detalle_compra WHERE id_compra = ? AND id_articulo = ?";
            $stmt_p = $conexion->prepare($sql_precio);
            $stmt_p->bind_param("ii", $id_compra, $id_art);
            $stmt_p->execute();
            $res_p = $stmt_p->get_result()->fetch_assoc();
            
            if ($res_p) {
                $monto_total_recibido += ($res_p['precio'] * $cant);
            }
        }

        // Insertar Cabecera de Recepción
        $sql_ins = "INSERT INTO recepcion_compra 
                    (id_proveedor, id_compra, num_conduze, id_almacen, monto_total, id_moneda, usuario_recepcion, id_empleado, estado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'activo')";
        $stmt_ins = $conexion->prepare($sql_ins);
        $stmt_ins->bind_param("iisidiii", $id_prov, $id_compra, $num_conduze_input, $id_alm_header, $monto_total_recibido, $id_moneda, $usuario_sistema, $id_empleado);
        $stmt_ins->execute();

        // Procesar Items
        $stmt_det = $conexion->prepare("INSERT INTO detalle_recepcion (id_proveedor, num_conduze, id_articulo, cantidad, fecha_entrega) VALUES (?, ?, ?, ?, NOW())");
        $stmt_upd_c = $conexion->prepare("UPDATE detalle_compra SET cantidad_recibida = cantidad_recibida + ? WHERE id_compra = ? AND id_articulo = ?");
        $stmt_inv = $conexion->prepare("INSERT INTO inventario (id_articulo, id_gondola, cantidad, estado) VALUES (?, 1, ?, 'activo') ON DUPLICATE KEY UPDATE cantidad = cantidad + ?");
        $stmt_mov = $conexion->prepare("INSERT INTO movimiento_inventario_almacen (id_compra, id_gondola, id_tipo_movimiento, fecha_movimiento, estado) VALUES (?, ?, 1, NOW(), 'activo')");

        foreach ($data['items'] as $item) {
            $id_art = (int)$item['id_articulo'];
            $cant  = (int)$item['cantidad'];
            $id_gondola_recibo = 1; // Ajustar según lógica de tu almacén

            $stmt_det->bind_param("isii", $id_prov, $num_conduze_input, $id_art, $cant);
            $stmt_det->execute();

            $stmt_upd_c->bind_param("iii", $cant, $id_compra, $id_art);
            $stmt_upd_c->execute();

            $stmt_inv->bind_param("iii", $id_art, $cant, $cant);
            $stmt_inv->execute();
            
            $stmt_mov->bind_param("ii", $id_compra, $id_gondola_recibo);
            $stmt_mov->execute();
        }

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => '¡Recepción guardada con éxito por el empleado seleccionado!']);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>