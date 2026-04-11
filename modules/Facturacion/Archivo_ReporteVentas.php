<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_sucursal = $_SESSION['id_sucursal'] ?? 1;

switch ($action) {
    case 'generar_reporte':
        generar_reporte($conexion, $id_sucursal);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function generar_reporte($conexion, $id_sucursal) {
    $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $_POST['fecha_fin'] ?? date('Y-m-t');
    $tipo_filtro = $_POST['tipo_filtro'] ?? 'todas';

    // Construcción de filtros según la pestaña seleccionada
    $condicion_extra = "";
    if ($tipo_filtro === 'pos') {
        $condicion_extra = " AND fc.id_cotizacion IS NOT NULL AND c.tipo_cotizacion = 'POS' ";
    } elseif ($tipo_filtro === 'taller') {
        $condicion_extra = " AND (c.tipo_cotizacion = 'Taller' OR (fc.id_cotizacion IS NULL AND fc.estado_pago = 'Pagado')) "; 
    } elseif ($tipo_filtro === 'credito') {
        $condicion_extra = " AND fc.estado_pago = 'Pendiente' ";
    }

    try {
        /* CONCAT_WS une nombre, nombre_dos, apellido_p y apellido_m con un espacio.
           Si algún campo es NULL, lo ignora automáticamente sin romper la cadena.
        */
        $sql = "SELECT fc.id_factura, 
                       DATE_FORMAT(fc.fecha_emision, '%d/%m/%Y') as fecha, 
                       IFNULL(
                           TRIM(CONCAT_WS(' ', p.nombre, p.nombre_dos, p.apellido_p, p.apellido_m)), 
                           'Cliente Ocasional'
                       ) as cliente, 
                       fc.NCF as ncf, 
                       fc.monto_total, 
                       fc.estado_pago,
                       IF(fc.id_cotizacion IS NOT NULL, 'Venta POS', 'Orden de Servicio') as origen
                FROM factura_central fc
                LEFT JOIN cliente cl ON fc.id_cliente = cl.id_cliente
                LEFT JOIN persona p ON cl.id_persona = p.id_persona
                LEFT JOIN cotizacion c ON fc.id_cotizacion = c.id_cotizacion
                WHERE fc.id_sucursal = ? 
                  AND DATE(fc.fecha_emision) >= ? 
                  AND DATE(fc.fecha_emision) <= ?
                  $condicion_extra
                ORDER BY fc.id_factura DESC";
                
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iss", $id_sucursal, $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_all(MYSQLI_ASSOC);

        // Cálculo de totales para las tarjetas superiores
        $total_monto = 0;
        $total_facturas = count($data);
        foreach ($data as $fila) {
            $total_monto += (float)$fila['monto_total'];
        }
        
        echo json_encode([
            'success' => true, 
            'data' => $data,
            'resumen' => [
                'total_monto' => $total_monto,
                'total_facturas' => $total_facturas
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>