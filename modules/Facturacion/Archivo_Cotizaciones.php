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
    case 'rechazar_cotizacion': rechazar_cotizacion($conexion); break;
    case 'obtener_detalle': obtener_detalle($conexion); break;
    default: echo json_encode(['success' => false, 'message' => 'Acción no válida']); break;
}

function listar_pendientes($conexion, $id_sucursal) {
    $sql = "SELECT id_cotizacion, DATE_FORMAT(fecha_creacion, '%d/%m/%Y %h:%i %p') as fecha, 
                   nombre_cliente as cliente, vehiculo_desc as vehiculo, monto_total, estado 
            FROM cotizacion 
            WHERE estado = 'Pendiente' AND id_sucursal = $id_sucursal 
            ORDER BY id_cotizacion DESC";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
}

function listar_historial($conexion, $id_sucursal) {
    $sql = "SELECT id_cotizacion, DATE_FORMAT(fecha_creacion, '%d/%m/%Y %h:%i %p') as fecha, 
                   nombre_cliente as cliente, vehiculo_desc as vehiculo, monto_total, estado 
            FROM cotizacion 
            WHERE id_sucursal = $id_sucursal 
            ORDER BY id_cotizacion DESC";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
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
                   (SELECT tel.numero FROM telefono tel 
                    JOIN cliente_telefono ct ON tel.id_telefono = ct.id_telefono 
                    WHERE ct.id_cliente = c.id_cliente LIMIT 1) as telefono
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
    
    $conexion->begin_transaction();
    try {
        if ($tipo_cliente === 'registrado') {
            $id_cliente = (int)$_POST['id_cliente'];
            $id_vehiculo = (int)$_POST['id_vehiculo'];
            $nombre = $_POST['nombre_cliente'];
            $vehiculo = $_POST['vehiculo_desc'];
            $telefono = $_POST['telefono_cliente'];

            $sql = "INSERT INTO cotizacion (id_cliente, id_vehiculo, nombre_cliente, telefono_cliente, vehiculo_desc, usuario_creacion, id_sucursal) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("iisssii", $id_cliente, $id_vehiculo, $nombre, $telefono, $vehiculo, $id_usuario, $id_sucursal);
            $stmt->execute();
        } else {
            $nombre = $_POST['nombre_ocasional'] ?? 'Cliente Ocasional';
            $telefono = $_POST['telefono_ocasional'] ?? '';
            $vehiculo = $_POST['vehiculo_ocasional'] ?? 'Vehículo no especificado';

            $sql = "INSERT INTO cotizacion (nombre_cliente, telefono_cliente, vehiculo_desc, usuario_creacion, id_sucursal) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssii", $nombre, $telefono, $vehiculo, $id_usuario, $id_sucursal);
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
        $detalles = $conexion->query("SELECT * FROM cotizacion_detalle WHERE id_cotizacion = $id_cotizacion")->fetch_all(MYSQLI_ASSOC);

        $id_vehiculo = $cot['id_vehiculo'];

        if (!$id_vehiculo) {
            // 1. Crear Persona
            $stmtP = $conexion->prepare("INSERT INTO persona (nombre, id_direccion, nacionalidad, estado, fecha_nacimiento) VALUES (?, 1, 1, 'activo', '2000-01-01')");
            $stmtP->bind_param("s", $cot['nombre_cliente']);
            $stmtP->execute();
            $id_persona = $conexion->insert_id;
            
            // 2. Crear Cliente
            $conexion->query("INSERT INTO cliente (id_persona, usuario_creacion, estado) VALUES ($id_persona, $id_usuario, 'activo')");
            $id_cliente = $conexion->insert_id;

            // 3. Crear Teléfono y vincularlo
            if(!empty($cot['telefono_cliente'])) {
                $conexion->query("INSERT INTO telefono (numero, estado) VALUES ('{$cot['telefono_cliente']}', 'activo')");
                $id_tel = $conexion->insert_id;
                $conexion->query("INSERT INTO cliente_telefono (id_cliente, id_telefono, estado) VALUES ($id_cliente, $id_tel, 'activo')");
            }

            // 4. Crear Vehículo
            $resMarca = $conexion->query("SELECT id_marca FROM marca LIMIT 1");
            $id_marca = $resMarca->num_rows > 0 ? $resMarca->fetch_assoc()['id_marca'] : 1;
            $resColor = $conexion->query("SELECT id_color FROM color LIMIT 1");
            $id_color = $resColor->num_rows > 0 ? $resColor->fetch_assoc()['id_color'] : 1;
            
            $placa = strtoupper(uniqid('COT-'));
            $vin = strtoupper(uniqid('VIN-'));
            $stmtV = $conexion->prepare("INSERT INTO vehiculo (id_cliente, id_marca, id_color, modelo, placa, vin_chasis, estado, usuario_creacion) VALUES (?, ?, ?, ?, ?, ?, 'activo', ?)");
            $stmtV->bind_param("iiisssi", $id_cliente, $id_marca, $id_color, $cot['vehiculo_desc'], $placa, $vin, $id_usuario);
            $stmtV->execute();
            $id_vehiculo = $conexion->insert_id;
            
            $conexion->query("UPDATE cotizacion SET id_cliente = $id_cliente, id_vehiculo = $id_vehiculo WHERE id_cotizacion = $id_cotizacion");
        }

        // 5. Crear Inspección
        $resEmp = $conexion->query("SELECT id_empleado FROM empleado WHERE estado = 'activo' LIMIT 1");
        $id_empleado = $resEmp->num_rows > 0 ? $resEmp->fetch_assoc()['id_empleado'] : 1;
        $conexion->query("INSERT INTO inspeccion (id_vehiculo, id_empleado, usuario_creacion, id_sucursal, kilometraje_recepcion, nivel_combustible, estado) VALUES ($id_vehiculo, $id_empleado, $id_usuario, $id_sucursal, 0, '1/4', 'activo')");
        $id_inspeccion = $conexion->insert_id;

        // 6. Crear Orden oficial
        $descOrd = "Orden desde Cotización COT-" . $id_cotizacion;
        $stmtOrd = $conexion->prepare("INSERT INTO orden (id_inspeccion, id_sucursal, descripcion, monto_total, estado, usuario_creacion) VALUES (?, ?, ?, ?, 'activo', ?)");
        $stmtOrd->bind_param("iisdi", $id_inspeccion, $id_sucursal, $descOrd, $cot['monto_total'], $id_usuario);
        $stmtOrd->execute();
        $id_orden = $conexion->insert_id;

        // 7. Transferir Detalles a Orden_Servicio y Orden_Repuesto
        foreach ($detalles as $d) {
            if ($d['tipo_item'] === 'servicio') {
                $conexion->query("INSERT INTO orden_servicio (id_orden, id_tipo_servicio, estado) VALUES ($id_orden, {$d['id_item']}, 'activo')");
            } else {
                $conexion->query("INSERT INTO orden_repuesto (id_orden, id_articulo, cantidad, precio_base, sub_total, estado) VALUES ($id_orden, {$d['id_item']}, {$d['cantidad']}, {$d['precio_unitario']}, {$d['subtotal']}, 'activo')");
            }
        }

        // 8. Establecer fase inicial: Diagnóstico (ID 1)
        $resEst = $conexion->query("SELECT id_estado FROM estado WHERE nombre = 'Diagnóstico' LIMIT 1");
        if($resEst->num_rows > 0) {
            $id_est = $resEst->fetch_assoc()['id_estado'];
            $conexion->query("INSERT INTO orden_estado (id_orden, id_estado, usuario_creacion) VALUES ($id_orden, $id_est, $id_usuario)");
        }

        // 9. Finalizar Cotización
        $conexion->query("UPDATE cotizacion SET estado = 'Aprobada' WHERE id_cotizacion = $id_cotizacion");

        $conexion->commit();
        // AQUI SE AGREGO EL ID_ORDEN AL JSON DE RESPUESTA
        echo json_encode(['success' => true, 'id_orden' => $id_orden, 'message' => "Aprobada exitosamente. Se generó la Orden ORD-$id_orden y pasó a Inspección (Diagnóstico)."]);
    } catch (Exception $e) {
        $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function rechazar_cotizacion($conexion) {
    $id_cotizacion = (int)$_POST['id_cotizacion'];
    if($conexion->query("UPDATE cotizacion SET estado = 'Rechazada' WHERE id_cotizacion = $id_cotizacion")) {
        echo json_encode(['success' => true, 'message' => 'Cotización marcada como Rechazada.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al procesar el rechazo.']);
    }
}

function obtener_detalle($conexion) {
    $id_cotizacion = (int)$_GET['id_cotizacion'];
    $sql = "SELECT tipo_item as tipo, id_item as id, descripcion, cantidad, precio_unitario as precio FROM cotizacion_detalle WHERE id_cotizacion = $id_cotizacion";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
}
?>