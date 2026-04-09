<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_sucursal = $_SESSION['id_sucursal'] ?? 1;

switch ($action) {
    case 'listar_facturas':
        listar_facturas($conexion, $id_sucursal);
        break;
    case 'obtener_detalle':
        obtener_detalle($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function listar_facturas($conexion, $id_sucursal) {
    $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $_POST['fecha_fin'] ?? date('Y-m-t');

    try {
        $sql = "SELECT f.id_factura, 
                       IFNULL(f.NCF, 'SIN NCF') as ncf, 
                       DATE_FORMAT(f.fecha_emision, '%d/%m/%Y %h:%i %p') as fecha,
                       IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, ''))) as cliente,
                       IFNULL(CONCAT(m.nombre, ' ', IFNULL(v.modelo, ''), ' [', v.placa, ']'), 'Vehículo no especificado') as vehiculo,
                       f.monto_total, 
                       f.estado_pago,
                       f.estado
                FROM factura_central f
                LEFT JOIN cliente c ON f.id_cliente = c.id_cliente
                LEFT JOIN persona p ON c.id_persona = p.id_persona
                LEFT JOIN orden o ON f.id_orden = o.id_orden
                LEFT JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
                LEFT JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
                LEFT JOIN marca m ON v.id_marca = m.id_marca
                WHERE f.id_sucursal = ? 
                  AND DATE(f.fecha_emision) >= ? 
                  AND DATE(f.fecha_emision) <= ?
                ORDER BY f.id_factura DESC";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iss", $id_sucursal, $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $res = $stmt->get_result();
        
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function obtener_detalle($conexion) {
    $id_factura = (int)($_GET['id_factura'] ?? 0);
    $detalles = [];
    
    try {
        // 1er INTENTO: Buscar en detalle_factura
        $sql1 = "SELECT df.cantidad, df.precio, df.subtotal, ra.nombre as descripcion 
                 FROM detalle_factura df 
                 JOIN repuesto_articulo ra ON df.id_articulo = ra.id_articulo 
                 WHERE df.id_factura = $id_factura";
        $res1 = $conexion->query($sql1);
        
        if($res1 && $res1->num_rows > 0) {
            while($row = $res1->fetch_assoc()) {
                $detalles[] = $row;
            }
        } else {
            // 2do INTENTO: Buscar el origen de la factura (Orden o Cotización)
            $sqlOrigen = "SELECT id_orden, id_cotizacion FROM factura_central WHERE id_factura = $id_factura";
            $resOrigen = $conexion->query($sqlOrigen);
            
            if($resOrigen && $resOrigen->num_rows > 0) {
                $origen = $resOrigen->fetch_assoc();
                
                if(!empty($origen['id_orden'])) {
                    $id_orden = $origen['id_orden'];
                    
                    // Extraer los SERVICIOS de la orden
                    $sqlS = "SELECT IFNULL(os.cantidad, 1) as cantidad, 
                                    IFNULL(os.precio_estimado, ts.precio) as precio, 
                                    (IFNULL(os.cantidad, 1) * IFNULL(os.precio_estimado, ts.precio)) as subtotal, 
                                    ts.nombre as descripcion 
                             FROM orden_servicio os 
                             JOIN tipo_servicio ts ON os.id_tipo_servicio = ts.id_tipo_servicio 
                             WHERE os.id_orden = $id_orden AND os.estado != 'eliminado'";
                    $resS = $conexion->query($sqlS);
                    if($resS) { while($row = $resS->fetch_assoc()) { $detalles[] = $row; } }
                    
                    // Extraer los REPUESTOS de la orden
                    $sqlR = "SELECT IFNULL(orp.cantidad, 1) as cantidad, 
                                    orp.precio_base as precio, 
                                    IFNULL(orp.sub_total, (IFNULL(orp.cantidad, 1) * orp.precio_base)) as subtotal, 
                                    ra.nombre as descripcion 
                             FROM orden_repuesto orp 
                             JOIN repuesto_articulo ra ON orp.id_articulo = ra.id_articulo 
                             WHERE orp.id_orden = $id_orden AND orp.estado != 'eliminado'";
                    $resR = $conexion->query($sqlR);
                    if($resR) { while($row = $resR->fetch_assoc()) { $detalles[] = $row; } }
                    
                } else if(!empty($origen['id_cotizacion'])) {
                    $id_cotizacion = $origen['id_cotizacion'];
                    
                    // Extraer directamente de la COTIZACIÓN
                    $sqlC = "SELECT cantidad, precio_unitario as precio, subtotal, descripcion 
                             FROM cotizacion_detalle 
                             WHERE id_cotizacion = $id_cotizacion";
                    $resC = $conexion->query($sqlC);
                    if($resC) { while($row = $resC->fetch_assoc()) { $detalles[] = $row; } }
                }
            }
        }
        
        echo json_encode(['success' => true, 'data' => $detalles]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>