<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? 1;

switch ($action) {
   case 'listar_clientes_aceite':
    // Ajuste de JOINS para usar Cliente_Telefono
    $sql = "SELECT 
                o.id_orden, 
                per.nombre, 
                per.apellido_p, 
                tel.id_telefono,
                tel.numero as telefono,
                v.placa,
                o.fecha_creacion as ultima_visita,
                DATEDIFF(NOW(), o.fecha_creacion) as dias_transcurridos
            FROM orden o
            INNER JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            INNER JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            INNER JOIN cliente cl ON v.id_cliente = cl.id_cliente
            INNER JOIN persona per ON cl.id_persona = per.id_persona
            -- Conexión con la tabla relacional del teléfono
            INNER JOIN Cliente_Telefono ct ON cl.id_cliente = ct.id_cliente
            INNER JOIN telefono tel ON ct.id_telefono = tel.id_telefono
            -- Conexión con los servicios de la orden
            INNER JOIN orden_servicio os ON o.id_orden = os.id_orden
            INNER JOIN tipo_servicio ts ON os.id_tipo_servicio = ts.id_tipo_servicio
            WHERE (ts.nombre LIKE '%Aceite%' OR o.descripcion LIKE '%Aceite%')
              AND o.fecha_creacion <= DATE_SUB(NOW(), INTERVAL 6 MONTH)
              AND ct.estado = 'activo'
              AND tel.estado = 'activo'
            GROUP BY cl.id_cliente
            ORDER BY o.fecha_creacion ASC";
    
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
    break;

    case 'registrar_envio_whatsapp':
    // Validamos que los datos realmente existan en el POST
    if (isset($_POST['id_telefono'], $_POST['nombre'], $_POST['placa'])) {
        $id_tel = (int)$_POST['id_telefono'];
        $nombre = $conexion->real_escape_string($_POST['nombre']);
        $placa = $conexion->real_escape_string($_POST['placa']);

        $mensaje = "Hola $nombre, recordamos que tu vehículo ($placa) tuvo su último cambio de aceite hace 6 meses. ¡Es tiempo de mantenimiento!";

        try {
            $sqlMsg = "INSERT INTO API_WhatsApp (id_telefono, tipo_mensaje, mensaje, estado_envio, fecha_envio, estado) 
                       VALUES ($id_tel, 'Recordatorio Aceite', '$mensaje', 'Pendiente', NOW(), 'activo')";
            
            if ($conexion->query($sqlMsg)) {
                echo json_encode(['success' => true, 'message' => 'Recordatorio registrado']);
            } else {
                echo json_encode(['success' => false, 'message' => $conexion->error]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Faltan datos en la solicitud (POST)']);
    }
    break;
}