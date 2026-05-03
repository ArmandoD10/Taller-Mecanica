<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'datos_servicios':
        // 1. Servicios más realizados (Basado en asignaciones completadas)
        $sqlServ = "SELECT ts.nombre, COUNT(ap.id_asignacion) as total 
                    FROM asignacion_personal ap 
                    JOIN Tipo_Servicio ts ON ap.id_tipo_servicio = ts.id_tipo_servicio 
                    WHERE ap.estado_asignacion = 'Completado' 
                    GROUP BY ts.id_tipo_servicio ORDER BY total DESC LIMIT 5";
        $resServ = $conexion->query($sqlServ)->fetch_all(MYSQLI_ASSOC);

        // 2. Daños/Trabajos más frecuentes (Desde inspecciones)
        $sqlDanos = "SELECT ts.descripcion as nombre, COUNT(it.id_inspeccion) as total 
                     FROM inspeccion_trabajo it 
                     JOIN trabajo_solicitado ts ON it.id_trabajo = ts.id_trabajo 
                     GROUP BY ts.id_trabajo ORDER BY total DESC LIMIT 5";
        $resDanos = $conexion->query($sqlDanos)->fetch_all(MYSQLI_ASSOC);

        // 3. Órdenes por Sucursal (Productividad)
        $sqlSuc = "SELECT s.nombre, COUNT(o.id_orden) as total 
                   FROM Orden o 
                   JOIN Sucursal s ON o.id_sucursal = s.id_sucursal 
                   WHERE o.estado != 'eliminado' 
                   GROUP BY s.id_sucursal";
        $resSuc = $conexion->query($sqlSuc)->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'servicios' => $resServ,
            'danos' => $resDanos,
            'sucursales' => $resSuc
        ]);
        break;

    case 'listar_tabla_servicios':
    $sqlTabla = "SELECT 
                    o.id_orden, 
                    ts.nombre as servicio, 
                    -- Intentamos obtener el nombre del mecánico de dos fuentes posibles
                    IFNULL(
                        (SELECT CONCAT(per2.nombre, ' ', per2.apellido_p)
                         FROM asignacion_orden ao2
                         JOIN asignacion_personal ap2 ON ao2.id_asignacion = ap2.id_asignacion
                         JOIN empleado e2 ON ap2.id_empleado = e2.id_empleado
                         JOIN Persona per2 ON e2.id_persona = per2.id_persona
                         WHERE ao2.id_orden = o.id_orden LIMIT 1),
                        '<span class=\"text-muted\">Sin asignar</span>'
                    ) as mecanico, 
                    DATE_FORMAT(o.fecha_creacion, '%d/%m/%Y') as fecha, 
                    s.nombre as sucursal
                 FROM Orden o
                 INNER JOIN Orden_Servicio os ON o.id_orden = os.id_orden
                 INNER JOIN Tipo_Servicio ts ON os.id_tipo_servicio = ts.id_tipo_servicio
                 INNER JOIN Sucursal s ON o.id_sucursal = s.id_sucursal
                 WHERE o.estado != 'eliminado'
                 GROUP BY o.id_orden, ts.id_tipo_servicio
                 ORDER BY o.id_orden DESC LIMIT 50";
                 
    $res = $conexion->query($sqlTabla);
    echo json_encode(['data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
    break;
}