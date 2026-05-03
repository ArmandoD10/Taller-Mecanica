<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_sucursal = $_SESSION['id_sucursal'] ?? 0;
// Capturamos el ID de usuario de la sesión para los movimientos de inventario
$id_usuario = $_SESSION['id_usuario'] ?? 1; 

switch ($action) {
    case 'buscar_productos':
        buscar_productos($conexion, $id_sucursal);
        break;

    case 'buscar_cliente_credito':
        buscar_cliente_credito($conexion);
        break;

    case 'listar_impuestos_automaticos':
        $res = $conexion->query("SELECT id_impuesto, nombre_impuesto, porcentaje FROM Impuestos WHERE estado = 'activo'");
        echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
        break;

    case 'simular_azul':
        simular_api_azul($conexion);
        break;

    case 'guardar_factura_pos':
        guardar_factura_pos($conexion, $id_sucursal, $id_usuario);
        break;

    case 'validar_admin':
        $user = $_POST['usuario'];
        $pass = $_POST['password'];
        // Buscamos usuario con nivel 1 (Administrador)
        $sql = "SELECT id_usuario FROM Usuario WHERE username = ? AND password_hash = ? AND id_nivel = 1 AND estado = 'activo'";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ss", $user, $pass);
        $stmt->execute();
        $res = $stmt->get_result();
        echo json_encode(['success' => $res->num_rows > 0]);
        break;

    case 'listar_ofertas_vigentes':
        $hoy = date('Y-m-d');
        // Consulta directa: Solo ofertas activas y vigentes
        $sql = "SELECT id_oferta, nombre_oferta, porciento 
                FROM Oferta 
                WHERE estado = 'activo' AND ? BETWEEN fecha_inicio AND fecha_fin";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $hoy); 
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $resultado]);
        break;

    case 'validar_stock_oferta':
        $id_art = $_GET['id_articulo'];
        $sql = "SELECT SUM(i.cantidad) as stock FROM Inventario i 
                INNER JOIN Gondola g ON i.id_gondola = g.id_gondola 
                INNER JOIN Almacen a ON g.id_almacen = a.id_almacen 
                WHERE i.id_articulo = ? AND a.id_sucursal = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ii", $id_art, $id_sucursal);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        echo json_encode(['success' => true, 'stock' => $res['stock'] ?? 0]);
        break;

    case 'cargar_datos_cotizacion':
        cargar_datos_cotizacion($conexion);
        break;

    case 'verificar_caja_abierta':
        try {
            $sql = "SELECT id_sesion FROM caja_sesion WHERE id_sucursal = ? AND estado = 'Abierta' LIMIT 1";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $id_sucursal);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'El turno está cerrado. Debe abrir la caja en el módulo de Gestión de Caja antes de procesar cobros.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function buscar_productos($conexion, $id_sucursal) {
    $term = "%" . ($_GET['term'] ?? '') . "%";
    
    $sql = "SELECT ra.id_articulo, ra.nombre, ra.precio_venta, ra.imagen, 
                   SUM(i.cantidad) as stock 
            FROM Repuesto_Articulo ra
            INNER JOIN Inventario i ON ra.id_articulo = i.id_articulo
            INNER JOIN Gondola g ON i.id_gondola = g.id_gondola
            INNER JOIN Almacen a ON g.id_almacen = a.id_almacen
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

function buscar_cliente_credito($conexion) {
    $term = "%" . ($_GET['term'] ?? '') . "%";
    $sql = "SELECT c.id_cliente, p.nombre, p.apellido_p, cr.id_credito, cr.monto_credito AS limite, cr.saldo_disponible AS disponible
            FROM Cliente c
            INNER JOIN Persona p ON c.id_persona = p.id_persona
            INNER JOIN Credito cr ON c.id_cliente = cr.id_cliente
            WHERE (p.nombre LIKE ? OR p.apellido_p LIKE ? OR p.cedula LIKE ?) 
              AND cr.estado_credito = 'Activo' AND cr.estado = 'activo'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sss", $term, $term, $term);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

function simular_api_azul($conexion) {
    $tarjeta = $_POST['tarjeta'] ?? '';
    $monto = $_POST['monto'] ?? 0;
    $referencia = "AZL-" . strtoupper(bin2hex(random_bytes(4)));
    $ultimos4 = substr($tarjeta, -4);
    
    $sql = "INSERT INTO Api_Azul (referencia_azul, codigo_tarjeta, monto, tipo_tarjeta, ultimos_4_digitos, estado_transaccion, codigo_autorizacion, mensaje_respuesta, estado) 
            VALUES (?, ?, ?, 'Credito', ?, 'Aprobada', 'AUTH-POS', 'Aprobado por Popular', 'activo')";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssds", $referencia, $tarjeta, $monto, $ultimos4);
    
    if($stmt->execute()) {
        echo json_encode(['success' => true, 'referencia' => $referencia, 'ultimos4' => $ultimos4]);
    } else {
        echo json_encode(['success' => false, 'message' => $conexion->error]);
    }
}

