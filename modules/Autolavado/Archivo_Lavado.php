<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? 1;
$id_sucursal = $_SESSION['id_sucursal'] ?? 1;

switch ($action) {
    case 'cargar_dependencias': cargar_dependencias($conexion, $id_sucursal); break;
    case 'listar': listar_lavados($conexion, $id_sucursal); break;
    case 'registrar_lavado': registrar_lavado($conexion, $id_usuario, $id_sucursal); break;
    case 'cambiar_estado': cambiar_estado($conexion, $id_usuario); break;
    case 'facturar_express': facturar_express($conexion, $id_usuario, $id_sucursal); break;
    case 'obtener_ticket': obtener_ticket($conexion); break;
    case 'verificar_membresia': verificar_membresia($conexion); break;
    default: echo json_encode(['success' => false, 'message' => 'Acción no válida']); break;
}

function cargar_dependencias($conexion, $id_sucursal) {
    $data = [];
    $data['tipos'] = $conexion->query("SELECT id_tipo, nombre FROM tipo_lavado WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    $data['precios'] = $conexion->query("SELECT id_precio, monto FROM precio WHERE estado = 'activo' ORDER BY monto ASC")->fetch_all(MYSQLI_ASSOC);
    
    $sqlOrdenes = "SELECT o.id_orden, CONCAT(m.nombre, ' ', v.modelo, ' [', v.placa, ']') AS vehiculo, p.nombre AS cliente,
                          (SELECT e.nombre FROM orden_estado oe JOIN estado e ON oe.id_estado = e.id_estado WHERE oe.id_orden = o.id_orden ORDER BY oe.sec_orden_estado DESC LIMIT 1) AS ultimo_estado
                   FROM orden o
                   JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
                   JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
                   JOIN marca m ON v.id_marca = m.id_marca
                   JOIN cliente c ON v.id_cliente = c.id_cliente
                   JOIN persona p ON c.id_persona = p.id_persona
                   WHERE o.id_sucursal = $id_sucursal AND o.estado = 'activo'
                   AND o.id_orden NOT IN (SELECT id_orden FROM orden_lavado WHERE estado = 'activo' AND estado_lavado != 'Entregado' AND id_orden IS NOT NULL)
                   HAVING ultimo_estado = 'Listo'
                   ORDER BY o.id_orden DESC";
                   
    $data['ordenes'] = $conexion->query($sqlOrdenes)->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
}

function listar_lavados($conexion, $id_sucursal) {
    $sql = "SELECT ol.id_orden_lavado as id_lavado, IFNULL(ol.id_orden, 'Express') as id_orden, 
                   ol.nivel_suciedad, DATE_FORMAT(ol.fecha_creacion, '%h:%i %p') as fecha,
                   tl.nombre as tipo_lavado, ol.estado_lavado as estado_actual,
                   IF(ol.id_orden IS NULL, 1, 0) as es_express,
                   
                   COALESCE(
                       ol.vehiculo_ocasional,
                       CONCAT(m.nombre, ' ', v.modelo, ' [', v.placa, ']'),
                       CONCAT(mo.nombre, ' ', vo.modelo, ' [', vo.placa, ']')
                   ) AS vehiculo,
                   
                   COALESCE(
                       ol.nombre_ocasional,
                       CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')),
                       CONCAT(po.nombre, ' ', IFNULL(po.apellido_p, ''))
                   ) AS cliente

            FROM orden_lavado ol
            JOIN tipo_lavado tl ON ol.id_tipo_lavado = tl.id_tipo
            LEFT JOIN vehiculo v ON ol.vin_chasis = v.vin_chasis AND ol.placa = v.placa
            LEFT JOIN marca m ON v.id_marca = m.id_marca
            LEFT JOIN cliente c ON ol.id_cliente = c.id_cliente
            LEFT JOIN persona p ON c.id_persona = p.id_persona
            LEFT JOIN orden o ON ol.id_orden = o.id_orden
            LEFT JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            LEFT JOIN vehiculo vo ON i.id_vehiculo = vo.sec_vehiculo
            LEFT JOIN marca mo ON vo.id_marca = mo.id_marca
            LEFT JOIN cliente co ON vo.id_cliente = co.id_cliente
            LEFT JOIN persona po ON co.id_persona = po.id_persona
            
            WHERE ol.id_sucursal = $id_sucursal AND ol.estado = 'activo'
            AND ol.estado_lavado IN ('En Cola', 'En Proceso', 'Listo')
            ORDER BY ol.id_orden_lavado ASC";
            
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
}

function registrar_lavado($conexion, $id_usuario, $id_sucursal) {
    $id_orden = !empty($_POST['id_orden_taller']) ? (int)$_POST['id_orden_taller'] : null;
    $tipo_cliente_lav = $_POST['tipo_cliente_lav'] ?? 'registrado';
    $sec_vehiculo = !empty($_POST['id_vehiculo_express']) ? (int)$_POST['id_vehiculo_express'] : null;
    
    $nombre_oca = !empty($_POST['occ_nombre_lav']) ? $_POST['occ_nombre_lav'] : null;
    $vehiculo_oca = !empty($_POST['occ_vehiculo_lav']) ? $_POST['occ_vehiculo_lav'] : null;
    
    $id_tipo = (int)$_POST['id_tipo_lavado'];
    $id_precio = isset($_POST['id_precio']) ? (int)$_POST['id_precio'] : null;
    $nivel_suciedad = $_POST['nivel_suciedad'] ?? 'Medio';

    // VARIABLES DE MEMBRESÍA
    $usar_membresia = !empty($_POST['usar_membresia']) ? 1 : 0;
    $id_membresia = !empty($_POST['id_membresia_activa']) ? (int)$_POST['id_membresia_activa'] : null;

    $conexion->begin_transaction();
    try {
        $resP = $conexion->query("SELECT monto FROM precio WHERE id_precio = $id_precio");
        $monto_real = $resP->fetch_assoc()['monto'] ?? 0;

        // Si usa membresía, sale gratis y descontamos el lavado
        if ($usar_membresia && $id_membresia) {
            $monto = 0; 
            // Descontamos solo si quedan lavados (ignora los ilimitados que tienen límite_lavado = 0)
            $conexion->query("UPDATE membresia_usuario SET lavado_restantes = lavado_restantes - 1 WHERE id_membresia = $id_membresia AND lavado_restantes > 0");
        } else {
            $id_membresia = null; 
            $monto = $monto_real;
        }

        $id_cliente = null; $vin_chasis = null; $placa = null;

        if (!$id_orden && $tipo_cliente_lav === 'registrado' && $sec_vehiculo) {
            $resCli = $conexion->query("SELECT id_cliente, vin_chasis, placa FROM vehiculo WHERE sec_vehiculo = $sec_vehiculo LIMIT 1");
            if($row = $resCli->fetch_assoc()) {
                $id_cliente = $row['id_cliente']; $vin_chasis = $row['vin_chasis']; $placa = $row['placa'];
            }
        }

        $sqlLav = "INSERT INTO orden_lavado 
                   (id_sucursal, id_orden, id_cliente, vin_chasis, placa, nombre_ocasional, vehiculo_ocasional, 
                    id_tipo_lavado, id_precio, id_membresia, nivel_suciedad, monto_total, estado_lavado, estado, usuario_creacion) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'En Cola', 'activo', ?)";
                   
        $stmtLav = $conexion->prepare($sqlLav);
        $stmtLav->bind_param("iiisssssiiisdi", $id_sucursal, $id_orden, $id_cliente, $vin_chasis, $placa, $nombre_oca, $vehiculo_oca, $id_tipo, $id_precio, $id_membresia, $nivel_suciedad, $monto, $id_usuario);
        $stmtLav->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Vehículo en cola de lavado.']);
    } catch (Exception $e) {
        $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function cambiar_estado($conexion, $id_usuario) {
    $id_orden_lavado = (int)$_POST['id_lavado'];
    $nuevo_estado = $_POST['nuevo_estado']; 

    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare("UPDATE orden_lavado SET estado_lavado = ? WHERE id_orden_lavado = ?");
        $stmt->bind_param("si", $nuevo_estado, $id_orden_lavado);
        $stmt->execute();

        if ($nuevo_estado === 'Listo') {
            $resOrd = $conexion->query("SELECT id_orden FROM orden_lavado WHERE id_orden_lavado = $id_orden_lavado");
            $id_orden = $resOrd->fetch_assoc()['id_orden'] ?? null;

            if ($id_orden) {
                $resEst = $conexion->query("SELECT id_estado FROM estado WHERE nombre = 'Listo' LIMIT 1");
                if ($resEst->num_rows > 0) {
                    $id_estado_listo = $resEst->fetch_assoc()['id_estado'];
                    $sql_estado = "INSERT INTO orden_estado (id_orden, id_estado, usuario_creacion) 
                                   VALUES ($id_orden, $id_estado_listo, $id_usuario) 
                                   ON DUPLICATE KEY UPDATE fecha_creacion = CURRENT_TIMESTAMP, usuario_creacion = $id_usuario";
                    $conexion->query($sql_estado);
                }
            }
        }

        $conexion->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function facturar_express($conexion, $id_usuario, $id_sucursal) {
    $id_orden_lavado = (int)$_POST['id_lavado'];

    $conexion->begin_transaction();
    try {
        $resLav = $conexion->query("SELECT monto_total, id_cliente FROM orden_lavado WHERE id_orden_lavado = $id_orden_lavado");
        $lavado_data = $resLav->fetch_assoc();
        $monto_base = (float)$lavado_data['monto_total'];
        $id_cliente = $lavado_data['id_cliente'] ?? null;

        $itbis = $monto_base * 0.18;
        $total_final = $monto_base + $itbis;

        $sqlF = "INSERT INTO factura_lavado (id_orden_lavado, id_sucursal, id_cliente, id_metodo, NCF, monto_total, estado_pago, usuario_creacion) 
                 VALUES (?, ?, ?, 1, 'B0200000001', ?, 'Pagado', ?)";
        $stmtF = $conexion->prepare($sqlF);
        $stmtF->bind_param("iiidi", $id_orden_lavado, $id_sucursal, $id_cliente, $total_final, $id_usuario);
        $stmtF->execute();
        $id_factura = $conexion->insert_id;

        $conexion->query("UPDATE orden_lavado SET estado_lavado = 'Entregado' WHERE id_orden_lavado = $id_orden_lavado");

        $conexion->commit();
        echo json_encode(['success' => true, 'id_factura' => $id_factura]);
    } catch (Exception $e) {
        $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtener_ticket($conexion) {
    $id_factura = (int)$_GET['id_factura'];
    $sql = "SELECT fl.id_factura_lavado, fl.NCF, fl.monto_total, DATE_FORMAT(fl.fecha_creacion, '%d/%m/%Y %h:%i %p') as fecha,
                   COALESCE(p.nombre, ol.nombre_ocasional, 'Cliente al Contado') as cliente,
                   COALESCE(v.placa, ol.placa, 'N/A') as placa,
                   tl.nombre as servicio
            FROM factura_lavado fl
            JOIN orden_lavado ol ON fl.id_orden_lavado = ol.id_orden_lavado
            JOIN tipo_lavado tl ON ol.id_tipo_lavado = tl.id_tipo
            LEFT JOIN cliente cli ON fl.id_cliente = cli.id_cliente
            LEFT JOIN persona p ON cli.id_persona = p.id_persona
            LEFT JOIN vehiculo v ON ol.vin_chasis = v.vin_chasis AND ol.placa = v.placa
            WHERE fl.id_factura_lavado = $id_factura";
            
    $res = $conexion->query($sql);
    if($res && $row = $res->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else echo json_encode(['success' => false]);
}

function verificar_membresia($conexion) {
    $sec_vehiculo = (int)$_GET['sec_vehiculo'];
    $sql = "SELECT mu.id_membresia, tm.nombre as plan, mu.lavado_restantes, pm.limite_lavado 
            FROM membresia_usuario mu 
            JOIN vehiculo v ON mu.id_cliente = v.id_cliente
            JOIN plan_membresia pm ON mu.id_plan = pm.id_plan 
            JOIN tipo_membresia tm ON pm.id_tipo_membresia = tm.id_tipo_membresia
            WHERE v.sec_vehiculo = $sec_vehiculo 
            AND mu.estado = 'activo' 
            AND mu.fecha_vencimiento >= CURDATE() 
            AND (mu.lavado_restantes > 0 OR pm.limite_lavado = 0)
            LIMIT 1";
    $res = $conexion->query($sql);
    if($res && $row = $res->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => true, 'data' => null]);
    }
}
?>