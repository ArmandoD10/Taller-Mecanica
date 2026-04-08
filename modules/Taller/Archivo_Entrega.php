<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_sucursal = $_SESSION['id_sucursal'] ?? 1;
$id_usuario = $_SESSION['id_usuario'] ?? 1;

switch ($action) {
    case 'listar':
        listar_entregas($conexion);
        break;
    case 'procesar_entrega':
        procesar_entrega($conexion);
        break;
    case 'procesar_calidad':
        procesar_calidad($conexion);
        break;
    case 'obtener_acta':
        obtener_acta($conexion);
        break;
    case 'listar_impuestos':
        $res = $conexion->query("SELECT id_impuesto, nombre_impuesto, porcentaje FROM Impuestos WHERE estado = 'activo'");
        echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
        break;
    case 'verificar_credito':
        verificar_credito($conexion);
        break;
    case 'simular_azul':
        simular_api_azul($conexion);
        break;
    case 'guardar_factura_orden':
        guardar_factura_orden($conexion, $id_sucursal, $id_usuario);
        break;
    case 'obtener_detalle_facturacion':
        obtener_detalle_facturacion($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function verificar_clave_admin($conexion, $username, $password_ingresada) {
    if (empty($username) || empty($password_ingresada)) return false;
    $sql = "SELECT u.password_hash FROM Usuario u JOIN Nivel n ON u.id_nivel = n.id_nivel WHERE u.username = ? AND n.nombre = 'Administrador' AND u.estado = 'activo' LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $hash = $row['password_hash'];
        if (password_verify($password_ingresada, $hash) || $password_ingresada === $hash) return true; 
    }
    return false; 
}

function listar_entregas($conexion) {
    $sql = "SELECT 
                o.id_orden, o.descripcion, 
                IFNULL(o.monto_total, 0) AS monto_total,
                CONCAT('RD$ ', FORMAT(IFNULL(o.monto_total, 0), 2)) AS monto_total_fmt,
                (SELECT e.nombre FROM Orden_Estado oe JOIN Estado e ON oe.id_estado = e.id_estado WHERE oe.id_orden = o.id_orden ORDER BY oe.fecha_creacion DESC LIMIT 1) AS estado_orden,
                CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS cliente,
                c.id_cliente,
                CONCAT(mar.nombre, ' ', IFNULL(v.modelo, ''), ' [', v.placa, ']') AS vehiculo,
                IFNULL(fc.estado_pago, 'Sin Facturar') AS estado_pago,
                DATE(o.fecha_creacion) as fecha_orden
            FROM Orden o
            JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            JOIN Vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN Marca mar ON v.id_marca = mar.id_marca
            JOIN Cliente c ON v.id_cliente = c.id_cliente
            JOIN Persona per ON c.id_persona = per.id_persona
            LEFT JOIN Factura_Central fc ON o.id_orden = fc.id_orden AND fc.estado != 'eliminado'
            WHERE o.estado != 'eliminado' 
            HAVING (estado_orden IN ('Control Calidad', 'Listo') OR (estado_orden = 'Entregado' AND fecha_orden = CURDATE()))
            ORDER BY CASE estado_orden WHEN 'Listo' THEN 1 WHEN 'Control Calidad' THEN 2 WHEN 'Entregado' THEN 3 ELSE 4 END, o.id_orden ASC";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
}

function verificar_credito($conexion) {
    $id_cliente = (int)$_GET['id_cliente'];
    
    // Leemos la nueva columna 'saldo_disponible'
    $sql = "SELECT id_credito, monto_credito, saldo_disponible 
            FROM Credito 
            WHERE id_cliente = $id_cliente AND estado_credito = 'Activo' AND estado = 'activo' LIMIT 1";
            
    $res = $conexion->query($sql);
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        echo json_encode([
            'success' => true, 
            'id_credito' => $row['id_credito'], 
            'limite' => $row['monto_credito'],
            'disponible' => $row['saldo_disponible']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Este cliente no posee una línea de crédito activa.']);
    }
}

function simular_api_azul($conexion) {
    $tarjeta = $_POST['tarjeta'] ?? '';
    $monto = $_POST['monto'] ?? 0;
    $referencia = "AZL-" . strtoupper(bin2hex(random_bytes(4)));
    $ultimos4 = substr($tarjeta, -4);
    
    $sql = "INSERT INTO Api_Azul (referencia_azul, codigo_tarjeta, monto, tipo_tarjeta, ultimos_4_digitos, estado_transaccion, codigo_autorizacion, mensaje_respuesta, estado) 
            VALUES (?, ?, ?, 'Credito', ?, 'Aprobada', 'AUTH-TALLER', 'Aprobado por Popular', 'activo')";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssds", $referencia, $tarjeta, $monto, $ultimos4);
    
    if($stmt->execute()) {
        echo json_encode(['success' => true, 'referencia' => $referencia, 'ultimos4' => $ultimos4]);
    } else {
        echo json_encode(['success' => false, 'message' => $conexion->error]);
    }
}

function guardar_factura_orden($conexion, $id_sucursal, $id_usuario) {
    $data = json_decode(file_get_contents("php://input"), true);
    $conexion->begin_transaction();

    try {
        if ($data['es_credito']) {
            // Validamos contra la nueva columna saldo_disponible
            $sqlC = "SELECT saldo_disponible FROM Credito WHERE id_credito = ? FOR UPDATE";
            $stmtC = $conexion->prepare($sqlC);
            $stmtC->bind_param("i", $data['id_credito']);
            $stmtC->execute();
            $cred = $stmtC->get_result()->fetch_assoc();

            if (!$cred || $cred['saldo_disponible'] < $data['total_final']) {
                throw new Exception("El cliente no tiene balance suficiente en su línea de crédito para cubrir RD$ " . number_format($data['total_final'], 2));
            }
        }

        $sqlF = "INSERT INTO Factura_Central (id_cliente, id_sucursal, id_orden, id_metodo, id_moneda, NCF, origen_negocio, monto_total, referencia_azul, estado_pago, usuario_creacion, estado) 
                 VALUES (?, ?, ?, ?, 1, ?, 'Taller', ?, ?, ?, ?, 'activo')";
                 
        $id_cliente = $data['id_cliente'];
        $id_orden = $data['id_orden'];
        $estado_pago = $data['es_credito'] ? 'Pendiente' : 'Pagado';
        
        $stmtF = $conexion->prepare($sqlF);
        $stmtF->bind_param("iiiisdssi", $id_cliente, $id_sucursal, $id_orden, $data['metodo_pago'], $data['ncf'], $data['total_final'], $data['referencia_azul'], $estado_pago, $id_usuario);
        $stmtF->execute();
        $id_factura = $conexion->insert_id;

        if ($data['es_credito']) {
            $sqlFC = "INSERT INTO Factura_Credito (id_credito, id_factura, estado) VALUES (?, ?, 'activo')";
            $stmtFC = $conexion->prepare($sqlFC);
            $stmtFC->bind_param("ii", $data['id_credito'], $id_factura);
            $stmtFC->execute();

            // LA MAGIA CONTABLE: Restamos del disponible y sumamos a la deuda (pendiente)
            $sqlUpdC = "UPDATE Credito 
                        SET saldo_disponible = saldo_disponible - ?, 
                            saldo_pendiente = saldo_pendiente + ? 
                        WHERE id_credito = ?";
            $stmtUpdC = $conexion->prepare($sqlUpdC);
            $stmtUpdC->bind_param("ddi", $data['total_final'], $data['total_final'], $data['id_credito']);
            $stmtUpdC->execute();
        }

        foreach ($data['impuestos_ids'] as $id_imp) {
            $sqlI = "INSERT INTO Factura_Impuesto (id_factura, id_impuesto, estado) VALUES (?, ?, 'activo')";
            $stmtI = $conexion->prepare($sqlI);
            $stmtI->bind_param("ii", $id_factura, $id_imp);
            $stmtI->execute();
        }

        $sqlUpdO = "UPDATE Orden SET monto_total = ? WHERE id_orden = ?";
        $stmtUpdO = $conexion->prepare($sqlUpdO);
        $stmtUpdO->bind_param("di", $data['total_final'], $id_orden);
        $stmtUpdO->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'id_factura' => $id_factura]);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtener_detalle_facturacion($conexion) {
    $id_orden = (int)$_GET['id_orden'];
    
    // 1. Extraer los nombres de los servicios que REALMENTE se trabajaron
    $sqlServ = "SELECT ts.nombre AS descripcion, 1 AS cantidad
                FROM asignacion_orden ao
                JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion
                JOIN Tipo_Servicio ts ON ap.id_tipo_servicio = ts.id_tipo_servicio
                WHERE ao.id_orden = $id_orden AND ap.estado != 'eliminado'
                ORDER BY ap.id_asignacion ASC";
                
    $nombres_servicios = $conexion->query($sqlServ)->fetch_all(MYSQLI_ASSOC);

    // 2. Extraer las Tarifas (Precios) reales que se asignaron en el módulo de Tiempos
    $sqlPrecios = "SELECT p.monto AS precio
                   FROM taller t
                   JOIN Precio p ON t.id_precio = p.id_precio
                   WHERE t.id_orden = $id_orden AND t.estado = 'activo'
                   ORDER BY t.id_taller ASC"; 
                   
    $resPrecios = $conexion->query($sqlPrecios);
    $tarifas_asignadas = $resPrecios ? $resPrecios->fetch_all(MYSQLI_ASSOC) : [];

    // 3. Emparejar el Servicio con su Tarifa Asignada dinámicamente
    $servicios_facturar = [];
    foreach ($nombres_servicios as $index => $serv) {
        $precio_real = isset($tarifas_asignadas[$index]) ? (float)$tarifas_asignadas[$index]['precio'] : 0;
        $servicios_facturar[] = [
            'descripcion' => $serv['descripcion'],
            'cantidad'    => 1,
            'precio'      => $precio_real,
            'subtotal'    => $precio_real
        ];
    }

    // 4. Extraer Repuestos
    $repuestos_data = [];
    try {
        $sqlRep = "SELECT ra.nombre AS descripcion, orp.cantidad, ra.precio_venta AS precio, (orp.cantidad * ra.precio_venta) AS subtotal
                   FROM Orden_Repuesto orp 
                   JOIN Repuesto_Articulo ra ON orp.id_articulo = ra.id_articulo
                   WHERE orp.id_orden = $id_orden AND orp.estado = 'activo'";
                   
        $repuestos = $conexion->query($sqlRep);
        if($repuestos) {
            $repuestos_data = $repuestos->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Exception $e) { }

    $detalle_completo = array_merge($servicios_facturar, $repuestos_data);
    echo json_encode(['success' => true, 'data' => $detalle_completo]);
}

function procesar_calidad($conexion) {
    $id_orden = $_POST['id_orden_calidad'] ?? '';
    $decision = $_POST['decision_calidad'] ?? '';
    $admin_user = $_POST['admin_username'] ?? '';
    $admin_pass = $_POST['admin_password'] ?? '';
    $usuario_sesion = $_SESSION['id_usuario'] ?? 1;

    if (!verificar_clave_admin($conexion, $admin_user, $admin_pass)) {
        echo json_encode(['success' => false, 'message' => 'Credenciales de Supervisor incorrectas.']); return;
    }

    $conexion->begin_transaction();
    try {
        $nombre_estado_nuevo = ($decision === 'Aprobado') ? 'Listo' : 'Reparación';
        $resEst = $conexion->query("SELECT id_estado FROM Estado WHERE nombre = '$nombre_estado_nuevo' LIMIT 1");
        $id_estado_nuevo = $resEst->fetch_assoc()['id_estado'];

        $stmtInsert = $conexion->prepare("INSERT INTO Orden_Estado (id_orden, id_estado, usuario_creacion) VALUES (?, ?, ?)");
        $stmtInsert->bind_param("iii", $id_orden, $id_estado_nuevo, $usuario_sesion);
        $stmtInsert->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Control de Calidad guardado exitosamente.']);
    } catch (Exception $e) {
        $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function procesar_entrega($conexion) {
    $id_orden = $_POST['id_orden_entrega'] ?? '';
    $usuario = $_SESSION['id_usuario'] ?? 1;
    $conexion->begin_transaction();
    try {
        $resEst = $conexion->query("SELECT id_estado FROM Estado WHERE nombre = 'Entregado' LIMIT 1");
        $id_estado_entregado = $resEst->fetch_assoc()['id_estado'];

        $stmtInsert = $conexion->prepare("INSERT INTO Orden_Estado (id_orden, id_estado, usuario_creacion) VALUES (?, ?, ?)");
        $stmtInsert->bind_param("iii", $id_orden, $id_estado_entregado, $usuario);
        $stmtInsert->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Entregado.']);
    } catch (Exception $e) {
        $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtener_acta($conexion) {
    $id_orden = (int)$_GET['id_orden'];
    $sql = "SELECT o.id_orden, DATE_FORMAT(o.fecha_creacion, '%d/%m/%Y %h:%i %p') AS fecha_ingreso,
            CONCAT('RD$ ', FORMAT(IFNULL(fc.monto_total, o.monto_total), 2)) AS monto_total_fmt,
            CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS cliente,
            v.placa, v.vin_chasis, CONCAT(mar.nombre, ' ', IFNULL(v.modelo, '')) AS vehiculo,
            DATE_FORMAT(oe_ent.fecha_creacion, '%d/%m/%Y %h:%i %p') AS fecha_entrega,
            IFNULL(u.username, 'Admin') AS entregado_por
            FROM Orden o
            JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            JOIN Vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN Marca mar ON v.id_marca = mar.id_marca
            JOIN Cliente c ON v.id_cliente = c.id_cliente
            JOIN Persona per ON c.id_persona = per.id_persona
            LEFT JOIN Factura_Central fc ON o.id_orden = fc.id_orden
            LEFT JOIN Orden_Estado oe_ent ON o.id_orden = oe_ent.id_orden 
            LEFT JOIN Estado e_ent ON oe_ent.id_estado = e_ent.id_estado AND e_ent.nombre = 'Entregado'
            LEFT JOIN Usuario u ON oe_ent.usuario_creacion = u.id_usuario
            WHERE o.id_orden = $id_orden ORDER BY oe_ent.fecha_creacion DESC LIMIT 1";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_assoc()]);
}
?>