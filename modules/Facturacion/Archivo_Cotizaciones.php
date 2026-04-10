<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? 1;
$id_sucursal = $_SESSION['id_sucursal'] ?? 1;

switch ($action) {
    case 'listar_pendientes': listar_pendientes($conexion, $id_sucursal); break;
    case 'listar_historial': listar_historial($conexion, $id_sucursal); break;
    case 'buscar_servicios': buscar_servicios($conexion); break;
    case 'buscar_repuestos': buscar_repuestos($conexion, $id_sucursal); break;
    case 'buscar_vehiculos': buscar_vehiculos($conexion); break;
    case 'crear_directa': crear_directa($conexion, $id_usuario, $id_sucursal); break;
    case 'guardar_cotizacion': guardar_cotizacion($conexion); break;
    case 'aprobar_cotizacion': aprobar_cotizacion($conexion, $id_usuario, $id_sucursal); break;
    case 'aprobar_pos': aprobar_pos($conexion, $id_usuario, $id_sucursal); break;
    case 'rechazar_cotizacion': rechazar_cotizacion($conexion); break;
    case 'obtener_detalle': obtener_detalle($conexion); break;
    default: echo json_encode(['success' => false, 'message' => 'Acción no válida']); break;
}

function listar_pendientes($conexion, $id_sucursal) {
    $sql = "SELECT id_cotizacion, DATE_FORMAT(fecha_creacion, '%d/%m/%Y %h:%i %p') as fecha, 
                   nombre_cliente as cliente, vehiculo_desc as vehiculo, monto_total, estado,
                   tipo_cotizacion, IF(id_cliente IS NULL, 1, 0) as es_ocasional
            FROM cotizacion 
            WHERE estado = 'Pendiente' AND id_sucursal = $id_sucursal 
            ORDER BY id_cotizacion DESC";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
}

