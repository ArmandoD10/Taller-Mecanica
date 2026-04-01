<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
session_start();

switch ($action) {
    case 'cargar_clientes':
        cargar_clientes($conexion);
        break;
    case 'cargar':
        cargar($conexion);
        break;
    case 'guardar_historial':
        guardar_historial($conexion);
        break;
    case 'actualizar':
        actualizar($conexion);
        break;
    case 'consultar_simulacion':
        consultar_simulacion($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function consultar_simulacion($conexion) {
    $cedula = $conexion->real_escape_string($_GET['cedula'] ?? '');

    if (empty($cedula)) {
        echo json_encode(['success' => false, 'message' => 'Cédula no proporcionada']);
        die();
    }

    // --- 1. VALIDACIÓN CRÍTICA: ¿Existe el cliente en el taller? ---
    $sql_id = "SELECT c.id_cliente FROM Cliente c 
               INNER JOIN Persona p ON c.id_persona = p.id_persona 
               WHERE p.cedula = '$cedula' LIMIT 1";
    
    $res_id = $conexion->query($sql_id);
    $row_id = $res_id->fetch_assoc();
    
    // Si NO existe en el taller, lanzamos el error de inmediato
    if (!$row_id) {
        echo json_encode([
            'success' => false, 
            'message' => 'Error: El titular de esta cédula no pertenece al taller. Debe registrarlo como cliente antes de consultar su crédito.'
        ]);
        die();
    }

    $id_cliente_taller = $row_id['id_cliente'];
    // -----------------------------------------------------------------

    // 2. Si pasó la validación, buscamos en Api_DataCredito
    $sql_api = "SELECT * FROM Api_DataCredito 
                WHERE cedula = '$cedula' AND estado = 'activo' LIMIT 1";
    
    $res_api = $conexion->query($sql_api);

    if ($res_api && $res_api->num_rows > 0) {
        $info_credito = $res_api->fetch_assoc();
        $ref = $info_credito['referencia'];

        // 3. Detalle de cuentas
        $sql_detalle = "SELECT entidad, producto, total, fecha FROM Detalle_ApiCredito WHERE referencia = '$ref'";
        $res_detalle = $conexion->query($sql_detalle);
        $cuentas = [];

        while ($row = $res_detalle->fetch_assoc()) {
            $cuentas[] = [
                'entidad' => $row['entidad'],
                'producto' => $row['producto'],
                'monto' => $row['total'],
                'fecha' => $row['fecha'],
                'estado' => ($info_credito['tiene_morosidad'] == 0) ? 'Al Día' : 'En Mora'
            ];
        }

        // 4. Respuesta Exitosa
        echo json_encode([
            'success' => true,
            'id_cliente_real' => $id_cliente_taller, 
            'referencia' => $ref,
            'cliente' => [
                'nombre' => $info_credito['nombre'] . ' ' . $info_credito['apellido'],
                'cedula' => $info_credito['cedula'],
                'score' => $info_credito['score_crediticio'],
                'riesgo' => $info_credito['nivel_riesgo'],
                'nacionalidad' => $info_credito['nacionalidad'],
                'saldo_total' => $info_credito['saldo_actual']
            ],
            'data' => $cuentas
        ]);

    } else {
        // El cliente existe en el taller, pero no tiene historial en el buró
        echo json_encode([
            'success' => false, 
            'message' => 'Cliente verificado en taller, pero no posee historial registrado en DataCrédito.'
        ]);
    }
    die();
}


// La función
function guardar_historial($conexion) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_cliente = (int)$_POST['id_cliente'];
        $referencia = $conexion->real_escape_string($_POST['referencia']);
        $estado_consulta = $conexion->real_escape_string($_POST['estado_consulta']);
        
        // Asumiendo que el usuario logueado está en la sesión
        // Si no tienes sesión aún, usa un ID fijo para pruebas
        $usuario_creacion = 1; 

        $sql = "INSERT INTO Consulta_DataCredito 
                (id_cliente, fecha_consulta, estado_consulta, observaciones, referencia_consulta, estado, usuario_creacion) 
                VALUES 
                ($id_cliente, NOW(), '$estado_consulta', 'N/A', '$referencia', 'activo', $usuario_creacion)";

        if ($conexion->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Historial guardado.']);
        } else {
            echo json_encode(['success' => false, 'message' => $conexion->error]);
        }
    }
    die();
}


?>