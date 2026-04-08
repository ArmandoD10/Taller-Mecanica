<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_sucursal = $_SESSION['id_sucursal'] ?? 0;

switch ($action) {
    case 'listar_ordenes_pendientes':
        listar_ordenes($conexion, $id_sucursal);
        break;
    case 'obtener_detalle_orden':
        obtener_detalle_orden($conexion);
        break;
    case 'buscar_productos':
        buscar_productos($conexion);
        break;
    case 'listar_impuestos_activos':
        listar_impuestos_activos($conexion);
        break;
    case 'guardar_factura':
        guardar_factura($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function listar_ordenes($conexion, $id_sucursal) {
    $sql = "SELECT 
                o.id_orden, 
                o.monto_total as total, 
                DATE_FORMAT(o.fecha_creacion, '%d/%m/%Y') as fecha_formateada,
                v.placa,
                p.nombre as nombre_persona,
                p.apellido_p as apellido_persona,
                c.id_cliente -- Necesario para Factura_Central más adelante
            FROM Orden o
            INNER JOIN Inspeccion i ON o.id_inspeccion = i.id_inspeccion
            INNER JOIN Vehiculo v ON i.id_vehiculo = v.sec_vehiculo 
            INNER JOIN Cliente c ON v.id_cliente = c.id_cliente
            INNER JOIN Persona p ON c.id_persona = p.id_persona
            WHERE o.id_sucursal = ? 
              AND o.estado = 'activo'
              AND o.id_orden NOT IN (
                  SELECT id_orden 
                  FROM Factura_Central 
                  WHERE estado != 'eliminado'
              )
            ORDER BY o.id_orden DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_sucursal);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // CREAMOS LA PROPIEDAD 'cliente' AQUÍ MISMO
        $nombre = $row['nombre_persona'] ?? '';
        $apellido = $row['apellido_persona'] ?? '';
        $row['cliente'] = trim($nombre . " " . $apellido);
        
        // Si por alguna razón está vacío, ponemos un fallback
        if (empty($row['cliente'])) {
            $row['cliente'] = "Cliente no identificado";
        }
        
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function obtener_detalle_orden($conexion) {
    $id_orden = (int)$_GET['id_orden'];
    
    // 1. Obtener Servicios de la Orden (Mano de Obra)
    $sql_serv = "SELECT ts.nombre, 'Servicio' as tipo, os.precio_estimado as precio, os.cantidad, ts.id_tipo_servicio as id_item
                 FROM Orden_Servicio os
                 JOIN Tipo_Servicio ts ON os.id_tipo_servicio = ts.id_tipo_servicio
                 WHERE os.id_orden = ? AND os.estado = 'activo'";
    
    $stmt_serv = $conexion->prepare($sql_serv);
    $stmt_serv->bind_param("i", $id_orden);
    $stmt_serv->execute();
    $servicios = $stmt_serv->get_result()->fetch_all(MYSQLI_ASSOC);

    // 2. Obtener Repuestos de la Orden
    $sql_rep = "SELECT ra.nombre, 'Producto' as tipo, ore.precio_base as precio, ore.cantidad, ra.id_articulo as id_item
                FROM Orden_Repuesto ore
                JOIN Repuesto_Articulo ra ON ore.id_articulo = ra.id_articulo
                WHERE ore.id_orden = ? AND ore.estado = 'activo'";
    
    $stmt_rep = $conexion->prepare($sql_rep);
    $stmt_rep->bind_param("i", $id_orden);
    $stmt_rep->execute();
    $repuestos = $stmt_rep->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true, 
        'items' => array_merge($servicios, $repuestos)
    ]);
}

function buscar_productos($conexion) {
    $term = "%" . $_GET['term'] . "%";
    
    // Especificamos ra.imagen para que no haya dudas de que viene de Repuesto_Articulo
    $sql = "SELECT 
                ra.id_articulo, 
                ra.nombre, 
                ra.precio_venta, 
                ra.imagen, 
                SUM(i.cantidad) as stock
            FROM Repuesto_Articulo ra
            INNER JOIN Inventario i ON ra.id_articulo = i.id_articulo
            WHERE (ra.nombre LIKE ? OR ra.num_serie LIKE ?) 
              AND ra.estado = 'activo' 
              AND i.estado = 'activo'
            GROUP BY ra.id_articulo
            LIMIT 5";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $term, $term);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
}

function listar_impuestos_activos($conexion) {
    $res = $conexion->query("SELECT id_impuesto, nombre_impuesto, porcentaje FROM Impuestos WHERE estado = 'activo'");
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}
?>