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
    $sql = "SELECT 
                c.id_cliente, 
                p.tipo_persona,
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
    $monto_credito = (float)($_POST['monto_credito'] ?? 0);
    $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? '';
    $referencia = $_POST['referencia_datacredito'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    
    $estado_credito = 'Activo'; 
    $saldo_pendiente = 0.00; 

    if (empty($id_cliente) || empty($monto_credito) || empty($fecha_vencimiento)) {
        echo json_encode(['success' => false, 'message' => 'Cliente, monto y fecha de vencimiento son obligatorios.']);
        exit;
    }

    $sqlCli = "SELECT p.tipo_persona FROM Cliente c JOIN Persona p ON c.id_persona = p.id_persona WHERE c.id_cliente = $id_cliente";
    $tipo_persona = $conexion->query($sqlCli)->fetch_assoc()['tipo_persona'] ?? 'Fisica';

    // 1. VALIDAR BYPASS PARA PERSONAS FÍSICAS
    if ($tipo_persona === 'Fisica' && $referencia === 'BYPASS-ADMIN') {
        if (empty($admin_password)) {
            echo json_encode(['success' => false, 'message' => 'Para aprobar manualmente a una persona física, debe ingresar la clave de administrador.']);
            exit;
        }
        $sqlPass = "SELECT password_hash FROM usuario WHERE id_usuario = $usuario_creacion";
        $hash = $conexion->query($sqlPass)->fetch_assoc()['password_hash'] ?? '';
        if (!password_verify($admin_password, $hash) && $admin_password !== $hash) {
            echo json_encode(['success' => false, 'message' => 'Clave de administrador incorrecta. Aprobación denegada.']);
            exit;
        }
    } else if ($tipo_persona === 'Fisica' && empty($referencia)) {
        echo json_encode(['success' => false, 'message' => 'Una persona física requiere consulta de DataCrédito o Aprobación Manual.']);
        exit;
    }

    $sql_check = "SELECT id_credito, saldo_pendiente, monto_credito FROM Credito WHERE id_cliente = $id_cliente AND estado_credito = 'Activo' AND estado = 'activo' LIMIT 1";
    $res_check = $conexion->query($sql_check);

    try {
        $conexion->begin_transaction();

        if ($res_check && $res_check->num_rows > 0) {
            // == EXISTE UN CRÉDITO: LO ACTUALIZAMOS ==
            $row = $res_check->fetch_assoc();
            $id_credito_existente = $row['id_credito'];
            $saldo_actual = (float)$row['saldo_pendiente'];
            $monto_actual_bd = (float)$row['monto_credito'];

            if ($monto_credito < $saldo_actual) {
                throw new Exception("El nuevo límite ($monto_credito) no puede ser menor a la deuda actual del cliente ($saldo_actual).");
            }

            // 2. VALIDAR AUMENTO DE LÍMITE (Cualquier persona)
            if ($monto_credito > $monto_actual_bd) {
                if (empty($admin_password)) {
                    throw new Exception("Para autorizar un aumento en el límite de crédito (De RD$ $monto_actual_bd a RD$ $monto_credito) se requiere Clave de Administrador.");
                }
                $sqlPass = "SELECT password_hash FROM usuario WHERE id_usuario = $usuario_creacion";
                $hash = $conexion->query($sqlPass)->fetch_assoc()['password_hash'] ?? '';
                if (!password_verify($admin_password, $hash) && $admin_password !== $hash) {
                    throw new Exception("Clave de administrador incorrecta. Aumento de límite denegado.");
                }
            }

            $sqlUpdate = "UPDATE Credito SET monto_credito = ?, fecha_vencimiento = ?, referencia_datacredito = IF(? != '', ?, referencia_datacredito) WHERE id_credito = ?";
            $stmtU = $conexion->prepare($sqlUpdate);
            $stmtU->bind_param("dsssi", $monto_credito, $fecha_vencimiento, $referencia, $referencia, $id_credito_existente);
            $stmtU->execute();
            
            $msg = 'El límite de crédito ha sido actualizado y unificado correctamente.';
        } else {
            // == CRÉDITO TOTALMENTE NUEVO ==
            $sqlI = "INSERT INTO Credito (id_cliente, monto_credito, saldo_pendiente, fecha_aprobacion, fecha_vencimiento, estado_credito, referencia_datacredito, usuario_creacion, estado)
                     VALUES (?, ?, 0.00, NOW(), ?, 'Activo', ?, ?, 'activo')";
            $stmtI = $conexion->prepare($sqlI);
            $stmtI->bind_param("idssi", $id_cliente, $monto_credito, $fecha_vencimiento, $referencia, $usuario_creacion);
            $stmtI->execute();
            
            $msg = 'Línea de crédito aprobada y asignada al cliente correctamente.';
        }

        $sqlLimit = "UPDATE Cliente SET limite_credito = ? WHERE id_cliente = ?";
        $stmtL = $conexion->prepare($sqlLimit);
        $stmtL->bind_param("di", $monto_credito, $id_cliente);
        $stmtL->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => $msg]);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function actualizar($conexion) {
    try {
        $conexion->begin_transaction();

        $usuario_creacion = $_SESSION['id_usuario'] ?? 1;
        $id_credito = $_POST['id_credito'];
        $id_cliente = $_POST['id_cliente'];
        $monto_credito = (float)$_POST['monto_credito'];
        $fecha_vencimiento = $_POST['fecha_vencimiento'];
        $referencia = $_POST['referencia_datacredito'] ?? '';
        $estado_credito = $_POST['estado_credito']; 
        $admin_password = $_POST['admin_password'] ?? '';

        // VALIDAR AUMENTO DE LÍMITE DESDE EL BOTÓN "EDITAR"
        $sqlActual = "SELECT monto_credito FROM Credito WHERE id_credito = $id_credito";
        $monto_actual_bd = (float)($conexion->query($sqlActual)->fetch_assoc()['monto_credito'] ?? 0);

        if ($monto_credito > $monto_actual_bd) {
            if (empty($admin_password)) {
                throw new Exception("Para autorizar un aumento en el límite de crédito (De RD$ $monto_actual_bd a RD$ $monto_credito) se requiere Clave de Administrador.");
            }
            $sqlPass = "SELECT password_hash FROM usuario WHERE id_usuario = $usuario_creacion";
            $hash = $conexion->query($sqlPass)->fetch_assoc()['password_hash'] ?? '';
            if (!password_verify($admin_password, $hash) && $admin_password !== $hash) {
                throw new Exception("Clave de administrador incorrecta. Aumento de límite denegado.");
            }
        }

        $sql = "UPDATE Credito 
                SET monto_credito=?, fecha_vencimiento=?, estado_credito=?, referencia_datacredito=? 
                WHERE id_credito=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("dsssi", $monto_credito, $fecha_vencimiento, $estado_credito, $referencia, $id_credito);
        $stmt->execute();

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

function cargar_consultas_api($conexion) {
    $id_cliente = $_GET['id_cliente'] ?? 0;
    
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