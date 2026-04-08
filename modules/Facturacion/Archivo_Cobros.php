<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? 1;

switch ($action) {
    case 'listar_pendientes':
        listar_pendientes($conexion);
        break;
    case 'procesar_pago':
        procesar_pago($conexion, $id_usuario);
        break;
    case 'obtener_recibo':
        obtener_recibo($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function listar_pendientes($conexion) {
    // Usamos f.fecha_emision y ordenamos por id_factura para que no falle
    $sql = "SELECT 
                f.id_factura, 
                f.id_orden, 
                f.monto_total, 
                DATE_FORMAT(f.fecha_emision, '%d/%m/%Y') AS fecha_emision_fmt,
                CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS cliente,
                fc.id_credito,
                IFNULL((SELECT SUM(monto) FROM Abono_Factura WHERE id_factura = f.id_factura AND estado = 'activo'), 0) AS total_pagado
            FROM Factura_Central f
            JOIN Cliente c ON f.id_cliente = c.id_cliente
            JOIN Persona per ON c.id_persona = per.id_persona
            JOIN Factura_Credito fc ON f.id_factura = fc.id_factura
            WHERE f.estado_pago = 'Pendiente' AND f.estado = 'activo'
            ORDER BY f.id_factura ASC";
            
    $res = $conexion->query($sql);
    
    if ($res) {
        $data = [];
        while($row = $res->fetch_assoc()) {
            $row['restante'] = (float)$row['monto_total'] - (float)$row['total_pagado'];
            $row['fecha_emision'] = $row['fecha_emision_fmt'];
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error SQL: ' . $conexion->error]);
    }
}

function procesar_pago($conexion, $id_usuario) {
    $id_factura = (int)$_POST['id_factura'];
    $id_credito = (int)$_POST['id_credito'];
    $monto_pago = (float)$_POST['monto_pago'];
    $metodo_pago = $_POST['metodo_pago'];
    $referencia = $_POST['referencia'] ?? 'N/A';

    if ($monto_pago <= 0) {
        echo json_encode(['success' => false, 'message' => 'El monto debe ser mayor a cero.']); return;
    }

    $conexion->begin_transaction();

    try {
        // 1. Guardar el Abono
        $sqlAbono = "INSERT INTO Abono_Factura (id_factura, monto, metodo_pago, referencia, usuario_creacion) VALUES (?, ?, ?, ?, ?)";
        $stmtA = $conexion->prepare($sqlAbono);
        $stmtA->bind_param("idssi", $id_factura, $monto_pago, $metodo_pago, $referencia, $id_usuario);
        $stmtA->execute();
        $id_abono = $conexion->insert_id;

        // 2. LA MAGIA CONTABLE INVERSA: Sumamos al disponible y restamos a la deuda (pendiente)
        $sqlCredito = "UPDATE Credito 
                       SET saldo_disponible = saldo_disponible + ?, 
                           saldo_pendiente = saldo_pendiente - ? 
                       WHERE id_credito = ?";
        $stmtC = $conexion->prepare($sqlCredito);
        $stmtC->bind_param("ddi", $monto_pago, $monto_pago, $id_credito);
        $stmtC->execute();

        // 3. Verificar si la factura ya se saldó por completo
        $sqlCheck = "SELECT f.monto_total, IFNULL(SUM(a.monto), 0) AS pagado 
                     FROM Factura_Central f 
                     LEFT JOIN Abono_Factura a ON f.id_factura = a.id_factura AND a.estado = 'activo'
                     WHERE f.id_factura = $id_factura";
        $resCheck = $conexion->query($sqlCheck)->fetch_assoc();
        
        if (($resCheck['monto_total'] - $resCheck['pagado']) <= 0.01) {
            $conexion->query("UPDATE Factura_Central SET estado_pago = 'Pagado' WHERE id_factura = $id_factura");
        }

        $conexion->commit();
        echo json_encode(['success' => true, 'id_abono' => $id_abono, 'message' => 'Pago procesado y crédito restaurado.']);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtener_recibo($conexion) {
    $id_abono = (int)$_GET['id_abono'];
    
    $sql = "SELECT 
                a.id_abono, a.monto, a.metodo_pago, a.referencia, DATE_FORMAT(a.fecha_creacion, '%d/%m/%Y %h:%i %p') AS fecha,
                f.id_factura, f.id_orden, f.monto_total,
                CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS cliente,
                IFNULL(u.username, 'Caja') AS cajero,
                (f.monto_total - (SELECT SUM(monto) FROM Abono_Factura WHERE id_factura = f.id_factura AND id_abono <= a.id_abono AND estado='activo')) AS balance_restante
            FROM Abono_Factura a
            JOIN Factura_Central f ON a.id_factura = f.id_factura
            JOIN Cliente c ON f.id_cliente = c.id_cliente
            JOIN Persona per ON c.id_persona = per.id_persona
            LEFT JOIN Usuario u ON a.usuario_creacion = u.id_usuario
            WHERE a.id_abono = $id_abono";
            
    $res = $conexion->query($sql);
    
    if ($res && $res->num_rows > 0) {
        echo json_encode(['success' => true, 'data' => $res->fetch_assoc()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Recibo no encontrado.']);
    }
}
?>