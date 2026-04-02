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
    case 'guardar':
        guardar($conexion);
        break;
    case 'actualizar':
        actualizar($conexion);
        break;
    case 'cargar_consultas_api':
        cargar_consultas_api($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function cargar_clientes($conexion) {
    // Traemos solo clientes activos. Diferenciamos si es empresa o persona física para el nombre.
    $sql = "SELECT 
                c.id_cliente, 
                IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', p.apellido_p)) AS nombre_cliente,
                p.cedula
            FROM Cliente c
            JOIN Persona p ON c.id_persona = p.id_persona
            WHERE c.estado = 'activo'
            ORDER BY nombre_cliente ASC";
    
    $resultado = $conexion->query($sql);
    $data = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $data[] = $fila;
        }
    }
    echo json_encode(['success' => true, 'data' => $data]);
}

function cargar($conexion) {
    $sql = "SELECT 
                cr.id_credito,
                cr.id_cliente,
                IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', p.apellido_p)) AS nombre_cliente,
                p.cedula,
                cr.monto_credito,
                cr.saldo_pendiente,
                cr.fecha_aprobacion,
                cr.fecha_vencimiento,
                cr.estado_credito,
                cr.referencia_datacredito
            FROM Credito cr
            JOIN Cliente c ON cr.id_cliente = c.id_cliente
            JOIN Persona p ON c.id_persona = p.id_persona
            WHERE cr.estado = 'activo'
            ORDER BY cr.id_credito DESC";

    $resultado = $conexion->query($sql);
    $data = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $data[] = $fila;
        }
    }
    echo json_encode(['success' => true, 'data' => $data]);
}

function guardar($conexion) {
    $usuario_creacion = $_SESSION['id_usuario'] ?? 1;

    $id_cliente = $_POST['id_cliente'] ?? '';
    $monto_credito = $_POST['monto_credito'] ?? 0;
    $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? '';
    $referencia = $_POST['referencia_datacredito'] ?? '';
    $estado_credito = 'Activo'; // Por defecto al crear
    $saldo_pendiente = 0.00; // Inicia en 0, se consume al facturar

    if (empty($id_cliente) || empty($monto_credito) || empty($fecha_vencimiento)) {
        echo json_encode(['success' => false, 'message' => 'Cliente, monto y fecha de vencimiento son obligatorios.']);
        exit;
    }

    // --- BLOQUE DE VALIDACIONES DE POLÍTICA DE CRÉDITO ---

    // 1. Buscamos el crédito activo más reciente del cliente
    $sql_check = "SELECT monto_credito, saldo_pendiente 
                  FROM Credito 
                  WHERE id_cliente = $id_cliente 
                  AND estado_credito = 'Activo' 
                  AND estado = 'activo' 
                  ORDER BY id_credito DESC LIMIT 1";
    
    $res_check = $conexion->query($sql_check);

    if ($res_check && $res_check->num_rows > 0) {
        $data = $res_check->fetch_assoc();
        $monto_actual = (float)$data['monto_credito'];
        $saldo_actual = (float)$data['saldo_pendiente'];

        // --- REGLA 1: Evitar Créditos "Vacíos" e Inactivos ---
        if ($saldo_actual == 0) {
            echo json_encode([
                'success' => false, 
                'message' => "Denegado: El cliente ya tiene una línea de crédito activa que NO ha empezado a utilizar. No se puede asignar una nueva hasta que consuma la actual."
            ]);
            exit;
        }

        // --- REGLA 2: Límite de Endeudamiento (75%) ---
        $porcentaje_uso = ($saldo_actual / $monto_actual) * 100;

        if ($porcentaje_uso >= 75) {
            echo json_encode([
                'success' => false, 
                'message' => "Denegado: El cliente ha consumido el " . number_format($porcentaje_uso, 2) . "% de su crédito disponible. Ha superado el límite de riesgo permitido (75%)."
            ]);
            exit;
        }
    }
    // --- FIN DE VALIDACIONES ---

    try {
        $conexion->begin_transaction();

        // 1. Insertar el Crédito
        $sql = "INSERT INTO Credito (id_cliente, monto_credito, saldo_pendiente, fecha_aprobacion, fecha_vencimiento, estado_credito, referencia_datacredito, usuario_creacion, estado)
                VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, 'activo')";
        $stmt = $conexion->prepare($sql);
        
        // 🔥 CORRECCIÓN APLICADA: 'iddsssi' (i=int, d=decimal, d=decimal, s=string/date, s=string, s=string, i=int)
        $stmt->bind_param("iddsssi", $id_cliente, $monto_credito, $saldo_pendiente, $fecha_vencimiento, $estado_credito, $referencia, $usuario_creacion);
        
        $stmt->execute();

        // 2. Actualizar el límite de crédito en el perfil del Cliente
        $sqlCli = "UPDATE Cliente SET limite_credito = ? WHERE id_cliente = ?";
        $stmtCli = $conexion->prepare($sqlCli);
        $stmtCli->bind_param("di", $monto_credito, $id_cliente);
        $stmtCli->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Línea de crédito aprobada y asignada al cliente correctamente.']);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()]);
    }
}

function actualizar($conexion) {
    try {
        $conexion->begin_transaction();

        $id_credito = $_POST['id_credito'];
        $id_cliente = $_POST['id_cliente'];
        $monto_credito = $_POST['monto_credito'];
        $fecha_vencimiento = $_POST['fecha_vencimiento'];
        $referencia = $_POST['referencia_datacredito'] ?? '';
        $estado_credito = $_POST['estado_credito']; // Activo, Pagado, Vencido, Cancelado

        // 1. Actualizar el Crédito
        $sql = "UPDATE Credito 
                SET monto_credito=?, fecha_vencimiento=?, estado_credito=?, referencia_datacredito=? 
                WHERE id_credito=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("dsssi", $monto_credito, $fecha_vencimiento, $estado_credito, $referencia, $id_credito);
        $stmt->execute();

        // 2. Sincronizar el límite de crédito del cliente si el crédito sigue Activo
        $sqlCli = "UPDATE Cliente SET limite_credito = ? WHERE id_cliente = ?";
        $stmtCli = $conexion->prepare($sqlCli);
        $stmtCli->bind_param("di", $monto_credito, $id_cliente);
        $stmtCli->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Crédito actualizado correctamente.']);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
    }
}

// Nueva función:
function cargar_consultas_api($conexion) {
    $id_cliente = $_GET['id_cliente'] ?? 0;
    
    // Unimos la consulta con el maestro de crédito para obtener el SCORE
    $sql = "SELECT 
                c.fecha_consulta, 
                c.referencia_consulta, 
                c.estado_consulta,
                a.score_crediticio
            FROM Consulta_DataCredito c
            JOIN Api_DataCredito a ON c.referencia_consulta = a.referencia
            WHERE c.id_cliente = $id_cliente AND c.estado = 'activo'
            ORDER BY c.fecha_consulta DESC";
            
    $res = $conexion->query($sql);
    $data = [];
    while($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
}
?>