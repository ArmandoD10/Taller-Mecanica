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

function guardar_factura_pos($conexion, $id_sucursal, $id_usuario) {
    $data = json_decode(file_get_contents("php://input"), true);
    $conexion->begin_transaction();

    try {
        if ($data['es_credito']) {
            $sqlC = "SELECT saldo_disponible FROM Credito WHERE id_credito = ? FOR UPDATE";
            $stmtC = $conexion->prepare($sqlC);
            $stmtC->bind_param("i", $data['id_credito']);
            $stmtC->execute();
            $cred = $stmtC->get_result()->fetch_assoc();

            if (!$cred || $cred['saldo_disponible'] < $data['total_final']) {
                throw new Exception("Saldo de crédito insuficiente para esta operación.");
            }
        }

        $sqlF = "INSERT INTO Factura_Central (id_cliente, id_sucursal, id_metodo, id_moneda, NCF, monto_total, referencia_azul, estado_pago, usuario_creacion, estado) 
                 VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, 'activo')";
        $id_cliente = $data['id_cliente'] ?? null;
        $estado_pago = $data['es_credito'] ? 'Pendiente' : 'Pagado';
        
        $stmtF = $conexion->prepare($sqlF);
        $stmtF->bind_param("iiisdssi", $id_cliente, $id_sucursal, $data['metodo_pago'], $data['ncf'], $data['total_final'], $data['referencia_azul'], $estado_pago, $id_usuario);
        $stmtF->execute();
        $id_factura = $conexion->insert_id;

        if ($data['es_credito']) {
            $sqlFC = "INSERT INTO Factura_Credito (id_credito, id_factura, estado) VALUES (?, ?, 'activo')";
            $stmtFC = $conexion->prepare($sqlFC);
            $stmtFC->bind_param("ii", $data['id_credito'], $id_factura);
            $stmtFC->execute();

            $sqlUpdC = "UPDATE Credito 
                        SET saldo_disponible = saldo_disponible - ?, 
                            saldo_pendiente = saldo_pendiente + ? 
                        WHERE id_credito = ?";
            $stmtUpdC = $conexion->prepare($sqlUpdC);
            $stmtUpdC->bind_param("ddi", $data['total_final'], $data['total_final'], $data['id_credito']);
            $stmtUpdC->execute();
        }

        foreach ($data['items'] as $item) {
            $sqlD = "INSERT INTO Detalle_Factura (id_factura, id_articulo, cantidad, precio, subtotal) VALUES (?, ?, ?, ?, ?)";
            $sub = $item['precio'] * $item['cantidad'];
            $stmtD = $conexion->prepare($sqlD);
            $stmtD->bind_param("iiidd", $id_factura, $item['id'], $item['cantidad'], $item['precio'], $sub);
            $stmtD->execute();

            $sqlU = "UPDATE Inventario i 
                     INNER JOIN Gondola g ON i.id_gondola = g.id_gondola 
                     INNER JOIN Almacen a ON g.id_almacen = a.id_almacen 
                     SET i.cantidad = i.cantidad - ? 
                     WHERE i.id_articulo = ? AND a.id_sucursal = ?";
            $stmtU = $conexion->prepare($sqlU);
            $stmtU->bind_param("iii", $item['cantidad'], $item['id'], $id_sucursal);
            $stmtU->execute();

            $motivo = "Venta POS #" . $id_factura;
            $sqlMov = "INSERT INTO Movimiento_Inventario (id_articulo, id_tipo_m, cantidad, motivo, fecha_creacion, estado, usuario_creacion) 
                       VALUES (?, 2, ?, ?, NOW(), 'activo', ?)";
            $stmtM = $conexion->prepare($sqlMov);
            $stmtM->bind_param("iisi", $item['id'], $item['cantidad'], $motivo, $id_usuario);
            $stmtM->execute();
        }

        foreach ($data['impuestos_ids'] as $id_imp) {
            $sqlI = "INSERT INTO Factura_Impuesto (id_factura, id_impuesto, estado) VALUES (?, ?, 'activo')";
            $stmtI = $conexion->prepare($sqlI);
            $stmtI->bind_param("ii", $id_factura, $id_imp);
            $stmtI->execute();
        }

        $conexion->commit();
        echo json_encode(['success' => true, 'id_factura' => $id_factura]);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>