function listar_historial($conexion, $id_sucursal) {
    // Añadimos filtro de fechas para que no colapse cuando tengas 10,000 cotizaciones
    $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $_POST['fecha_fin'] ?? date('Y-m-t');

    try {
        $sql = "SELECT id_cotizacion, DATE_FORMAT(fecha_creacion, '%d/%m/%Y %h:%i %p') as fecha, 
                       nombre_cliente as cliente, vehiculo_desc as vehiculo, monto_total, estado, 
                       tipo_cotizacion, IF(id_cliente IS NULL, 1, 0) as es_ocasional
                FROM cotizacion 
                WHERE id_sucursal = ? 
                  AND DATE(fecha_creacion) >= ? 
                  AND DATE(fecha_creacion) <= ?
                ORDER BY id_cotizacion DESC";
                
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iss", $id_sucursal, $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $res = $stmt->get_result();
        
        echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function buscar_servicios($conexion) {
    $term = "%" . ($_GET['term'] ?? '') . "%";
    $sql = "SELECT id_tipo_servicio as id, nombre as descripcion, precio, 'servicio' as tipo 
            FROM tipo_servicio WHERE nombre LIKE ? AND estado = 'activo'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $term);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

function buscar_repuestos($conexion, $id_sucursal) {
    $term = "%" . ($_GET['term'] ?? '') . "%";
    $sql = "SELECT ra.id_articulo as id, ra.nombre as descripcion, ra.precio_venta as precio, SUM(i.cantidad) as stock, 'repuesto' as tipo
            FROM repuesto_articulo ra
            JOIN inventario i ON ra.id_articulo = i.id_articulo
            JOIN gondola g ON i.id_gondola = g.id_gondola
            JOIN almacen a ON g.id_almacen = a.id_almacen
            WHERE (ra.nombre LIKE ? OR ra.num_serie LIKE ?) AND a.id_sucursal = ? AND ra.estado = 'activo'
            GROUP BY ra.id_articulo, ra.nombre, ra.precio_venta HAVING stock > 0";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssi", $term, $term, $id_sucursal);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

function buscar_vehiculos($conexion) {
    $term = "%" . ($_GET['term'] ?? '') . "%";
    $sql = "SELECT v.sec_vehiculo as id_vehiculo, c.id_cliente, v.placa, 
                   CONCAT(m.nombre, ' ', IFNULL(v.modelo, ''), ' [', v.placa, ']') as vehiculo_desc,
                   CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) as cliente,
                   (SELECT tel.numero FROM telefono tel JOIN cliente_telefono ct ON tel.id_telefono = ct.id_telefono WHERE ct.id_cliente = c.id_cliente LIMIT 1) as telefono
            FROM vehiculo v
            JOIN marca m ON v.id_marca = m.id_marca
            JOIN cliente c ON v.id_cliente = c.id_cliente
            JOIN persona p ON c.id_persona = p.id_persona
            WHERE v.placa LIKE ? OR p.nombre LIKE ? OR p.cedula LIKE ?
            LIMIT 15";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sss", $term, $term, $term);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

function crear_directa($conexion, $id_usuario, $id_sucursal) {
    $tipo_cliente = $_POST['tipo_cliente'] ?? 'registrado';
    $tipo_cotizacion = $_POST['tipo_cotizacion'] ?? 'Taller';
    
    $conexion->begin_transaction();
    try {
        if ($tipo_cliente === 'registrado') {
            $id_cliente = (int)$_POST['id_cliente'];
            $id_vehiculo = (int)$_POST['id_vehiculo'];
            $nombre = $_POST['nombre_cliente'];
            $vehiculo = $_POST['vehiculo_desc'];
            $telefono = $_POST['telefono_cliente'];

            $sql = "INSERT INTO cotizacion (id_cliente, id_vehiculo, tipo_cotizacion, nombre_cliente, telefono_cliente, vehiculo_desc, usuario_creacion, id_sucursal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("iisssssi", $id_cliente, $id_vehiculo, $tipo_cotizacion, $nombre, $telefono, $vehiculo, $id_usuario, $id_sucursal);
            $stmt->execute();
        } else {
            $nombre = $_POST['nombre_ocasional'] ?? 'Cliente Ocasional';
            $telefono = $_POST['telefono_ocasional'] ?? '';
            $vehiculo = $_POST['vehiculo_ocasional'] ?? 'Vehículo no especificado';

            $sql = "INSERT INTO cotizacion (tipo_cotizacion, nombre_cliente, telefono_cliente, vehiculo_desc, usuario_creacion, id_sucursal) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ssssii", $tipo_cotizacion, $nombre, $telefono, $vehiculo, $id_usuario, $id_sucursal);
            $stmt->execute();
        }
        
        $conexion->commit();
        echo json_encode(['success' => true, 'id_cotizacion' => $conexion->insert_id]);
    } catch(Exception $e) {
        $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function guardar_cotizacion($conexion) {
    $data = json_decode(file_get_contents("php://input"), true);
    $id_cotizacion = (int)$data['id_cotizacion'];
    $items = $data['items'] ?? [];
    $total = (float)$data['total_final'];

    $conexion->begin_transaction();
    try {
        $conexion->query("DELETE FROM cotizacion_detalle WHERE id_cotizacion = $id_cotizacion");

        $sqlD = "INSERT INTO cotizacion_detalle (id_cotizacion, tipo_item, id_item, descripcion, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtD = $conexion->prepare($sqlD);

        foreach ($items as $item) {
            $sub = (float)$item['precio'] * (int)$item['cantidad'];
            $stmtD->bind_param("isssidd", $id_cotizacion, $item['tipo'], $item['id'], $item['descripcion'], $item['cantidad'], $item['precio'], $sub);
            $stmtD->execute();
        }

        $conexion->query("UPDATE cotizacion SET monto_total = $total WHERE id_cotizacion = $id_cotizacion");

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Cotización guardada exitosamente.']);
    } catch (Exception $e) {
        $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function aprobar_cotizacion($conexion, $id_usuario, $id_sucursal) {
    $id_cotizacion = (int)$_POST['id_cotizacion'];
    
    $conexion->begin_transaction();
    try {
        $cot = $conexion->query("SELECT * FROM cotizacion WHERE id_cotizacion = $id_cotizacion")->fetch_assoc();
        if ($cot['tipo_cotizacion'] === 'POS') {
            throw new Exception("Error: Una cotización POS no puede ser enviada a Taller.");
        }

        $detalles = $conexion->query("SELECT * FROM cotizacion_detalle WHERE id_cotizacion = $id_cotizacion")->fetch_all(MYSQLI_ASSOC);
        $id_vehiculo = $cot['id_vehiculo'];

        if (!$id_vehiculo) {
            throw new Exception("Error: Cliente Ocasional no tiene vehículo registrado. Archive esta cotización o registre al cliente formalmente.");
        }

        $resEmp = $conexion->query("SELECT id_empleado FROM empleado WHERE estado = 'activo' LIMIT 1");
        $id_empleado = $resEmp->num_rows > 0 ? $resEmp->fetch_assoc()['id_empleado'] : 1;
        $conexion->query("INSERT INTO inspeccion (id_vehiculo, id_empleado, usuario_creacion, id_sucursal, kilometraje_recepcion, nivel_combustible, estado) VALUES ($id_vehiculo, $id_empleado, $id_usuario, $id_sucursal, 0, '1/4', 'activo')");
        $id_inspeccion = $conexion->insert_id;

        $descOrd = "Orden desde Cotización COT-" . $id_cotizacion;
        $stmtOrd = $conexion->prepare("INSERT INTO orden (id_inspeccion, id_sucursal, descripcion, monto_total, estado, usuario_creacion) VALUES (?, ?, ?, ?, 'activo', ?)");
        $stmtOrd->bind_param("iisdi", $id_inspeccion, $id_sucursal, $descOrd, $cot['monto_total'], $id_usuario);
        $stmtOrd->execute();
        $id_orden = $conexion->insert_id;

        foreach ($detalles as $d) {
            if ($d['tipo_item'] === 'servicio') {
                $conexion->query("INSERT INTO orden_servicio (id_orden, id_tipo_servicio, estado) VALUES ($id_orden, {$d['id_item']}, 'activo')");
            } else {
                $conexion->query("INSERT INTO orden_repuesto (id_orden, id_articulo, cantidad, precio_base, sub_total, estado) VALUES ($id_orden, {$d['id_item']}, {$d['cantidad']}, {$d['precio_unitario']}, {$d['subtotal']}, 'activo')");
            }
        }

        $resEst = $conexion->query("SELECT id_estado FROM estado WHERE nombre = 'Diagnóstico' LIMIT 1");
        if($resEst->num_rows > 0) {
            $id_est = $resEst->fetch_assoc()['id_estado'];
            $conexion->query("INSERT INTO orden_estado (id_orden, id_estado, usuario_creacion) VALUES ($id_orden, $id_est, $id_usuario)");
        }

        $conexion->query("UPDATE cotizacion SET estado = 'Aprobada' WHERE id_cotizacion = $id_cotizacion");

        $conexion->commit();
        echo json_encode(['success' => true, 'id_orden' => $id_orden, 'message' => "Aprobada. Se generó la Orden ORD-$id_orden."]);
    } catch (Exception $e) {
        $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function aprobar_pos($conexion, $id_usuario, $id_sucursal) {
    $id_cotizacion = (int)$_POST['id_cotizacion'];
    $ncf = $_POST['ncf'] ?? 'B0200000001';
    $metodo_pago = (int)$_POST['metodo_pago'];

    $conexion->begin_transaction();
    try {
        $cot = $conexion->query("SELECT * FROM cotizacion WHERE id_cotizacion = $id_cotizacion")->fetch_assoc();
        $detalles = $conexion->query("SELECT * FROM cotizacion_detalle WHERE id_cotizacion = $id_cotizacion")->fetch_all(MYSQLI_ASSOC);

        // CORRECCIÓN MAGISTRAL: Evitar Error de Llave Foránea con Clientes Ocasionales (NULL)
        if (!empty($cot['id_cliente'])) {
            $sqlF = "INSERT INTO factura_central (id_cliente, id_sucursal, id_cotizacion, id_metodo, id_moneda, NCF, monto_total, estado_pago, usuario_creacion, estado) VALUES (?, ?, ?, ?, 1, ?, ?, 'Pagado', ?, 'activo')";
            $stmtF = $conexion->prepare($sqlF);
            $stmtF->bind_param("iiiisdi", $cot['id_cliente'], $id_sucursal, $id_cotizacion, $metodo_pago, $ncf, $cot['monto_total'], $id_usuario);
        } else {
            // Si no hay cliente (Ocasional), lo insertamos sin esa columna para que la BD asuma el NULL natural sin pelear.
            $sqlF = "INSERT INTO factura_central (id_sucursal, id_cotizacion, id_metodo, id_moneda, NCF, monto_total, estado_pago, usuario_creacion, estado) VALUES (?, ?, ?, 1, ?, ?, 'Pagado', ?, 'activo')";
            $stmtF = $conexion->prepare($sqlF);
            $stmtF->bind_param("iiisdi", $id_sucursal, $id_cotizacion, $metodo_pago, $ncf, $cot['monto_total'], $id_usuario);
        }
        $stmtF->execute();
        $id_factura = $conexion->insert_id;

        $conexion->query("INSERT IGNORE INTO tipo_movimiento (id_tipo_m, nombre, estado, usuario_creacion) VALUES (2, 'Venta POS', 'activo', $id_usuario)");

        $stmtD = $conexion->prepare("INSERT INTO detalle_factura (id_factura, id_articulo, cantidad, precio, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmtU = $conexion->prepare("UPDATE inventario i INNER JOIN gondola g ON i.id_gondola = g.id_gondola INNER JOIN almacen a ON g.id_almacen = a.id_almacen SET i.cantidad = i.cantidad - ? WHERE i.id_articulo = ? AND a.id_sucursal = ?");
        $stmtM = $conexion->prepare("INSERT INTO movimiento_inventario (id_articulo, id_tipo_m, cantidad, motivo, fecha_creacion, estado, usuario_creacion) VALUES (?, 2, ?, ?, NOW(), 'activo', ?)");

        foreach ($detalles as $d) {
            if ($d['tipo_item'] === 'repuesto') {
                $stmtD->bind_param("iiidd", $id_factura, $d['id_item'], $d['cantidad'], $d['precio_unitario'], $d['subtotal']);
                $stmtD->execute();

                $stmtU->bind_param("iii", $d['cantidad'], $d['id_item'], $id_sucursal);
                $stmtU->execute();

                $motivo = "Venta POS desde Cotización COT-" . $id_cotizacion;
                $stmtM->bind_param("iisi", $d['id_item'], $d['cantidad'], $motivo, $id_usuario);
                $stmtM->execute();
            }
        }

        $conexion->query("UPDATE cotizacion SET estado = 'Aprobada' WHERE id_cotizacion = $id_cotizacion");

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => "Facturado con éxito. N° Factura: FAC-$id_factura"]);
    } catch (Exception $e) {
        $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function rechazar_cotizacion($conexion) {
    $id_cotizacion = (int)$_POST['id_cotizacion'];
    if($conexion->query("UPDATE cotizacion SET estado = 'Rechazada' WHERE id_cotizacion = $id_cotizacion")) {
        echo json_encode(['success' => true, 'message' => 'Cotización Archivada/Rechazada.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al archivar.']);
    }
}

function obtener_detalle($conexion) {
    $id_cotizacion = (int)$_GET['id_cotizacion'];
    $sql = "SELECT tipo_item as tipo, id_item as id, descripcion, cantidad, precio_unitario as precio FROM cotizacion_detalle WHERE id_cotizacion = $id_cotizacion";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
}
?>