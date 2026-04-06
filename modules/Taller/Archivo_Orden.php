<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_usuario_sesion = $_SESSION['id_usuario'] ?? 0;

// 1. OBTENER INFORMACIÓN DEL EMPLEADO Y SUCURSAL
$id_sucursal_user = 0;
if ($id_usuario_sesion > 0) {
    $sql_info = "SELECT es.id_sucursal FROM empleado_usuario eu
                 INNER JOIN empleado_sucursal es ON eu.id_empleado = es.id_empleado
                 WHERE eu.id_usuario = ? AND es.estado = 'activo' LIMIT 1";
    $stmt_info = $conexion->prepare($sql_info);
    $stmt_info->bind_param("i", $id_usuario_sesion);
    $stmt_info->execute();
    $res_info = $stmt_info->get_result()->fetch_assoc();
    if ($res_info) {
        $id_sucursal_user = $res_info['id_sucursal'];
    }
}

// 2. PROCESAMIENTO DE ACCIONES
switch ($action) {
    case 'listar_inspecciones':
    $sql = "SELECT 
                i.id_inspeccion, i.fecha_inspeccion, 'General' as tipo_inspeccion,
                v.placa, v.modelo,
                m.nombre as marca,
                col.nombre as color_nombre, -- Traemos el nombre del color
                p.nombre as cliente_nombre,
                (SELECT COUNT(*) FROM inspeccion_detalle idet 
                 WHERE idet.id_inspeccion = i.id_inspeccion AND idet.estado = 'D') as hallazgos_criticos
            FROM inspeccion i
            INNER JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            INNER JOIN cliente c ON v.id_cliente = c.id_cliente
            INNER JOIN persona p ON c.id_persona = p.id_persona
            LEFT JOIN marca m ON v.id_marca = m.id_marca
            LEFT JOIN color col ON v.id_color = col.id_color -- JOIN con tu tabla de colores
            LEFT JOIN orden o ON i.id_inspeccion = o.id_inspeccion
            WHERE i.id_sucursal = ? AND i.estado = 'activo' AND o.id_orden IS NULL 
            ORDER BY i.fecha_inspeccion DESC";
    // ... resto del código ...

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_sucursal_user); // Vinculamos la sucursal de la sesión
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $resultado]);
    break;


    case 'obtener_detalle_inspeccion':
        $id_ins = (int)$_GET['id'];
        $sql_det = "SELECT elemento, categoria, estado FROM inspeccion_detalle 
                    WHERE id_inspeccion = ? AND estado IN ('D', 'F')";
        $stmt_det = $conexion->prepare($sql_det);
        $stmt_det->bind_param("i", $id_ins);
        $stmt_det->execute();
        $hallazgos = $stmt_det->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['success' => true, 'hallazgos' => $hallazgos]);
        break;

    case 'listar_catalogo_servicios':
    // Asegúrate de que la columna se llame 'precio' en tu tabla Tipo_Servicio
    $sql = "SELECT id_tipo_servicio, nombre, IFNULL(precio, 0) as precio_valor 
            FROM Tipo_Servicio WHERE estado = 'activo'";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
    break;