function cargar_datos_cotizacion($conexion) {
    $id_cotizacion = (int)($_GET['id_cotizacion'] ?? 0);
    
    $sqlCot = "SELECT c.id_cotizacion, c.nombre_cliente, c.id_cliente, 
                      IFNULL(cr.id_credito, 0) as id_credito, IFNULL(cr.saldo_disponible, 0) as disponible
               FROM cotizacion c
               LEFT JOIN Cliente cl ON c.id_cliente = cl.id_cliente
               LEFT JOIN Credito cr ON cl.id_cliente = cr.id_cliente AND cr.estado_credito = 'Activo' AND cr.estado = 'activo'
               WHERE c.id_cotizacion = ?";
    $stmtCot = $conexion->prepare($sqlCot);
    $stmtCot->bind_param("i", $id_cotizacion);
    $stmtCot->execute();
    $cotizacion = $stmtCot->get_result()->fetch_assoc();

    if (!$cotizacion) {
        echo json_encode(['success' => false, 'message' => 'Cotización no encontrada.']);
        return;
    }

    $sqlDet = "SELECT cd.id_item as id_articulo, cd.descripcion as nombre, cd.precio_unitario as precio_venta, cd.cantidad 
               FROM cotizacion_detalle cd 
               WHERE cd.id_cotizacion = ? AND cd.tipo_item = 'repuesto'";
    $stmtDet = $conexion->prepare($sqlDet);
    $stmtDet->bind_param("i", $id_cotizacion);
    $stmtDet->execute();
    $detalles = $stmtDet->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'cotizacion' => $cotizacion, 'items' => $detalles]);
}

