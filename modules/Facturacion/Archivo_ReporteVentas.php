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
    $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes por defecto
    $fecha_fin = $_POST['fecha_fin'] ?? date('Y-m-t');        // Último día del mes por defecto

    // Validar fechas
    if (empty($fecha_inicio) || empty($fecha_fin)) {
        echo json_encode(['success' => false, 'message' => 'Debe proporcionar un rango de fechas válido.']);
        return;
    }

    try {
        $sql = "SELECT f.id_factura, 
                       IFNULL(f.NCF, 'SIN NCF') as ncf, 
                       DATE_FORMAT(f.fecha_emision, '%d/%m/%Y %h:%i %p') as fecha,
                       IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, ''))) as cliente,
                       IFNULL(p.cedula, 'N/A') as rnc_cedula, 
                       f.monto_total, 
                       f.estado, 
                       f.estado_pago
                FROM factura_central f
                LEFT JOIN cliente c ON f.id_cliente = c.id_cliente
                LEFT JOIN persona p ON c.id_persona = p.id_persona
                WHERE f.id_sucursal = ? 
                  AND DATE(f.fecha_emision) >= ? 
                  AND DATE(f.fecha_emision) <= ?
                  AND f.estado != 'eliminado'
                ORDER BY f.fecha_emision ASC";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iss", $id_sucursal, $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $datos = $res->fetch_all(MYSQLI_ASSOC);
        
        // Calcular totales en el backend
        $total_ventas = 0;
        $cantidad_facturas = 0;
        
        foreach($datos as $fila) {
            // Solo sumamos las facturas que no estén canceladas
            if($fila['estado'] !== 'inactivo' && $fila['estado_pago'] !== 'Cancelado') {
                $total_ventas += (float)$fila['monto_total'];
                $cantidad_facturas++;
            }
        }

        echo json_encode([
            'success' => true, 
            'data' => $datos,
            'resumen' => [
                'total_ventas' => $total_ventas,
                'cantidad' => $cantidad_facturas
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al generar reporte: ' . $e->getMessage()]);
    }
}
?>