case 'buscar_repuestos_stock':
    $termino = "%".$_GET['term']."%";
    // Buscamos el artículo y su stock en las góndolas de LA SUCURSAL DEL USUARIO
    $sql = "SELECT a.id_articulo, a.nombre, a.precio_venta, a.imagen, 
                   IFNULL(SUM(i.cantidad), 0) as stock_sucursal
            FROM repuesto_articulo a
            LEFT JOIN inventario i ON a.id_articulo = i.id_articulo
            LEFT JOIN gondola g ON i.id_gondola = g.id_gondola
            LEFT JOIN almacen alm ON g.id_almacen = alm.id_almacen
            WHERE (a.nombre LIKE ? OR a.id_articulo LIKE ?)
              AND (alm.id_sucursal = ? OR alm.id_sucursal IS NULL)
            GROUP BY a.id_articulo LIMIT 10";
            
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssi", $termino, $termino, $id_sucursal_user);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    break;

   case 'guardar_orden_maestra':
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Datos no recibidos']);
        break;
    }

    try {
        $conexion->begin_transaction();

        // 1. VALIDACIÓN PREVIA DE STOCK (Evita inconsistencias antes de insertar nada)
        if (!empty($data['repuestos'])) {
            foreach ($data['repuestos'] as $r) {
                $sql_check = "SELECT SUM(i.cantidad) as stock_disponible 
                             FROM inventario i 
                             INNER JOIN gondola g ON i.id_gondola = g.id_gondola
                             INNER JOIN almacen a ON g.id_almacen = a.id_almacen
                             WHERE i.id_articulo = ? AND a.id_sucursal = ?";
                $stmt_check = $conexion->prepare($sql_check);
                $stmt_check->bind_param("ii", $r['id_art'], $id_sucursal_user);
                $stmt_check->execute();
                $res_check = $stmt_check->get_result()->fetch_assoc();
                
                if (!$res_check || $res_check['stock_disponible'] < $r['cant']) {
                    throw new Exception("Stock insuficiente para el producto ID: " . $r['id_art']);
                }
            }
        }

        // 2. INSERTAR CABECERA DE LA ORDEN
        // Estado lógico 'activo' para la fila
        $sql_orden = "INSERT INTO orden (id_inspeccion, id_sucursal, descripcion, monto_total, usuario_creacion, estado) 
                      VALUES (?, ?, ?, ?, ?, 'activo')";
        $stmt_o = $conexion->prepare($sql_orden);
        $stmt_o->bind_param("iisdi", 
            $data['id_inspeccion'], 
            $id_sucursal_user, 
            $data['descripcion'], 
            $data['monto_total'], 
            $id_usuario_sesion
        );
        $stmt_o->execute();
        $id_orden = $conexion->insert_id;

        // 3. INSERTAR ESTADO INICIAL EN EL HISTORIAL (ID 1 = 'activa')
        $id_estado_inicial = 1; 
        $sql_historial = "INSERT INTO Orden_Estado (id_orden, id_estado, usuario_creacion) VALUES (?, ?, ?)";
        $stmt_h = $conexion->prepare($sql_historial);
        $stmt_h->bind_param("iii", $id_orden, $id_estado_inicial, $id_usuario_sesion);
        $stmt_h->execute();

        // 4. INSERTAR SERVICIOS
        if (!empty($data['servicios'])) {
            $sql_serv = "INSERT INTO orden_servicio (id_orden, id_tipo_servicio, cantidad, precio_estimado) VALUES (?, ?, ?, ?)";
            $stmt_s = $conexion->prepare($sql_serv);
            foreach ($data['servicios'] as $s) {
                $stmt_s->bind_param("iiid", $id_orden, $s['id_tipo'], $s['cant'], $s['precio']);
                $stmt_s->execute();
            }
        }

        // 5. INSERTAR REPUESTOS Y REBAJAR STOCK
        if (!empty($data['repuestos'])) {
            // SQL para el detalle
            $sql_rep_ins = "INSERT INTO orden_repuesto (id_orden, id_articulo, cantidad, precio_base) VALUES (?, ?, ?, ?)";
            $stmt_r = $conexion->prepare($sql_rep_ins);

            // SQL para rebajar inventario físico de la sucursal
            $sql_update_inv = "UPDATE inventario i
                               INNER JOIN gondola g ON i.id_gondola = g.id_gondola
                               INNER JOIN almacen a ON g.id_almacen = a.id_almacen
                               SET i.cantidad = i.cantidad - ? 
                               WHERE i.id_articulo = ? AND a.id_sucursal = ?";
            $stmt_inv = $conexion->prepare($sql_update_inv);

            foreach ($data['repuestos'] as $r) {
                // A. Guardar en detalle de orden
                $stmt_r->bind_param("iiid", $id_orden, $r['id_art'], $r['cant'], $r['precio']);
                $stmt_r->execute();

                // B. Rebajar de la tabla inventario (Sucursal actual)
                $stmt_inv->bind_param("iii", $r['cant'], $r['id_art'], $id_sucursal_user);
                $stmt_inv->execute();
            }
        }

        $conexion->commit();
        echo json_encode(['success' => true, 'id_orden' => $id_orden]);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

    case 'listar_monitor_taller':
        // Buscamos órdenes activas y su estado más reciente en el historial
        $sql = "SELECT 
                    o.id_orden, o.monto_total,
                    v.placa, m.nombre as marca, v.modelo,
                    p.nombre as cliente_nombre,
                    e.nombre as nombre_proceso,
                    (SELECT COUNT(*) FROM orden_servicio WHERE id_orden = o.id_orden) as total_servicios,
                    (SELECT COUNT(*) FROM orden_repuesto WHERE id_orden = o.id_orden) as total_repuestos
                FROM orden o
                INNER JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
                INNER JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
                INNER JOIN cliente c ON v.id_cliente = c.id_cliente
                INNER JOIN persona p ON c.id_persona = p.id_persona
                LEFT JOIN marca m ON v.id_marca = m.id_marca
                INNER JOIN Orden_Estado oe ON o.id_orden = oe.id_orden
                INNER JOIN Estado e ON oe.id_estado = e.id_estado
                WHERE o.id_sucursal = ? 
                  AND o.estado = 'activo'
                  AND oe.sec_orden_estado = (SELECT MAX(sec_orden_estado) FROM Orden_Estado WHERE id_orden = o.id_orden)
                ORDER BY o.id_orden DESC";
                
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_sucursal_user);
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
        break;

    case 'obtener_detalle_orden_completo':
    $id_o = (int)$_GET['id'];
    
    // 1. Obtener Servicios de la orden
    $sql_s = "SELECT s.cantidad, s.precio_estimado AS precio, ts.nombre 
              FROM orden_servicio s 
              INNER JOIN Tipo_Servicio ts ON s.id_tipo_servicio = ts.id_tipo_servicio 
              WHERE s.id_orden = ?";
    $stmt_s = $conexion->prepare($sql_s);
    $stmt_s->bind_param("i", $id_o);
    $stmt_s->execute();
    $servicios = $stmt_s->get_result()->fetch_all(MYSQLI_ASSOC);

    // 2. Obtener Repuestos de la orden
    $sql_r = "SELECT r.cantidad, r.precio_base AS precio, ra.nombre, ra.imagen 
              FROM orden_repuesto r 
              INNER JOIN repuesto_articulo ra ON r.id_articulo = ra.id_articulo 
              WHERE r.id_orden = ?";
    $stmt_r = $conexion->prepare($sql_r);
    $stmt_r->bind_param("i", $id_o);
    $stmt_r->execute();
    $repuestos = $stmt_r->get_result()->fetch_all(MYSQLI_ASSOC);

    // 3. Obtener el total directamente de la cabecera
    $sql_t = "SELECT monto_total FROM orden WHERE id_orden = ?";
    $stmt_t = $conexion->prepare($sql_t);
    $stmt_t->bind_param("i", $id_o);
    $stmt_t->execute();
    $total = $stmt_t->get_result()->fetch_assoc()['monto_total'];

    echo json_encode([
        'success' => true, 
        'servicios' => $servicios, 
        'repuestos' => $repuestos,
        'total' => $total
    ]);
    break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}