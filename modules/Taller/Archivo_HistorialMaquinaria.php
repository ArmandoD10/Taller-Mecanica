<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        $sql = "SELECT 
                    m.id_maquinaria, m.nombre AS maquinaria,
                    o.id_orden,
                    CONCAT(per.nombre, ' ', per.apellido_p) AS mecanico,
                    ts.nombre AS servicio,
                    DATE_FORMAT(rt.hora_inicio, '%d/%m/%Y %h:%i %p') AS inicio,
                    DATE_FORMAT(rt.hora_fin, '%d/%m/%Y %h:%i %p') AS fin,
                    TIMESTAMPDIFF(MINUTE, rt.hora_inicio, rt.hora_fin) AS minutos_uso
                FROM Maquinaria m
                JOIN Orden_Maquinaria om ON m.id_maquinaria = om.id_maquinaria
                JOIN Orden o ON om.id_orden = o.id_orden
                JOIN asignacion_orden ao ON o.id_orden = ao.id_orden
                JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion
                JOIN Tipo_Servicio ts ON ap.id_tipo_servicio = ts.id_tipo_servicio
                JOIN detalle_asignacion_p dap ON ap.id_asignacion = dap.id_asignacion
                JOIN Empleado e ON dap.id_empleado = e.id_empleado
                JOIN Persona per ON e.id_persona = per.id_persona
                JOIN registro_tiempos rt ON ap.id_asignacion = rt.id_asignacion
                WHERE rt.hora_fin IS NOT NULL
                ORDER BY rt.hora_inicio DESC";
        $res = $conexion->query($sql);
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        break;
}
?>