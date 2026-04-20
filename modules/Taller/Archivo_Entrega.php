<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$action = $_GET['action'] ?? '';
$id_sucursal = (!empty($_SESSION['id_sucursal']) && $_SESSION['id_sucursal'] != 0) ? $_SESSION['id_sucursal'] : 1;
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
    case 'obtener_servicios_calidad':
        obtener_servicios_calidad($conexion);
        break;
    case 'obtener_acta':
        obtener_acta($conexion);
        break;
    case 'listar_impuestos':
        $res = $conexion->query("SELECT id_impuesto, nombre_impuesto, porcentaje FROM impuestos WHERE estado = 'activo'");
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
    case 'buscar_productos':
        buscar_productos($conexion, $id_sucursal);
        break;
    case 'validar_admin':
        validar_acceso_admin($conexion);
        break;
    case 'listar_ofertas_vigentes':
        listar_ofertas_vigentes($conexion);
        break;

    // ==========================================
    // NUEVOS MÉTODOS PARA EL MÓDULO DE GARANTÍAS
    // ==========================================
    case 'obtener_catalogo_politicas':
        try {
            $sql = "SELECT id_politica, nombre, tiempo_cobertura, unidad_tiempo, kilometraje_cobertura 
                    FROM politica_garantia WHERE estado = 'activo'";
            $res = $conexion->query($sql);
            echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'obtener_items_para_garantia':
        try {
            $id_orden = (int)$_GET['id_orden'];
            
            // 1. Obtener Servicios de la Orden
            $sqlServ = "SELECT os.sec_serv as id, ts.nombre as descripcion, 'servicio' as tipo 
                        FROM orden_servicio os 
                        JOIN tipo_servicio ts ON os.id_tipo_servicio = ts.id_tipo_servicio 
                        WHERE os.id_orden = $id_orden AND os.estado = 'activo'";
            $servicios = $conexion->query($sqlServ)->fetch_all(MYSQLI_ASSOC);

            // 2. Obtener Repuestos de la Orden
            $sqlRep = "SELECT orp.sec_detalle as id, ra.nombre as descripcion, 'repuesto' as tipo 
                       FROM orden_repuesto orp 
                       JOIN repuesto_articulo ra ON orp.id_articulo = ra.id_articulo 
                       WHERE orp.id_orden = $id_orden AND orp.estado = 'activo'";
            $repuestos = $conexion->query($sqlRep)->fetch_all(MYSQLI_ASSOC);

            // 3. Unir todo
            $items = array_merge($servicios, $repuestos);
            
            echo json_encode(['success' => true, 'data' => $items]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

// ... [TUS OTRAS FUNCIONES MANTIENEN SU CÓDIGO EXACTAMENTE IGUAL HASTA LLEGAR A PROCESAR_ENTREGA] ...
function verificar_clave_admin($conexion, $username, $password_ingresada) {
    if (empty($username) || empty($password_ingresada)) return false;
    $sql = "SELECT u.password_hash FROM usuario u JOIN nivel n ON u.id_nivel = n.id_nivel WHERE u.username = ? AND n.nombre = 'Administrador' AND u.estado = 'activo' LIMIT 1";
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

function validar_acceso_admin($conexion) {
    $user = $_POST['usuario'] ?? '';
    $pass = $_POST['password'] ?? '';
    if (verificar_clave_admin($conexion, $user, $pass)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas o usuario no es Administrador.']);
    }
}

function listar_ofertas_vigentes($conexion) {
    $sql = "SELECT id_oferta, nombre_oferta, porciento 
            FROM oferta 
            WHERE estado = 'activo' AND CURDATE() BETWEEN fecha_inicio AND fecha_fin";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
}

function listar_entregas($conexion) {
    $sql = "SELECT 
                o.id_orden, o.descripcion, 
                IFNULL(o.monto_total, 0) AS monto_total,
                CONCAT('RD$ ', FORMAT(IFNULL(o.monto_total, 0), 2)) AS monto_total_fmt,
                (SELECT e.nombre FROM orden_estado oe JOIN estado e ON oe.id_estado = e.id_estado WHERE oe.id_orden = o.id_orden ORDER BY oe.fecha_creacion DESC LIMIT 1) AS estado_orden,
                CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS cliente,
                c.id_cliente,
                CONCAT(mar.nombre, ' ', IFNULL(v.modelo, ''), ' [', v.placa, ']') AS vehiculo,
                IFNULL(fc.estado_pago, 'Sin Facturar') AS estado_pago,
                DATE(o.fecha_creacion) as fecha_orden
            FROM orden o
            JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN marca mar ON v.id_marca = mar.id_marca
            JOIN cliente c ON v.id_cliente = c.id_cliente
            JOIN persona per ON c.id_persona = per.id_persona
            LEFT JOIN factura_central fc ON o.id_orden = fc.id_orden AND fc.estado != 'eliminado'
            WHERE o.estado != 'eliminado' 
            HAVING (estado_orden IN ('Control Calidad', 'Listo') OR (estado_orden = 'Entregado' AND fecha_orden = CURDATE()))
            ORDER BY CASE estado_orden WHEN 'Listo' THEN 1 WHEN 'Control Calidad' THEN 2 WHEN 'Entregado' THEN 3 ELSE 4 END, o.id_orden ASC";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
}

function buscar_productos($conexion, $id_sucursal) {
    $term = "%" . ($_GET['term'] ?? '') . "%";
    $sql = "SELECT ra.id_articulo, ra.nombre, ra.precio_venta, ra.imagen, 
                   SUM(i.cantidad) as stock 
            FROM repuesto_articulo ra
            INNER JOIN inventario i ON ra.id_articulo = i.id_articulo
            INNER JOIN gondola g ON i.id_gondola = g.id_gondola
            INNER JOIN almacen a ON g.id_almacen = a.id_almacen
            WHERE (ra.nombre LIKE ? OR ra.num_serie LIKE ?) 
              AND a.id_sucursal = ? 
              AND ra.estado = 'activo'
            GROUP BY ra.id_articulo, ra.nombre, ra.precio_venta, ra.imagen
            HAVING stock > 0"; 
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssi", $term, $term, $id_sucursal);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

function verificar_credito($conexion) {
    $id_cliente = (int)$_GET['id_cliente'];
    $sql = "SELECT id_credito, monto_credito, saldo_disponible 
            FROM credito 
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
    $sql = "INSERT INTO api_azul (referencia_azul, codigo_tarjeta, monto, tipo_tarjeta, ultimos_4_digitos, estado_transaccion, codigo_autorizacion, mensaje_respuesta, estado) 
            VALUES (?, ?, ?, 'Credito', ?, 'Aprobada', 'AUTH-TALLER', 'Aprobado por Popular', 'activo')";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssds", $referencia, $tarjeta, $monto, $ultimos4);
    if($stmt->execute()) {
        echo json_encode(['success' => true, 'referencia' => $referencia, 'ultimos4' => $ultimos4]);
    } else {
        echo json_encode(['success' => false, 'message' => $conexion->error]);
    }
}

function guardar_factura_orden($conexion, $id_sucursal_sesion, $id_usuario) {
    $data = json_decode(file_get_contents("php://input"), true);
    $conexion->begin_transaction();

    try {
        $id_cliente = (int)$data['id_cliente'];
        $id_orden = (int)$data['id_orden'];
        
        $qSucursal = $conexion->query("SELECT id_sucursal FROM orden WHERE id_orden = $id_orden LIMIT 1");
        $id_sucursal_real = ($qSucursal && $rSuc = $qSucursal->fetch_assoc()) ? (int)$rSuc['id_sucursal'] : $id_sucursal_sesion;

        // 1. VALIDACIÓN DE CRÉDITO EXISTENTE (Si aplica)
        if ($data['es_credito']) {
            $sqlC = "SELECT id_credito, saldo_disponible FROM credito WHERE id_cliente = ? AND estado = 'activo' LIMIT 1 FOR UPDATE";
            $stmtC = $conexion->prepare($sqlC);
            $stmtC->bind_param("i", $id_cliente);
            $stmtC->execute();
            $cred = $stmtC->get_result()->fetch_assoc();

            if (!$cred || $cred['saldo_disponible'] < $data['total_final']) {
                throw new Exception("El cliente no tiene línea de crédito activa o balance suficiente.");
            }
            $id_credito = $cred['id_credito'];
        }

        // 2. INSERTAR FACTURA CENTRAL
        $sqlF = "INSERT INTO factura_central (id_cliente, id_sucursal, id_orden, id_metodo, id_moneda, NCF, origen_negocio, monto_total, referencia_azul, estado_pago, usuario_creacion, estado) 
                 VALUES (?, ?, ?, ?, 1, ?, 'Taller', ?, ?, ?, ?, 'activo')";
                 
        $estado_pago = $data['es_credito'] ? 'Pendiente' : 'Pagado';
        
        $stmtF = $conexion->prepare($sqlF);
        $stmtF->bind_param("iiiisdssi", $id_cliente, $id_sucursal_real, $id_orden, $data['metodo_pago'], $data['ncf'], $data['total_final'], $data['referencia_azul'], $estado_pago, $id_usuario);
        $stmtF->execute();
        $id_factura = $conexion->insert_id;

        // 3. LÓGICA DE CRÉDITO Y ACUERDO DE PAGO
        if ($data['es_credito']) {
            // Vincular Factura con Crédito
            $sqlFC = "INSERT INTO factura_credito (id_credito, id_factura, estado) VALUES (?, ?, 'activo')";
            $stmtFC = $conexion->prepare($sqlFC);
            $stmtFC->bind_param("ii", $id_credito, $id_factura);
            $stmtFC->execute();

            // Actualizar Saldo de la Línea de Crédito
            $sqlUpdC = "UPDATE credito 
                        SET saldo_disponible = saldo_disponible - ?, 
                            saldo_pendiente = saldo_pendiente + ? 
                        WHERE id_credito = ?";
            $stmtUpdC = $conexion->prepare($sqlUpdC);
            $stmtUpdC->bind_param("ddi", $data['total_final'], $data['total_final'], $id_credito);
            $stmtUpdC->execute();

            // INSERTAR ACUERDO DE PAGO (LAS CUOTAS)
            if (!empty($data['acuerdo_pago'])) {
                $sqlCuota = "INSERT INTO Acuerdo_Pago_Cuotas (id_factura, numero_cuota, monto_cuota, fecha_programada, usuario_creacion) 
                             VALUES (?, ?, ?, ?, ?)";
                $stmtCuota = $conexion->prepare($sqlCuota);
                
                foreach ($data['acuerdo_pago'] as $cuota) {
                    $stmtCuota->bind_param("iidsi", 
                        $id_factura, 
                        $cuota['nro'], 
                        $cuota['monto'], 
                        $cuota['fecha'], 
                        $id_usuario
                    );
                    $stmtCuota->execute();
                }
            }
        }

        // 4. PROCESAR IMPUESTOS, OFERTAS Y REPUESTOS (Mantenemos tu lógica igual)
        if (!empty($data['ofertas_ids'])) {
            foreach ($data['ofertas_ids'] as $id_oferta) {
                $conexion->query("INSERT INTO factura_oferta (id_factura, id_oferta, estado) VALUES ($id_factura, $id_oferta, 'activo')");
            }
        }

        if (!empty($data['repuestos_extra'])) {
            foreach ($data['repuestos_extra'] as $item) {
                $sub = $item['precio'] * $item['cantidad'];
                $stmtR = $conexion->prepare("INSERT INTO orden_repuesto (id_orden, id_articulo, cantidad, precio_base, sub_total, estado) VALUES (?, ?, ?, ?, ?, 'activo')");
                $stmtR->bind_param("iiidd", $id_orden, $item['id'], $item['cantidad'], $item['precio'], $sub);
                $stmtR->execute();

                $conexion->query("UPDATE inventario i 
                                 INNER JOIN gondola g ON i.id_gondola = g.id_gondola 
                                 INNER JOIN almacen a ON g.id_almacen = a.id_almacen 
                                 SET i.cantidad = i.cantidad - {$item['cantidad']} 
                                 WHERE i.id_articulo = {$item['id']} AND a.id_sucursal = $id_sucursal_real");
            }
        }

        foreach ($data['impuestos_ids'] as $id_imp) {
            $conexion->query("INSERT INTO factura_impuesto (id_factura, id_impuesto, estado) VALUES ($id_factura, $id_imp, 'activo')");
        }

        $conexion->query("UPDATE orden SET monto_total = {$data['total_final']} WHERE id_orden = $id_orden");
        
        $conexion->commit();
        echo json_encode(['success' => true, 'id_factura' => $id_factura]);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtener_detalle_facturacion($conexion) {
    $id_orden = (int)$_GET['id_orden'];
    
    $sqlServ = "SELECT os.id_tipo_servicio, ts.nombre AS descripcion, 1 AS cantidad, os.precio_estimado AS precio
                FROM orden_servicio os
                JOIN tipo_servicio ts ON os.id_tipo_servicio = ts.id_tipo_servicio
                WHERE os.id_orden = $id_orden AND os.estado = 'activo'";
                
    $servicios_db = $conexion->query($sqlServ)->fetch_all(MYSQLI_ASSOC);

    $servicios_facturar = [];
    foreach ($servicios_db as $serv) {
        $precio_real = (float)$serv['precio'];
        $servicios_facturar[] = [
            'id'          => 0,
            'descripcion' => $serv['descripcion'],
            'cantidad'    => 1,
            'precio'      => $precio_real,
            'subtotal'    => $precio_real,
            'es_extra'    => false
        ];
    }
    
    $sqlLav = "SELECT ol.id_orden_lavado, tl.nombre AS descripcion, ol.monto_total AS precio
               FROM orden_lavado ol
               JOIN tipo_lavado tl ON ol.id_tipo_lavado = tl.id_tipo
               WHERE ol.id_orden = $id_orden AND ol.estado = 'activo' AND ol.estado_lavado != 'Entregado'";
               
    $lavado_db = $conexion->query($sqlLav);
    if ($lavado_db) {
        $lavados = $lavado_db->fetch_all(MYSQLI_ASSOC);
        foreach ($lavados as $lav) {
            $precio_real = (float)$lav['precio'];
            $servicios_facturar[] = [
                'id'          => 0,
                'descripcion' => "Servicio de Autolavado: " . $lav['descripcion'],
                'cantidad'    => 1,
                'precio'      => $precio_real,
                'subtotal'    => $precio_real,
                'es_extra'    => false
            ];
        }
    }

    $repuestos_data = [];
    try {
        $sqlRep = "SELECT ra.id_articulo as id, ra.nombre AS descripcion, orp.cantidad, ra.precio_venta AS precio, (orp.cantidad * ra.precio_venta) AS subtotal, false as es_extra
                   FROM orden_repuesto orp 
                   JOIN repuesto_articulo ra ON orp.id_articulo = ra.id_articulo
                   WHERE orp.id_orden = $id_orden AND orp.estado = 'activo'";
                   
        $repuestos = $conexion->query($sqlRep);
        if($repuestos) {
            $repuestos_data = $repuestos->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Exception $e) { }

    $detalle_completo = array_merge($servicios_facturar, $repuestos_data);
    echo json_encode(['success' => true, 'data' => $detalle_completo]);
}

function obtener_servicios_calidad($conexion) {
    $id_orden = (int)($_GET['id_orden'] ?? 0);
    $sql = "SELECT ap.id_asignacion, ts.nombre 
            FROM asignacion_personal ap 
            JOIN asignacion_orden ao ON ap.id_asignacion = ao.id_asignacion 
            JOIN tipo_servicio ts ON ap.id_tipo_servicio = ts.id_tipo_servicio 
            WHERE ao.id_orden = $id_orden AND ap.estado_asignacion = 'Completado' AND ap.estado = 'activo'";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
}

function procesar_calidad($conexion) {
    $id_orden = $_POST['id_orden_calidad'] ?? '';
    $decision = $_POST['decision_calidad'] ?? '';
    $admin_user = $_POST['admin_username'] ?? '';
    $admin_pass = $_POST['admin_password'] ?? '';
    $servicios_rechazados = isset($_POST['servicios_rechazados']) ? json_decode($_POST['servicios_rechazados'], true) : [];
    $usuario_sesion = $_SESSION['id_usuario'] ?? 1;

    if (!verificar_clave_admin($conexion, $admin_user, $admin_pass)) {
        echo json_encode(['success' => false, 'message' => 'Credenciales de Supervisor incorrectas.']); return;
    }

    $conexion->begin_transaction();
    try {
        $nombre_estado_nuevo = ($decision === 'Aprobado') ? 'Listo' : 'Reparación';
        
        $resEst = $conexion->query("SELECT id_estado FROM estado WHERE nombre = '$nombre_estado_nuevo' LIMIT 1");
        $rowEst = $resEst ? $resEst->fetch_assoc() : null;

        if (!$rowEst && $decision === 'Rechazado') {
            $resEstAlt = $conexion->query("SELECT id_estado FROM estado WHERE nombre IN ('En Proceso', 'Proceso', 'En Reparación', 'Revisión') LIMIT 1");
            $rowEst = $resEstAlt ? $resEstAlt->fetch_assoc() : null;
        }

        if (!$rowEst) {
            throw new Exception("No existe el estado '$nombre_estado_nuevo' en la base de datos.");
        }

        $id_estado_nuevo = $rowEst['id_estado'];

        $checkEst = $conexion->query("SELECT 1 FROM orden_estado WHERE id_orden = $id_orden AND id_estado = $id_estado_nuevo");
        if($checkEst->num_rows > 0){
            $conexion->query("UPDATE orden_estado SET fecha_creacion = CURRENT_TIMESTAMP, usuario_creacion = $usuario_sesion WHERE id_orden = $id_orden AND id_estado = $id_estado_nuevo");
        } else {
            $conexion->query("INSERT INTO orden_estado (id_orden, id_estado, usuario_creacion) VALUES ($id_orden, $id_estado_nuevo, $usuario_sesion)");
        }

        if ($decision === 'Rechazado') {
            if (empty($servicios_rechazados)) {
                throw new Exception("Debe especificar qué servicios serán devueltos a reparación.");
            }
            foreach ($servicios_rechazados as $id_asig) {
                $id_asig = (int)$id_asig;
                $conexion->query("UPDATE asignacion_personal SET estado_asignacion = 'Pendiente' WHERE id_asignacion = $id_asig");
            }
        }

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Veredicto de Calidad guardado exitosamente.']);
    } catch (Exception $e) {
        $conexion->rollback(); 
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ==========================================
// FUNCIÓN PROCESAR ENTREGA (NUEVA LÓGICA DE GARANTÍA POR LÍNEA)
// ==========================================
function procesar_entrega($conexion) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $id_orden = $data['id_orden_entrega'] ?? 0;
    $usuario = $_SESSION['id_usuario'] ?? 1;
    $garantias_asignadas = $data['garantias_asignadas'] ?? []; 
    $hay_garantias_asignadas = false;

    $conexion->begin_transaction();
    try {
        // 1. Cambiar estado de la Orden a 'Entregado'
        $resEst = $conexion->query("SELECT id_estado FROM estado WHERE nombre = 'Entregado' LIMIT 1");
        if(!$resEst || $resEst->num_rows == 0) throw new Exception("Estado 'Entregado' no encontrado en el sistema.");
        $id_estado_entregado = $resEst->fetch_assoc()['id_estado'];

        $checkEst = $conexion->query("SELECT 1 FROM orden_estado WHERE id_orden = $id_orden AND id_estado = $id_estado_entregado");
        if($checkEst->num_rows > 0){
            $conexion->query("UPDATE orden_estado SET fecha_creacion = CURRENT_TIMESTAMP, usuario_creacion = $usuario WHERE id_orden = $id_orden AND id_estado = $id_estado_entregado");
        } else {
            $conexion->query("INSERT INTO orden_estado (id_orden, id_estado, usuario_creacion) VALUES ($id_orden, $id_estado_entregado, $usuario)");
        }

        // 2. Procesar Garantías Individuales (Iterar sobre los selects de la vista)
        if (!empty($garantias_asignadas)) {
            
            // Preparar Sentencias UPDATE
            $stmtUpdServ = $conexion->prepare("UPDATE orden_servicio SET id_politica = ?, fecha_vencimiento = ?, kilometraje_vencimiento = ? WHERE sec_serv = ?");
            $stmtUpdRep = $conexion->prepare("UPDATE orden_repuesto SET id_politica = ?, fecha_vencimiento = ?, kilometraje_vencimiento = ? WHERE sec_detalle = ?");

            foreach ($garantias_asignadas as $g) {
                // $g['id_politica'] viene del SELECT. Si es vacío (0 o ""), no aplica garantía.
                if (empty($g['id_politica'])) continue;
                
                $id_politica = (int)$g['id_politica'];
                $id_linea = (int)$g['id_linea'];
                $tipo_linea = $g['tipo_linea']; // 'servicio' o 'repuesto'
                
                // Calcular Fechas y KM de Vencimiento usando MySQL
                $sqlCalc = "SELECT 
                                CASE unidad_tiempo 
                                    WHEN 'Dias' THEN DATE_ADD(CURDATE(), INTERVAL tiempo_cobertura DAY)
                                    WHEN 'Meses' THEN DATE_ADD(CURDATE(), INTERVAL tiempo_cobertura MONTH)
                                    WHEN 'Anios' THEN DATE_ADD(CURDATE(), INTERVAL tiempo_cobertura YEAR)
                                END as fecha_ven,
                                kilometraje_cobertura
                            FROM politica_garantia WHERE id_politica = ?";
                $stmtCalc = $conexion->prepare($sqlCalc);
                $stmtCalc->bind_param("i", $id_politica);
                $stmtCalc->execute();
                $resCalc = $stmtCalc->get_result()->fetch_assoc();

                $fecha_ven = $resCalc['fecha_ven'];
                $km_cobertura = $resCalc['kilometraje_cobertura'];

                // Necesitamos el KM actual del vehículo para sumarle la cobertura
                $km_vencimiento = null;
                if ($km_cobertura !== null) {
                    $sqlKm = "SELECT i.kilometraje_recepcion FROM orden o JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion WHERE o.id_orden = $id_orden";
                    $resKm = $conexion->query($sqlKm)->fetch_assoc();
                    if ($resKm) {
                        $km_vencimiento = (int)$resKm['kilometraje_recepcion'] + (int)$km_cobertura;
                    }
                }

                // Guardar en su tabla correspondiente
                if ($tipo_linea === 'servicio') {
                    $stmtUpdServ->bind_param("isii", $id_politica, $fecha_ven, $km_vencimiento, $id_linea);
                    $stmtUpdServ->execute();
                    $hay_garantias_asignadas = true;
                } else if ($tipo_linea === 'repuesto') {
                    $stmtUpdRep->bind_param("isii", $id_politica, $fecha_ven, $km_vencimiento, $id_linea);
                    $stmtUpdRep->execute();
                    $hay_garantias_asignadas = true;
                }
            }
        }

        // 3. Crear el Documento de Certificado (Si al menos 1 línea tuvo garantía)
        $generar_certificado = false;
        if ($hay_garantias_asignadas) {
            $generar_certificado = true;
            
            // Extraer ID del vehiculo para el certificado
            $sql_veh = "SELECT i.id_vehiculo FROM orden o JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion WHERE o.id_orden = ?";
            $stmt_veh = $conexion->prepare($sql_veh);
            $stmt_veh->bind_param("i", $id_orden);
            $stmt_veh->execute();
            $id_vehiculo = $stmt_veh->get_result()->fetch_assoc()['id_vehiculo'];

            $codigo_certificado = "GAR-" . $id_orden . "-" . strtoupper(substr(uniqid(), -3));
            
            // Insertar certificado maestro (ahora las fechas están en las líneas)
            $sql_gar = "INSERT INTO garantia_servicio (id_orden, id_vehiculo, codigo_certificado, fecha_vencimiento, estado, usuario_creacion) 
                        VALUES (?, ?, ?, CURDATE(), 'activo', ?)"; // fecha_vencimiento_general es dummy, importa la línea
            
            $stmt_gar = $conexion->prepare($sql_gar);
            $stmt_gar->bind_param("iisi", $id_orden, $id_vehiculo, $codigo_certificado, $usuario);
            $stmt_gar->execute();
        }

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Vehículo entregado exitosamente.', 'generar_certificado' => $generar_certificado]);
    } catch (Exception $e) {
        $conexion->rollback(); 
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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
            FROM orden o
            JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN marca mar ON v.id_marca = mar.id_marca
            JOIN cliente c ON v.id_cliente = c.id_cliente
            JOIN persona per ON c.id_persona = per.id_persona
            LEFT JOIN factura_central fc ON o.id_orden = fc.id_orden
            LEFT JOIN orden_estado oe_ent ON o.id_orden = oe_ent.id_orden 
            LEFT JOIN estado e_ent ON oe_ent.id_estado = e_ent.id_estado AND e_ent.nombre = 'Entregado'
            LEFT JOIN usuario u ON oe_ent.usuario_creacion = u.id_usuario
            WHERE o.id_orden = $id_orden ORDER BY oe_ent.fecha_creacion DESC LIMIT 1";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_assoc()]);
}
?>