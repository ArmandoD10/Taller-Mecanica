<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        // Blindamos la consulta con IFNULL y LEFT JOIN para evitar caídas por falta de datos
        $sql = "SELECT 
                    CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS mecanico,
                    o.id_orden,
                    ts.nombre AS servicio,
                    ap.estado_asignacion,
                    DATE_FORMAT(rt.hora_inicio, '%d/%m/%Y %h:%i %p') AS inicio,
                    DATE_FORMAT(rt.hora_fin, '%d/%m/%Y %h:%i %p') AS fin,
                    rt.notas_hallazgos
                FROM Empleado e
                JOIN Persona per ON e.id_persona = per.id_persona
                JOIN detalle_asignacion_p dap ON e.id_empleado = dap.id_empleado
                JOIN asignacion_personal ap ON dap.id_asignacion = ap.id_asignacion
                JOIN Tipo_Servicio ts ON ap.id_tipo_servicio = ts.id_tipo_servicio
                JOIN asignacion_orden ao ON ap.id_asignacion = ao.id_asignacion
                JOIN Orden o ON ao.id_orden = o.id_orden
                LEFT JOIN registro_tiempos rt ON ap.id_asignacion = rt.id_asignacion
                WHERE ap.estado != 'eliminado'
                ORDER BY rt.hora_inicio DESC, ap.id_asignacion DESC";
                
        $res = $conexion->query($sql);
        
        // Manejo de errores seguro
        if ($res) {
            echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error en Base de Datos: ' . $conexion->error]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>