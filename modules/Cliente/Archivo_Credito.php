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
?>