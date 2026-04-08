<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listar_historial($conexion);
        break;
    case 'obtener_detalles':
        obtener_detalles($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida en módulo de historial']);
        break;
}

function listar_historial($conexion) {
    // Consulta adaptada a la nueva estructura normalizada
    $sql = "SELECT 
                o.id_orden, 
                DATE_FORMAT(o.fecha_creacion, '%d/%m/%Y %h:%i %p') AS fecha_fmt,
                
                -- Extraemos el estado más reciente desde Orden_Estado
                (SELECT e.nombre FROM Orden_Estado oe JOIN Estado e ON oe.id_estado = e.id_estado 
                 WHERE oe.id_orden = o.id_orden ORDER BY oe.fecha_creacion DESC LIMIT 1) AS estado_orden,
                 
                IFNULL(o.monto_total, 0) AS monto_total,
                CONCAT('RD$ ', FORMAT(IFNULL(o.monto_total, 0), 2)) AS monto_total_fmt,
                CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS cliente,
                v.placa, 
                CONCAT(mar.nombre, ' ', IFNULL(v.modelo, '')) AS vehiculo
            FROM Orden o
            JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            JOIN Vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN Marca mar ON v.id_marca = mar.id_marca
            JOIN Cliente c ON v.id_cliente = c.id_cliente
            JOIN Persona per ON c.id_persona = per.id_persona
            WHERE o.estado != 'eliminado'
            ORDER BY o.id_orden DESC";
            
    $res = $conexion->query($sql);
    
    if ($res) {
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error SQL: ' . $conexion->error]);
    }
}

function obtener_detalles($conexion) {
    $id_orden = (int)($_GET['id_orden'] ?? 0);
    
    if($id_orden === 0) {
        echo json_encode(['success' => false, 'message' => 'ID de orden no válido.']);
        return;
    }

    $response = [];

    // 1. OBTENER DATOS DE CABECERA adaptados
    $sqlCabecera = "SELECT 
                o.id_orden, 
                DATE_FORMAT(o.fecha_creacion, '%d/%m/%Y %h:%i %p') AS fecha_fmt,
                
                (SELECT e.nombre FROM Orden_Estado oe JOIN Estado e ON oe.id_estado = e.id_estado 
                 WHERE oe.id_orden = o.id_orden ORDER BY oe.fecha_creacion DESC LIMIT 1) AS estado_orden,
                 
                CONCAT('RD$ ', FORMAT(IFNULL(o.monto_total, 0), 2)) AS monto_total_fmt,
                CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, ''), ' ', IFNULL(per.apellido_m, '')) AS cliente,
                per.cedula,
                v.placa, 
                v.vin_chasis,
                i.kilometraje_recepcion AS kilometraje,
                CONCAT(mar.nombre, ' ', IFNULL(v.modelo, ''), ' (', IFNULL(v.anio, 'N/A'), ')') AS vehiculo
            FROM Orden o
            JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            JOIN Vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN Marca mar ON v.id_marca = mar.id_marca
            JOIN Cliente c ON v.id_cliente = c.id_cliente
            JOIN Persona per ON c.id_persona = per.id_persona
            WHERE o.id_orden = $id_orden LIMIT 1";
            
    $resCab = $conexion->query($sqlCabecera);
    if($resCab && $resCab->num_rows > 0) {
        $response['cabecera'] = $resCab->fetch_assoc();
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró la información cabecera de la orden.']);
        return;
    }

    // 2. OBTENER SERVICIOS REALIZADOS Y TIEMPOS (Radiografía)
    $sqlServicios = "SELECT 
                        ts.nombre AS servicio, 
                        ap.estado_asignacion,
                        DATE_FORMAT(rt.hora_inicio, '%d/%m/%Y %h:%i %p') AS hora_inicio, 
                        DATE_FORMAT(rt.hora_fin, '%d/%m/%Y %h:%i %p') AS hora_fin, 
                        rt.notas_hallazgos,
                        (
                            SELECT GROUP_CONCAT(DISTINCT CONCAT(p2.nombre, ' ', p2.apellido_p) SEPARATOR ', ') 
                            FROM detalle_asignacion_p dap 
                            JOIN Empleado e2 ON dap.id_empleado = e2.id_empleado 
                            JOIN Persona p2 ON e2.id_persona = p2.id_persona 
                            WHERE dap.id_asignacion = ap.id_asignacion
                        ) AS mecanicos
                    FROM asignacion_orden ao
                    JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion
                    JOIN Tipo_Servicio ts ON ap.id_tipo_servicio = ts.id_tipo_servicio
                    LEFT JOIN registro_tiempos rt ON ap.id_asignacion = rt.id_asignacion
                    WHERE ao.id_orden = $id_orden
                    ORDER BY ap.id_asignacion ASC";
                    
    $resServ = $conexion->query($sqlServicios);
    $response['servicios'] = $resServ ? $resServ->fetch_all(MYSQLI_ASSOC) : [];

    echo json_encode(['success' => true, 'data' => $response]);
}
?>