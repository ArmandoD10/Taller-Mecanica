<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listar($conexion);
        break;
    case 'obtener_detalle':
        obtener_detalle($conexion);
        break;
    case 'anular':
        anular_pago($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function listar($conexion) {
    // Listamos el historial de pagos combinando con la orden de compra y el proveedor
    $sql = "SELECT pc.id_pago_compra, pc.monto_pagado, pc.fecha_pago, pc.referencia_pago, pc.estado,
                   c.id_compra, p.nombre_comercial, 
                   m.nombre as metodo_pago, mo.codigo_ISO as moneda
            FROM pago_compra pc
            JOIN compra c ON pc.id_compra = c.id_compra
            JOIN proveedor p ON c.id_proveedor = p.id_proveedor
            JOIN metodo_pago m ON pc.id_metodo = m.id_metodo
            JOIN moneda mo ON pc.id_moneda = mo.id_moneda
            ORDER BY pc.id_pago_compra DESC";
            
    $res = $conexion->query($sql);
    
    if (!$res) {
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $conexion->error]);
        return;
    }
    
    echo json_encode([
        'success' => true, 
        'data' => $res->fetch_all(MYSQLI_ASSOC)
    ]);
}

function obtener_detalle($conexion) {
    $id_pago = (int)($_GET['id_pago'] ?? 0);
    
    // CORRECCIÓN APLICADA: Ahora extraemos "u.username" en lugar de "u.nombre"
    $sql = "SELECT pc.id_pago_compra, pc.monto_pagado, pc.fecha_pago, pc.referencia_pago, pc.estado,
                   c.id_compra, p.nombre_comercial as proveedor, p.RNC,
                   m.nombre as metodo_pago, mo.codigo_ISO as moneda, mo.nombre as nombre_moneda,
                   IFNULL(u.username, CONCAT('ID Usuario: ', pc.usuario_creacion)) as usuario_registro
            FROM pago_compra pc
            JOIN compra c ON pc.id_compra = c.id_compra
            JOIN proveedor p ON c.id_proveedor = p.id_proveedor
            JOIN metodo_pago m ON pc.id_metodo = m.id_metodo
            JOIN moneda mo ON pc.id_moneda = mo.id_moneda
            LEFT JOIN usuario u ON pc.usuario_creacion = u.id_usuario
            WHERE pc.id_pago_compra = ?";
            
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error en la consulta SQL: ' . $conexion->error]);
        return;
    }

    $stmt->bind_param("i", $id_pago);
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta: ' . $conexion->error]);
        return;
    }
    
    $detalle = $stmt->get_result()->fetch_assoc();
    
    if ($detalle) {
        echo json_encode(['success' => true, 'data' => $detalle]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró el registro del pago en la base de datos.']);
    }
}

function anular_pago($conexion) {
    $id = (int)$_POST['id_pago_compra'];
    $id_usuario = $_SESSION['id_usuario'] ?? 0;
    
    try {
        $conexion->begin_transaction();
        
        // 1. VALIDACIÓN DE SEGURIDAD: COMPROBAR ROL DE ADMINISTRADOR
        $sql_admin = "SELECT n.nombre 
                      FROM usuario u 
                      JOIN nivel n ON u.id_nivel = n.id_nivel 
                      WHERE u.id_usuario = ?";
        $stmt_admin = $conexion->prepare($sql_admin);
        $stmt_admin->bind_param("i", $id_usuario);
        $stmt_admin->execute();
        $resultado_rol = $stmt_admin->get_result()->fetch_assoc();
        $nombre_rol = $resultado_rol['nombre'] ?? '';
        
        // Comprobamos si la palabra "Administrador" o "Admin" está en el rol
        if (stripos($nombre_rol, 'Admin') === false) {
            throw new Exception("ACCESO DENEGADO: Solo un usuario con nivel de Administrador puede anular pagos a proveedores.");
        }
        
        // 2. ANULACIÓN LÓGICA DEL PAGO
        $sql = "UPDATE pago_compra SET estado = 'eliminado' WHERE id_pago_compra = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la actualización en la base de datos.");
        }
        
        $conexion->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Pago anulado correctamente por el Administrador. El dinero vuelve a reflejarse como deuda pendiente en la Orden de Compra.'
        ]);
        
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
}
?>