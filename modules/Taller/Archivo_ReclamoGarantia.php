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
        try {
            $sql = "SELECT rg.id_reclamo, rg.id_orden_original, DATE_FORMAT(rg.fecha_reclamo, '%d/%m/%Y') as fecha,
                           CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) as cliente,
                           CONCAT(m.nombre, ' ', IFNULL(v.modelo, ''), ' [', v.placa, ']') as vehiculo,
                           rg.estado_reclamo,
                           rg.tipo_item,
                           IF(rg.tipo_item = 'servicio', 
                              (SELECT ts.nombre FROM orden_servicio os JOIN tipo_servicio ts ON os.id_tipo_servicio = ts.id_tipo_servicio WHERE os.sec_serv = rg.id_item_afectado),
                              (SELECT ra.nombre FROM orden_repuesto orp JOIN repuesto_articulo ra ON orp.id_articulo = ra.id_articulo WHERE orp.sec_detalle = rg.id_item_afectado)
                           ) as item_afectado
                    FROM reclamo_garantia rg
                    JOIN cliente c ON rg.id_cliente = c.id_cliente
                    JOIN persona p ON c.id_persona = p.id_persona
                    JOIN vehiculo v ON rg.id_vehiculo = v.sec_vehiculo
                    LEFT JOIN marca m ON v.id_marca = m.id_marca
                    WHERE rg.estado = 'activo' AND rg.id_sucursal = ?
                    ORDER BY rg.id_reclamo DESC LIMIT 200";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $id_sucursal);
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error SQL: ' . $e->getMessage()]);
        }
        break;

    case 'buscar_orden':
        try {
            $id_orden = (int)$_GET['id_orden'];
            
            $sqlCab = "SELECT o.id_orden, o.id_sucursal, c.id_cliente, i.id_vehiculo,
                              CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) as cliente,
                              CONCAT(m.nombre, ' ', IFNULL(v.modelo, ''), ' [', v.placa, ']') as vehiculo
                       FROM orden o
                       JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
                       JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
                       LEFT JOIN marca m ON v.id_marca = m.id_marca
                       JOIN cliente c ON v.id_cliente = c.id_cliente
                       JOIN persona p ON c.id_persona = p.id_persona
                       WHERE o.id_orden = ?";
            $stmtC = $conexion->prepare($sqlCab);
            $stmtC->bind_param("i", $id_orden);
            $stmtC->execute();
            $cabecera = $stmtC->get_result()->fetch_assoc();

            if (!$cabecera) throw new Exception("No se encontró la Orden ORD-$id_orden o no tiene un vehículo asociado.");

            // Buscar ítems con garantía
            $sqlItems = "
                SELECT 'servicio' as tipo, os.sec_serv as id_item, ts.nombre as descripcion, 
                       os.fecha_vencimiento, os.kilometraje_vencimiento, pg.nombre as politica
                FROM orden_servicio os
                JOIN tipo_servicio ts ON os.id_tipo_servicio = ts.id_tipo_servicio
                JOIN politica_garantia pg ON os.id_politica = pg.id_politica
                WHERE os.id_orden = $id_orden AND os.id_politica IS NOT NULL
                UNION ALL
                SELECT 'repuesto' as tipo, orp.sec_detalle as id_item, ra.nombre as descripcion, 
                       orp.fecha_vencimiento, orp.kilometraje_vencimiento, pg.nombre as politica
                FROM orden_repuesto orp
                JOIN repuesto_articulo ra ON orp.id_articulo = ra.id_articulo
                JOIN politica_garantia pg ON orp.id_politica = pg.id_politica
                WHERE orp.id_orden = $id_orden AND orp.id_politica IS NOT NULL
            ";
            $items = $conexion->query($sqlItems)->fetch_all(MYSQLI_ASSOC);

            if (count($items) === 0) throw new Exception("Esta orden no posee ningún servicio o repuesto con garantía registrada.");

            echo json_encode(['success' => true, 'cabecera' => $cabecera, 'items' => $items]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'guardar_reclamo':
        try {
            $id_orden = (int)$_POST['rg_id_orden'];
            $id_cliente = (int)$_POST['rg_id_cliente'];
            $id_vehiculo = (int)$_POST['rg_id_vehiculo'];
            $id_sucursal_orden = (int)$_POST['rg_id_sucursal'];
            $km_actual = (int)$_POST['km_actual'];
            $falla = $_POST['falla_reportada'];
            
            // El radio button trae valor tipo "servicio_45" o "repuesto_12"
            $seleccion = explode("_", $_POST['item_afectado']);
            $tipo_item = $seleccion[0];
            $id_item = (int)$seleccion[1];

            $sql = "INSERT INTO reclamo_garantia (id_orden_original, id_sucursal, id_cliente, id_vehiculo, tipo_item, id_item_afectado, kilometraje_reclamo, falla_reportada, usuario_creacion) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            
            // CORRECCIÓN VITAL: 4 enteros, 1 string, 2 enteros, 1 string, 1 entero (iiiisiisi)
            $stmt->bind_param("iiiisiisi", $id_orden, $id_sucursal_orden, $id_cliente, $id_vehiculo, $tipo_item, $id_item, $km_actual, $falla, $id_usuario);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Expediente de reclamo creado exitosamente.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()]);
        }
        break;

    case 'evaluar_reclamo':
        try {
            $id_reclamo = (int)$_POST['ev_id_reclamo'];
            $decision = $_POST['ev_decision']; 
            $resolucion = $_POST['ev_resolucion'];

            $sql = "UPDATE reclamo_garantia SET estado_reclamo = ?, resolucion = ? WHERE id_reclamo = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ssi", $decision, $resolucion, $id_reclamo);
            $stmt->execute();

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
}
?>