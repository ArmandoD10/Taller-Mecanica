<?php
include("../../controller/conexion.php");
session_start();
header('Content-Type: application/json');

$id_usuario = $_SESSION['id_usuario'] ?? 0;

// 1. Obtener la sucursal actual del usuario
$sql_s = "SELECT es.id_sucursal FROM empleado_sucursal es 
          INNER JOIN empleado_usuario eu ON es.id_empleado = eu.id_empleado 
          WHERE eu.id_usuario = ? AND es.estado = 'activo' LIMIT 1";
$stmt_s = $conexion->prepare($sql_s);
$stmt_s->bind_param("i", $id_usuario);
$stmt_s->execute();
$id_sucursal_actual = $stmt_s->get_result()->fetch_assoc()['id_sucursal'] ?? 0;

// 2. Buscar transferencias PENDIENTES donde YO soy el origen (tengo que enviar)
$sql = "SELECT t.id_transferencia, t.fecha_solicitud, dt.cantidad, ra.nombre as producto, s.nombre as sucursal_destino
        FROM transferencia t
        INNER JOIN detalle_transferencia dt ON t.id_transferencia = dt.id_transferencia
        INNER JOIN repuesto_articulo ra ON dt.id_articulo = ra.id_articulo
        INNER JOIN sucursal s ON t.id_sucursal_destino = s.id_sucursal
        WHERE t.id_sucursal_origen = ? AND t.estado = 'pendiente'
        ORDER BY t.fecha_solicitud DESC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_sucursal_actual);
$stmt->execute();
$res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'data' => $res, 'total' => count($res)]);