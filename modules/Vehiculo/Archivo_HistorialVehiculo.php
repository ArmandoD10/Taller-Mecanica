<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'cargar_vehiculos':
        cargar_vehiculos($conexion);
        break;
    case 'buscar_historial':
        buscar_historial($conexion);
        break;
}

function cargar_vehiculos($conexion) {
    // Buscador inicial: Trae placa, chasis, marca, modelo y el nombre del cliente
    $sql = "SELECT v.sec_vehiculo, v.placa, v.vin_chasis, m.nombre AS marca, v.modelo, 
                   CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) AS cliente
            FROM Vehiculo v
            JOIN Marca m ON v.id_marca = m.id_marca
            JOIN Cliente c ON v.id_cliente = c.id_cliente
            JOIN Persona p ON c.id_persona = p.id_persona
            WHERE v.estado = 'activo'";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function buscar_historial($conexion) {
    $id = (int)($_GET['id_vehiculo'] ?? 0);

    // 1. Información General del Vehículo y Propietario
    $sqlV = "SELECT v.*, m.nombre as marca, col.nombre as color,
                    CONCAT(p.nombre, ' ', p.apellido_p) as propietario, p.cedula,
                    (SELECT t.numero FROM Telefono t JOIN Cliente_Telefono ct ON t.id_telefono = ct.id_telefono WHERE ct.id_cliente = c.id_cliente LIMIT 1) as telefono
             FROM Vehiculo v
             JOIN Marca m ON v.id_marca = m.id_marca
             JOIN Color col ON v.id_color = col.id_color
             JOIN Cliente c ON v.id_cliente = c.id_cliente
             JOIN Persona p ON c.id_persona = p.id_persona
             WHERE v.sec_vehiculo = $id";
    $vehiculo = $conexion->query($sqlV)->fetch_assoc();

    // 2. HISTORIAL "MÉDICO": Todas las órdenes cerradas o en proceso
    $sqlH = "SELECT o.id_orden, o.fecha_creacion, o.descripcion as falla_reportada, 
                    o.monto_total, 
                    rt.notas_hallazgos as diagnostico_tecnico,
                    ts.nombre as servicio_realizado,
                    GROUP_CONCAT(DISTINCT CONCAT(per.nombre, ' ', per.apellido_p) SEPARATOR ', ') as mecanicos
             FROM Orden o
             JOIN Inspeccion i ON o.id_inspeccion = i.id_inspeccion
             JOIN Asignacion_Orden ao ON o.id_orden = ao.id_orden
             JOIN Asignacion_Personal ap ON ao.id_asignacion = ap.id_asignacion
             JOIN Tipo_Servicio ts ON ap.id_tipo_servicio = ts.id_tipo_servicio
             LEFT JOIN Registro_Tiempos rt ON ap.id_asignacion = rt.id_asignacion
             LEFT JOIN Detalle_Asignacion_P dap ON ap.id_asignacion = dap.id_asignacion
             LEFT JOIN Empleado emp ON dap.id_empleado = emp.id_empleado
             LEFT JOIN Persona per ON emp.id_persona = per.id_persona
             WHERE i.id_vehiculo = $id AND ap.estado_asignacion = 'Completado'
             GROUP BY ap.id_asignacion
             ORDER BY o.fecha_creacion DESC";
    
    $historial = $conexion->query($sqlH)->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'vehiculo' => $vehiculo,
        'historial' => $historial
    ]);
}