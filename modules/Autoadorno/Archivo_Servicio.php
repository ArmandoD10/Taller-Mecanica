<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_sucursal = $_SESSION['id_sucursal'] ?? 1;
$id_usuario = $_SESSION['id_usuario'] ?? 0;

switch ($action) {
    // 1. Cargar la lista de combos disponibles
    case 'listar_combos':
        $sql = "SELECT p.id_paquete, p.nombre_paquete, 
                (SELECT SUM(cantidad * precio_unidad_con_descuento) 
                 FROM Paquete_Detalle_Articulo 
                 WHERE id_paquete = p.id_paquete) as precio_total
                FROM Paquete_Servicio p 
                WHERE p.estado = 'activo'";
        $res = $conexion->query($sql);
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        break;

    // 2. Ver detalle de artículos de un combo (con imágenes)
    case 'obtener_detalle_paquete':
        $id = (int)$_GET['id'];
        $sql = "SELECT a.nombre, a.imagen, d.cantidad, d.precio_unidad_con_descuento as precio 
                FROM Paquete_Detalle_Articulo d
                JOIN repuesto_articulo a ON d.id_articulo = a.id_articulo
                WHERE d.id_paquete = $id";
        $res = $conexion->query($sql);
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        break;

    // 3. Buscar inspecciones pendientes por placa
    case 'buscar_inspecciones_pendientes':
    $placa = $conexion->real_escape_string($_GET['placa']);
    
    // Unimos Inspección con Vehículo usando sec_vehiculo
    $sql = "SELECT i.id_inspeccion, 
                   DATE_FORMAT(i.fecha_inspeccion, '%d/%m/%Y %h:%i %p') as fecha_formateada, 
                   v.placa, m.nombre as marca, v.modelo
            FROM inspeccion i
            INNER JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            LEFT JOIN marca m ON v.id_marca = m.id_marca
            LEFT JOIN orden o ON i.id_inspeccion = o.id_inspeccion
            WHERE v.placa = '$placa' 
              AND i.estado = 'activo' -- Cambiado de 'finalizado' a 'activo' según tu DB
              AND o.id_orden IS NULL 
            ORDER BY i.fecha_inspeccion DESC";
            
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
    break;

    // 4. Guardar la nueva orden con validación de inventario
    case 'guardar_orden':
    $id_inspeccion = (int)$_POST['id_inspeccion'];
    $id_paquete = (int)$_POST['id_paquete'];
    $total_combo = (float)$_POST['total_oculto'];
    $id_usuario = $_SESSION['id_usuario'];
    $id_sucursal = $_SESSION['id_sucursal'];
    $nombre_combo = $conexion->real_escape_string($_POST['nombre_combo']);

    $conexion->begin_transaction();
    try {
        // 1. INSERTAR CABECERA DE ORDEN (SIN id_vehiculo, SOLO id_inspeccion)
        // Eliminamos id_vehiculo de la lista de columnas
        $sqlOrden = "INSERT INTO orden (id_sucursal, id_inspeccion, descripcion, monto_total, fecha_creacion, usuario_creacion, estado) 
                     VALUES ($id_sucursal, $id_inspeccion, 'ORDEN AUTOADORNO: $nombre_combo', $total_combo, NOW(), $id_usuario, 'activo')";
        
        if (!$conexion->query($sqlOrden)) {
            throw new Exception("Error al crear cabecera: " . $conexion->error);
        }
        $id_orden = $conexion->insert_id;

        // 2. INSERTAR EN ORDEN_SERVICIO (Para visibilidad en Tiempos)
        $resT = $conexion->query("SELECT id_tipo_servicio FROM tipo_servicio WHERE nombre LIKE '%Autoadorno%' LIMIT 1");
        $id_tipo = ($rowT = $resT->fetch_assoc()) ? $rowT['id_tipo_servicio'] : 1;
        
        $conexion->query("INSERT INTO orden_servicio (id_orden, id_tipo_servicio, precio_estimado, cantidad, estado) 
                          VALUES ($id_orden, $id_tipo, $total_combo, 1, 'activo')");

        // 3. INSERTAR ESTADO INICIAL (Para Monitor de Taller)
        $conexion->query("INSERT INTO Orden_Estado (id_orden, id_estado, fecha_creacion, usuario_creacion) 
                         VALUES ($id_orden, 1, NOW(), $id_usuario)");

        // 4. REBAJAR INVENTARIO FIFO POR SUCURSAL
        $resArt = $conexion->query("SELECT id_articulo, cantidad, precio_unidad_con_descuento FROM Paquete_Detalle_Articulo WHERE id_paquete = $id_paquete");
        
        while($art = $resArt->fetch_assoc()) {
            $id_art = $art['id_articulo'];
            $cant_req = $art['cantidad'];

            // Registrar repuesto en la orden
            $conexion->query("INSERT INTO orden_repuesto (id_orden, id_articulo, cantidad, precio_base, estado) 
                             VALUES ($id_orden, $id_art, $cant_req, {$art['precio_unidad_con_descuento']}, 'activo')");

            // Descuento físico de la tabla Inventario
            $sqlInv = "SELECT i.sec_inventario, i.cantidad FROM Inventario i
                       JOIN Gondola g ON i.id_gondola = g.id_gondola
                       JOIN Almacen al ON g.id_almacen = al.id_almacen
                       WHERE i.id_articulo = $id_art AND al.id_sucursal = $id_sucursal AND i.cantidad > 0
                       ORDER BY i.sec_inventario ASC";
            
            $resInv = $conexion->query($sqlInv);
            while($inv = $resInv->fetch_assoc()) {
                if($cant_req <= 0) break;
                $a_descontar = min($inv['cantidad'], $cant_req);
                $conexion->query("UPDATE Inventario SET cantidad = cantidad - $a_descontar WHERE sec_inventario = {$inv['sec_inventario']}");
                $cant_req -= $a_descontar;
            }
        }

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => "Orden #$id_orden creada con éxito."]);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

    // 5. Listar órdenes recientes de la sucursal
    case 'listar_ordenes':
        $sql = "SELECT o.id_orden, o.descripcion, o.monto_total, o.fecha_creacion, e.nombre as estado
                FROM orden o
                JOIN Orden_Estado oe ON o.id_orden = oe.id_orden
                JOIN Estado e ON oe.id_estado = e.id_estado
                WHERE o.id_sucursal = $id_sucursal 
                AND o.descripcion LIKE 'ORDEN AUTOADORNO%'
                AND oe.sec_orden_estado = (SELECT MAX(sec_orden_estado) FROM Orden_Estado WHERE id_orden = o.id_orden)
                ORDER BY o.id_orden DESC LIMIT 10";
        $res = $conexion->query($sql);
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
        break;
}
?>