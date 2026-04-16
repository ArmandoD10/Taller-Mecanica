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
        // 1. Fallback de seguridad: Si el usuario no tiene sucursal, asumimos la 1 (Principal)
        if ($id_sucursal_user == 0) {
            $id_sucursal_user = 1; 
        }

        // 2. Cambiamos los JOIN por LEFT JOIN para que no oculte inspecciones aunque falte un dato
        $sql = "SELECT 
                    i.id_inspeccion, DATE_FORMAT(i.fecha_inspeccion, '%d/%m/%Y %h:%i %p') as fecha_inspeccion, 
                    v.placa, v.modelo, m.nombre as marca, col.nombre as color_nombre, 
                    IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, ''))) as cliente_nombre,
                    (SELECT COUNT(*) FROM inspeccion_detalle idet WHERE idet.id_inspeccion = i.id_inspeccion AND idet.estado = 'D') as hallazgos_criticos
                FROM inspeccion i
                LEFT JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
                LEFT JOIN cliente c ON v.id_cliente = c.id_cliente
                LEFT JOIN persona p ON c.id_persona = p.id_persona
                LEFT JOIN marca m ON v.id_marca = m.id_marca
                LEFT JOIN color col ON v.id_color = col.id_color
                LEFT JOIN orden o ON i.id_inspeccion = o.id_inspeccion
                WHERE (i.id_sucursal = ? OR i.id_sucursal IS NULL) 
                  AND i.estado = 'activo' 
                  AND o.id_orden IS NULL 
                ORDER BY i.fecha_inspeccion DESC";
                
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_sucursal_user);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'sucursal_detectada' => $id_sucursal_user, // Te lo mando para debug
            'data' => $resultado
        ]);
        break;


    case 'obtener_detalle_inspeccion':
        $id_ins = (int)$_GET['id'];

        // SE AGREGÓ: El JOIN con la tabla color para que no se quede botado
        $sql_info = "SELECT 
                        v.placa, v.modelo, IFNULL(m.nombre, '') as marca,
                        IFNULL(col.nombre, 'No especificado') as color_nombre,
                        IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, ''))) as cliente_nombre
                     FROM inspeccion i
                     JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
                     JOIN cliente c ON v.id_cliente = c.id_cliente
                     JOIN persona p ON c.id_persona = p.id_persona
                     LEFT JOIN marca m ON v.id_marca = m.id_marca
                     LEFT JOIN color col ON v.id_color = col.id_color
                     WHERE i.id_inspeccion = ?";
        $stmt_info = $conexion->prepare($sql_info);
        $stmt_info->bind_param("i", $id_ins);
        $stmt_info->execute();
        $info_vehiculo = $stmt_info->get_result()->fetch_assoc();

        $sql_det = "SELECT elemento, categoria, estado FROM inspeccion_detalle 
                    WHERE id_inspeccion = ? AND estado IN ('D', 'F')";
        $stmt_det = $conexion->prepare($sql_det);
        $stmt_det->bind_param("i", $id_ins);
        $stmt_det->execute();
        $hallazgos = $stmt_det->get_result()->fetch_all(MYSQLI_ASSOC);

        $sql_trabajos = "SELECT ts.descripcion 
                         FROM inspeccion_trabajo it 
                         JOIN trabajo_solicitado ts ON it.id_trabajo = ts.id_trabajo 
                         WHERE it.id_inspeccion = ?";
        $stmt_trab = $conexion->prepare($sql_trabajos);
        $stmt_trab->bind_param("i", $id_ins);
        $stmt_trab->execute();
        $trabajos = $stmt_trab->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true, 
            'info_vehiculo' => $info_vehiculo,
            'hallazgos' => $hallazgos, 
            'trabajos' => $trabajos
        ]);
        break;

    case 'listar_catalogo_servicios':
        // Asegúrate de que la columna se llame 'precio' en tu tabla Tipo_Servicio
        $sql = "SELECT id_tipo_servicio, nombre, IFNULL(precio, 0) as precio_valor 
                FROM tipo_servicio WHERE estado = 'activo'";
        $res = $conexion->query($sql);
        if ($res) {
            echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error SQL: ' . $conexion->error]);
        }
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
            $sql_historial = "INSERT INTO orden_estado (id_orden, id_estado, usuario_creacion) VALUES (?, ?, ?)";
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
                $sql_rep_ins = "INSERT INTO orden_repuesto (id_orden, id_articulo, cantidad, precio_base) VALUES (?, ?, ?, ?)";
                $stmt_r = $conexion->prepare($sql_rep_ins);

                $sql_update_inv = "UPDATE inventario i
                                   INNER JOIN gondola g ON i.id_gondola = g.id_gondola
                                   INNER JOIN almacen a ON g.id_almacen = a.id_almacen
                                   SET i.cantidad = i.cantidad - ? 
                                   WHERE i.id_articulo = ? AND a.id_sucursal = ?";
                $stmt_inv = $conexion->prepare($sql_update_inv);

                foreach ($data['repuestos'] as $r) {
                    $stmt_r->bind_param("iiid", $id_orden, $r['id_art'], $r['cant'], $r['precio']);
                    $stmt_r->execute();

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
        $sql = "SELECT 
                    o.id_orden, o.monto_total,
                    v.placa, m.nombre as marca, v.modelo,
                    IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, ''))) as cliente_nombre,
                    e.nombre as nombre_proceso,
                    (SELECT COUNT(*) FROM orden_servicio WHERE id_orden = o.id_orden) as total_servicios,
                    (SELECT COUNT(*) FROM orden_repuesto WHERE id_orden = o.id_orden) as total_repuestos,
                    (SELECT COUNT(*) FROM asignacion_orden ao JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion WHERE ao.id_orden = o.id_orden AND ap.estado_asignacion = 'Completado') as servicios_listos,
                    (SELECT per.nombre FROM asignacion_orden ao JOIN detalle_asignacion_p dap ON ao.id_asignacion = dap.id_asignacion JOIN empleado emp ON dap.id_empleado = emp.id_empleado JOIN persona per ON emp.id_persona = per.id_persona WHERE ao.id_orden = o.id_orden LIMIT 1) as tecnico_principal
                FROM orden o
                INNER JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
                INNER JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
                INNER JOIN cliente c ON v.id_cliente = c.id_cliente
                INNER JOIN persona p ON c.id_persona = p.id_persona
                LEFT JOIN marca m ON v.id_marca = m.id_marca
                INNER JOIN orden_estado oe ON o.id_orden = oe.id_orden
                INNER JOIN estado e ON oe.id_estado = e.id_estado
                WHERE o.id_sucursal = ? 
                  AND o.estado = 'activo'
                  AND oe.sec_orden_estado = (SELECT MAX(sec_orden_estado) FROM orden_estado WHERE id_orden = o.id_orden)
                  AND e.nombre NOT IN ('Entregado', 'Listo')
                ORDER BY o.id_orden DESC";
                
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_sucursal_user);
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
        break;

    case 'obtener_detalle_orden_completo':
        $id_o = (int)$_GET['id'];
        
        $sql_s = "SELECT s.cantidad, s.precio_estimado AS precio, ts.nombre 
                  FROM orden_servicio s 
                  INNER JOIN tipo_servicio ts ON s.id_tipo_servicio = ts.id_tipo_servicio 
                  WHERE s.id_orden = ?";
        $stmt_s = $conexion->prepare($sql_s);
        $stmt_s->bind_param("i", $id_o);
        $stmt_s->execute();
        $servicios = $stmt_s->get_result()->fetch_all(MYSQLI_ASSOC);

        $sql_r = "SELECT r.cantidad, r.precio_base AS precio, ra.nombre, ra.imagen 
                  FROM orden_repuesto r 
                  INNER JOIN repuesto_articulo ra ON r.id_articulo = ra.id_articulo 
                  WHERE r.id_orden = ?";
        $stmt_r = $conexion->prepare($sql_r);
        $stmt_r->bind_param("i", $id_o);
        $stmt_r->execute();
        $repuestos = $stmt_r->get_result()->fetch_all(MYSQLI_ASSOC);

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
?>