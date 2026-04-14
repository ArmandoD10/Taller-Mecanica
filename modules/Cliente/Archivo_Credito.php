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
    case 'eliminar':
        eliminar($conexion);
        break;
    case 'cargar_consultas_api':
        cargar_consultas_api($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

// =========================================================================
// FUNCIÓN DE SEGURIDAD BLINDADA: SUPERVISOR OVERRIDE
// =========================================================================
function verificar_clave_admin($conexion, $username, $password_ingresada) {
    if (empty($username) || empty($password_ingresada)) return false;

    $sql = "SELECT u.password_hash 
            FROM usuario u 
            JOIN nivel n ON u.id_nivel = n.id_nivel 
            WHERE u.username = ? AND n.nombre = 'Administrador' AND u.estado = 'activo' LIMIT 1";
            
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $hash = $row['password_hash'];
        if (password_verify($password_ingresada, $hash) || $password_ingresada === $hash) {
            return true; 
        }
    }
    return false; 
}
// =========================================================================

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
    $monto_ingresado = (float)($_POST['monto_credito'] ?? 0);
    $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? '';
    $referencia = $_POST['referencia_datacredito'] ?? '';
    
    $admin_user = $_POST['admin_username'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    
    if (empty($id_cliente) || empty($monto_ingresado) || empty($fecha_vencimiento)) {
        echo json_encode(['success' => false, 'message' => 'Cliente, monto y fecha de vencimiento son obligatorios.']);
        exit;
    }

    $sqlCli = "SELECT p.tipo_persona FROM cliente c JOIN persona p ON c.id_persona = p.id_persona WHERE c.id_cliente = $id_cliente";
    $tipo_persona = $conexion->query($sqlCli)->fetch_assoc()['tipo_persona'] ?? 'Fisica';

    // 1. VALIDACIÓN BYPASS
    if ($tipo_persona === 'Fisica' && $referencia === 'BYPASS-ADMIN') {
        if (empty($admin_user) || empty($admin_password)) {
            echo json_encode(['success' => false, 'message' => 'Faltan credenciales de Administrador para autorizar el Bypass.']);
            exit;
        }
        if (!verificar_clave_admin($conexion, $admin_user, $admin_password)) {
            echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas o el usuario no tiene permisos de Administrador.']);
            exit;
        }
    }

    $sql_check = "SELECT id_credito, monto_credito FROM credito WHERE id_cliente = $id_cliente AND estado_credito = 'Activo' AND estado = 'activo' LIMIT 1";
    $res_check = $conexion->query($sql_check);

    try {
        $conexion->begin_transaction();

        if ($res_check && $res_check->num_rows > 0) {
            // == EL CLIENTE YA TIENE CRÉDITO: SE SUMARÁ (AMPLIACIÓN) ==
            $row = $res_check->fetch_assoc();
            $id_credito_existente = $row['id_credito'];
            $monto_actual_bd = (float)$row['monto_credito'];
            
            // LA SUMATORIA MATEMÁTICA
            $nuevo_monto_total = $monto_actual_bd + $monto_ingresado;

            if (empty($admin_user) || empty($admin_password)) {
                throw new Exception("Para ampliar una línea de crédito existente se requieren credenciales de Administrador.");
            }
            if (!verificar_clave_admin($conexion, $admin_user, $admin_password)) {
                throw new Exception("Credenciales incorrectas o sin permisos de Administrador para autorizar el aumento.");
            }

            // SOLUCIÓN: Sumamos el nuevo monto al saldo disponible también
            $sqlUpdate = "UPDATE credito SET monto_credito = ?, saldo_disponible = saldo_disponible + ?, fecha_vencimiento = ?, referencia_datacredito = IF(? != '', ?, referencia_datacredito) WHERE id_credito = ?";
            $stmtU = $conexion->prepare($sqlUpdate);
            $stmtU->bind_param("ddsssi", $nuevo_monto_total, $monto_ingresado, $fecha_vencimiento, $referencia, $referencia, $id_credito_existente);
            $stmtU->execute();
            
            $msg = "Se ha ampliado el crédito del cliente. Nuevo límite total: RD$ " . number_format($nuevo_monto_total, 2);
            
            $stmtL = $conexion->prepare("UPDATE cliente SET limite_credito = ? WHERE id_cliente = ?");
            $stmtL->bind_param("di", $nuevo_monto_total, $id_cliente);
            $stmtL->execute();

        } else {
            // == CRÉDITO TOTALMENTE NUEVO ==
            // SOLUCIÓN: Se inyecta el 'saldo_disponible' igual que el 'monto_credito' inicial
            $sqlI = "INSERT INTO credito (id_cliente, monto_credito, saldo_disponible, saldo_pendiente, fecha_aprobacion, fecha_vencimiento, estado_credito, referencia_datacredito, usuario_creacion, estado)
                     VALUES (?, ?, ?, 0.00, NOW(), ?, 'Activo', ?, ?, 'activo')";
            $stmtI = $conexion->prepare($sqlI);
            $stmtI->bind_param("iddssi", $id_cliente, $monto_ingresado, $monto_ingresado, $fecha_vencimiento, $referencia, $usuario_creacion);
            $stmtI->execute();
            
            $msg = 'Línea de crédito inicial aprobada y asignada al cliente correctamente.';
            
            $stmtL = $conexion->prepare("UPDATE cliente SET limite_credito = ? WHERE id_cliente = ?");
            $stmtL->bind_param("di", $monto_ingresado, $id_cliente);
            $stmtL->execute();
        }

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

        $id_credito = $_POST['id_credito'];
        $id_cliente = $_POST['id_cliente'];
        $monto_credito = (float)$_POST['monto_credito'];
        $fecha_vencimiento = $_POST['fecha_vencimiento'];
        $referencia = $_POST['referencia_datacredito'] ?? '';
        $estado_credito = $_POST['estado_credito']; 
        
        $admin_user = $_POST['admin_username'] ?? '';
        $admin_password = $_POST['admin_password'] ?? '';

        if (empty($admin_user) || empty($admin_password)) {
            throw new Exception("Toda modificación a un registro de crédito existente requiere autorización del Administrador.");
        }
        
        if (!verificar_clave_admin($conexion, $admin_user, $admin_password)) {
            throw new Exception("Credenciales incorrectas o el usuario no tiene rol de Administrador. Edición denegada.");
        }

        $stmtCheck = $conexion->prepare("SELECT saldo_pendiente FROM credito WHERE id_credito = ?");
        $stmtCheck->bind_param("i", $id_credito);
        $stmtCheck->execute();
        $saldo_actual = (float)($stmtCheck->get_result()->fetch_assoc()['saldo_pendiente'] ?? 0);

        if ($monto_credito < $saldo_actual) {
            throw new Exception("El nuevo límite (RD$ $monto_credito) no puede ser menor a la deuda que ya tiene el cliente (RD$ $saldo_actual).");
        }

        // SOLUCIÓN: Recalculamos el saldo disponible restándole la deuda al nuevo monto
        $sql = "UPDATE credito 
                SET monto_credito=?, saldo_disponible = (? - saldo_pendiente), fecha_vencimiento=?, estado_credito=?, referencia_datacredito=? 
                WHERE id_credito=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ddsssi", $monto_credito, $monto_credito, $fecha_vencimiento, $estado_credito, $referencia, $id_credito);
        $stmt->execute();

        $sqlCli = "UPDATE cliente SET limite_credito = ? WHERE id_cliente = ?";
        $stmtCli = $conexion->prepare($sqlCli);
        $stmtCli->bind_param("di", $monto_credito, $id_cliente);
        $stmtCli->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Crédito actualizado correctamente tras verificación de seguridad.']);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
    }
}
function eliminar($conexion) {
    try {
        $conexion->begin_transaction();

        $id_credito = $_POST['id_credito_eliminar'] ?? '';
        $admin_user = $_POST['admin_username_eliminar'] ?? '';
        $admin_password = $_POST['admin_password_eliminar'] ?? '';

        if (empty($id_credito) || empty($admin_user) || empty($admin_password)) {
            throw new Exception("Faltan datos o las credenciales de administrador.");
        }
        if (!verificar_clave_admin($conexion, $admin_user, $admin_password)) {
            throw new Exception("Credenciales incorrectas o el usuario no tiene rol de Administrador. Eliminación denegada.");
        }

        $stmtCheck = $conexion->prepare("SELECT saldo_pendiente, id_cliente, monto_credito FROM Credito WHERE id_credito = ?");
        $stmtCheck->bind_param("i", $id_credito);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        
        if($resCheck->num_rows === 0) {
            throw new Exception("El crédito especificado no existe.");
        }
        
        $res = $resCheck->fetch_assoc();
        
        if($res['saldo_pendiente'] > 0) {
            throw new Exception("Denegado: No se puede eliminar un crédito que tiene un saldo pendiente por cobrar.");
        }

        $idCliente = $res['id_cliente'];
        $montoCredito = (float)$res['monto_credito'];

        $stmtDel = $conexion->prepare("UPDATE Credito SET estado = 'eliminado' WHERE id_credito = ?");
        $stmtDel->bind_param("i", $id_credito);
        $stmtDel->execute();

        $stmtLim = $conexion->prepare("UPDATE Cliente SET limite_credito = GREATEST(0, limite_credito - ?) WHERE id_cliente = ?");
        $stmtLim->bind_param("di", $montoCredito, $idCliente);
        $stmtLim->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'El crédito fue cancelado y eliminado del perfil del cliente.']);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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