function guardar_factura_pos($conexion, $id_sucursal, $id_usuario) {
    $data = json_decode(file_get_contents("php://input"), true);
    $conexion->begin_transaction();

    try {
        // === 0. VERIFICACIÓN DE CAJA ABIERTA ===
        $sqlCaja = "SELECT id_sesion FROM caja_sesion WHERE id_sucursal = ? AND estado = 'Abierta' LIMIT 1";
        $stmtCaja = $conexion->prepare($sqlCaja);
        $stmtCaja->bind_param("i", $id_sucursal);
        $stmtCaja->execute();
        if ($stmtCaja->get_result()->num_rows === 0) {
            throw new Exception("Operación denegada. No existe una caja abierta en esta sucursal.");
        }

        // === 1. VALIDACIÓN DE CRÉDITO Y EFECTIVO ===
        $es_credito = $data['es_credito'] ?? false;
        $metodo_pago = $data['metodo_pago'] ?? null;
        $total_final = (float)($data['total_final'] ?? 0);
        $efectivo_recibido = isset($data['efectivo_recibido']) ? (float)$data['efectivo_recibido'] : 0;

        if (!$es_credito && $metodo_pago == 1) { // 1 es Efectivo
            if ($efectivo_recibido < $total_final) {
                throw new Exception("El monto recibido es insuficiente para cubrir la factura.");
            }
        }

        if ($es_credito) {
            $sqlC = "SELECT saldo_disponible FROM Credito WHERE id_credito = ? FOR UPDATE";
            $stmtC = $conexion->prepare($sqlC);
            $stmtC->bind_param("i", $data['id_credito']);
            $stmtC->execute();
            $cred = $stmtC->get_result()->fetch_assoc();

            if (!$cred || $cred['saldo_disponible'] < $total_final) {
                throw new Exception("Saldo de crédito insuficiente para esta operación.");
            }
        }

        // === 2. INSERCIÓN DE CABECERA DE FACTURA ===[cite: 12]
        $sqlF = "INSERT INTO Factura_Central (id_cliente, id_sucursal, id_metodo, id_moneda, NCF, monto_total, referencia_azul, estado_pago, usuario_creacion, estado) 
                 VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, 'activo')";
        $id_cliente = $data['id_cliente'] ?? null;
        $estado_pago = $es_credito ? 'Pendiente' : 'Pagado';
        
        $stmtF = $conexion->prepare($sqlF);
        $stmtF->bind_param("iiisdssi", $id_cliente, $id_sucursal, $metodo_pago, $data['ncf'], $total_final, $data['referencia_azul'], $estado_pago, $id_usuario);
        $stmtF->execute();
        $id_factura = $conexion->insert_id;

        // === 3. LÓGICA DE CRÉDITO Y ACUERDO DE PAGO ===[cite: 10, 12]
        if ($es_credito) {
            // Relación Factura-Crédito[cite: 12]
            $sqlFC = "INSERT INTO Factura_Credito (id_credito, id_factura, estado) VALUES (?, ?, 'activo')";
            $stmtFC = $conexion->prepare($sqlFC);
            $stmtFC->bind_param("ii", $data['id_credito'], $id_factura);
            $stmtFC->execute();

            // Actualización de saldos[cite: 12]
            $sqlUpdC = "UPDATE Credito 
                        SET saldo_disponible = saldo_disponible - ?, 
                            saldo_pendiente = saldo_pendiente + ? 
                        WHERE id_credito = ?";
            $stmtUpdC = $conexion->prepare($sqlUpdC);
            $stmtUpdC->bind_param("ddi", $total_final, $total_final, $data['id_credito']);
            $stmtUpdC->execute();

            // Inserción de cuotas del Acuerdo de Pago[cite: 10]
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

        // === 4. DETALLE DE PRODUCTOS, INVENTARIO Y MOVIMIENTOS ===[cite: 12]
        foreach ($data['items'] as $item) {
            // Insertar Detalle[cite: 12]
            $sqlD = "INSERT INTO Detalle_Factura (id_factura, id_articulo, cantidad, precio, subtotal) VALUES (?, ?, ?, ?, ?)";
            $sub = $item['precio'] * $item['cantidad'];
            $stmtD = $conexion->prepare($sqlD);
            $stmtD->bind_param("iiidd", $id_factura, $item['id'], $item['cantidad'], $item['precio'], $sub);
            $stmtD->execute();

            // Descontar Stock por Sucursal[cite: 12]
            $sqlU = "UPDATE Inventario i 
                     INNER JOIN Gondola g ON i.id_gondola = g.id_gondola 
                     INNER JOIN Almacen a ON g.id_almacen = a.id_almacen 
                     SET i.cantidad = i.cantidad - ? 
                     WHERE i.id_articulo = ? AND a.id_sucursal = ?";
            $stmtU = $conexion->prepare($sqlU);
            $stmtU->bind_param("iii", $item['cantidad'], $item['id'], $id_sucursal);
            $stmtU->execute();

            // Registrar Movimiento de Salida[cite: 12]
            $motivo = "Venta POS #" . $id_factura;
            $sqlMov = "INSERT INTO Movimiento_Inventario (id_articulo, id_tipo_m, cantidad, motivo, fecha_creacion, estado, usuario_creacion) 
                       VALUES (?, 2, ?, ?, NOW(), 'activo', ?)"; // Tipo 2 = Salida[cite: 12]
            $stmtM = $conexion->prepare($sqlMov);
            $stmtM->bind_param("iisi", $item['id'], $item['cantidad'], $motivo, $id_usuario);
            $stmtM->execute();
        }

        // === 5. IMPUESTOS Y OFERTAS ===[cite: 12]
        if (!empty($data['impuestos_ids'])) {
            foreach ($data['impuestos_ids'] as $id_imp) {
                $conexion->query("INSERT INTO Factura_Impuesto (id_factura, id_impuesto, estado) VALUES ($id_factura, $id_imp, 'activo')");
            }
        }

        if (!empty($data['ofertas_aplicadas'])) {
            foreach ($data['ofertas_aplicadas'] as $id_o) {
                $conexion->query("INSERT INTO Oferta_Factura (id_factura, id_oferta, estado) VALUES ($id_factura, $id_o, 'activo')");
            }
        }

        $conexion->commit();
        echo json_encode(['success' => true, 'id_factura' => $id_factura]);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}