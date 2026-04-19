<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        // Listado general del historial de órdenes
        $sql = "SELECT o.id_orden, DATE_FORMAT(o.fecha_creacion, '%d/%m/%Y') as fecha_fmt,
                       DATE(o.fecha_creacion) as fecha_db,
                       CONCAT(IFNULL(m.nombre, ''), ' ', v.modelo) as vehiculo,
                       v.placa,
                       IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, ''))) as cliente,
                       e.nombre as estado_orden,
                       CONCAT('RD$ ', FORMAT(o.monto_total, 2)) as monto_total_fmt
                FROM orden o
                JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
                JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
                JOIN cliente c ON v.id_cliente = c.id_cliente
                JOIN persona p ON c.id_persona = p.id_persona
                LEFT JOIN marca m ON v.id_marca = m.id_marca
                JOIN orden_estado oe ON o.id_orden = oe.id_orden
                JOIN estado e ON oe.id_estado = e.id_estado
                WHERE oe.sec_orden_estado = (SELECT MAX(sec_orden_estado) FROM orden_estado WHERE id_orden = o.id_orden)
                ORDER BY o.id_orden DESC LIMIT 500";
        
        $res = $conexion->query($sql);
        echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
        break;

    case 'obtener_detalles':
        $id_orden = (int)$_GET['id_orden'];
        
        // 1. CABECERA
        $sql_cabecera = "SELECT o.id_orden, DATE_FORMAT(o.fecha_creacion, '%d/%m/%Y %h:%i %p') as fecha_fmt,
                                e.nombre as estado_orden,
                                IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, ''))) as cliente,
                                p.cedula,
                                CONCAT(IFNULL(m.nombre, ''), ' ', v.modelo) as vehiculo,
                                v.placa, v.vin_chasis, i.kilometraje_recepcion as kilometraje,
                                CONCAT('RD$ ', FORMAT(o.monto_total, 2)) as monto_total_fmt,
                                o.id_inspeccion
                         FROM orden o
                         JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
                         JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
                         JOIN cliente c ON v.id_cliente = c.id_cliente
                         JOIN persona p ON c.id_persona = p.id_persona
                         LEFT JOIN marca m ON v.id_marca = m.id_marca
                         JOIN orden_estado oe ON o.id_orden = oe.id_orden
                         JOIN estado e ON oe.id_estado = e.id_estado
                         WHERE o.id_orden = ?
                         ORDER BY oe.sec_orden_estado DESC LIMIT 1";
        $stmt_c = $conexion->prepare($sql_cabecera);
        $stmt_c->bind_param("i", $id_orden);
        $stmt_c->execute();
        $cabecera = $stmt_c->get_result()->fetch_assoc();

        // 2. TRABAJOS SOLICITADOS (Desde Inspección)
        $id_inspeccion = $cabecera['id_inspeccion'] ?? 0;
        $sql_trabajos = "SELECT ts.descripcion 
                         FROM inspeccion_trabajo it 
                         JOIN trabajo_solicitado ts ON it.id_trabajo = ts.id_trabajo 
                         WHERE it.id_inspeccion = ?";
        $stmt_t = $conexion->prepare($sql_trabajos);
        $stmt_t->bind_param("i", $id_inspeccion);
        $stmt_t->execute();
        $trabajos = $stmt_t->get_result()->fetch_all(MYSQLI_ASSOC);

        // 3. SERVICIOS Y TIEMPOS (Mano de Obra)
        // SOLUCIÓN: El JOIN ahora conecta 'ao' con 'ap' de la forma correcta.
        $sql_servicios = "SELECT ts.nombre as servicio,
                                 GROUP_CONCAT(CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) SEPARATOR ', ') as mecanicos,
                                 TIME_FORMAT(MAX(ap.hora_asignacion), '%h:%i %p') as hora_inicio,
                                 NULL as hora_fin,
                                 MAX(ap.estado_asignacion) as estado_asignacion,
                                 NULL as notas_hallazgos
                          FROM orden_servicio os
                          JOIN tipo_servicio ts ON os.id_tipo_servicio = ts.id_tipo_servicio
                          LEFT JOIN asignacion_orden ao ON os.id_orden = ao.id_orden
                          LEFT JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion AND ap.id_tipo_servicio = os.id_tipo_servicio
                          LEFT JOIN detalle_asignacion_p dap ON ap.id_asignacion = dap.id_asignacion
                          LEFT JOIN empleado emp ON dap.id_empleado = emp.id_empleado
                          LEFT JOIN persona per ON emp.id_persona = per.id_persona
                          WHERE os.id_orden = ?
                          GROUP BY os.id_tipo_servicio";
        $stmt_s = $conexion->prepare($sql_servicios);
        $stmt_s->bind_param("i", $id_orden);
        $stmt_s->execute();
        $servicios = $stmt_s->get_result()->fetch_all(MYSQLI_ASSOC);

        // 4. REPUESTOS UTILIZADOS
        $sql_repuestos = "SELECT ra.nombre, o_r.cantidad, o_r.precio_base,
                                 (o_r.cantidad * o_r.precio_base) as subtotal
                          FROM orden_repuesto o_r
                          JOIN repuesto_articulo ra ON o_r.id_articulo = ra.id_articulo
                          WHERE o_r.id_orden = ?";
        $stmt_r = $conexion->prepare($sql_repuestos);
        $stmt_r->bind_param("i", $id_orden);
        $stmt_r->execute();
        $repuestos = $stmt_r->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true, 
            'data' => [
                'cabecera' => $cabecera,
                'trabajos' => $trabajos,
                'servicios' => $servicios,
                'repuestos' => $repuestos
            ]
        ]);
        break;
}
?>