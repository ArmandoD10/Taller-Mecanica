<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listar($conexion);
        break;
    case 'cargar_dependencias':
        cargar_dependencias($conexion);
        break;
    case 'buscar_ordenes':
        buscar_ordenes_pendientes($conexion);
        break;
    case 'guardar':
        guardar($conexion);
        break;
    case 'anular':
        anular_pago($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function listar($conexion) {
    // Listamos el historial de pagos realizados
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
    
    echo json_encode([
        'success' => true, 
        'data' => $res->fetch_all(MYSQLI_ASSOC)
    ]);
}

function cargar_dependencias($conexion) {
    $data = [];
    
    // Solo cargamos proveedores que tengan órdenes de compra activas en el sistema
    $sql_prov = "SELECT DISTINCT p.id_proveedor, p.nombre_comercial, p.RNC 
                 FROM proveedor p
                 JOIN compra c ON p.id_proveedor = c.id_proveedor
                 WHERE c.estado = 'activo' AND p.estado = 'activo'";
                 
    $data['proveedores'] = $conexion->query($sql_prov)->fetch_all(MYSQLI_ASSOC);
    $data['metodos'] = $conexion->query("SELECT id_metodo, nombre FROM metodo_pago WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    $data['monedas'] = $conexion->query("SELECT id_moneda, codigo_ISO, nombre FROM moneda WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'data' => $data
    ]);
}

function buscar_ordenes_pendientes($conexion) {
    $id_proveedor = (int)$_GET['id_proveedor'];
    
    // Busca órdenes activas y calcula cuánto se ha pagado sumando la tabla pago_compra
    $sql = "SELECT c.id_compra, c.fecha_creacion, c.monto as total_orden,
                   IFNULL((SELECT SUM(monto_pagado) FROM pago_compra WHERE id_compra = c.id_compra AND estado = 'activo'), 0) as total_pagado
            FROM compra c
            WHERE c.id_proveedor = ? AND c.estado = 'activo'
            HAVING (total_orden - total_pagado) > 0";
            
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_proveedor);
    $stmt->execute();
    $res = $stmt->get_result();
    
    echo json_encode([
        'success' => true, 
        'data' => $res->fetch_all(MYSQLI_ASSOC)
    ]);
}

function guardar($conexion) {
    $id_compra = (int)$_POST['id_compra'];
    $monto_pagado = (float)$_POST['monto_pagado'];
    $id_metodo = (int)$_POST['id_metodo'];
    $id_moneda = (int)$_POST['id_moneda'];
    $referencia_pago = trim($_POST['referencia_pago'] ?? '');
    
    $usuario = $_SESSION['id_usuario'] ?? 1;
    $fecha_actual = date('Y-m-d H:i:s');

    if ($monto_pagado <= 0) {
        echo json_encode(['success' => false, 'message' => 'El monto a pagar debe ser mayor a 0.']);
        return;
    }

    try {
        $conexion->begin_transaction();

        // Verificamos que el monto no exceda el balance pendiente
        $sql_check = "SELECT c.monto as total_orden,
                             IFNULL((SELECT SUM(monto_pagado) FROM pago_compra WHERE id_compra = c.id_compra AND estado = 'activo'), 0) as total_pagado
                      FROM compra c 
                      WHERE c.id_compra = ?";
                      
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("i", $id_compra);
        $stmt_check->execute();
        $balance_info = $stmt_check->get_result()->fetch_assoc();
        
        $balance_pendiente = $balance_info['total_orden'] - $balance_info['total_pagado'];
        
        // Damos un margen de error de 1 centavo por si hay redondeos decimales en la base de datos
        if ($monto_pagado > ($balance_pendiente + 0.01)) {
            throw new Exception("El monto introducido supera el balance pendiente de la orden.");
        }

        // Insertamos el pago
        $sql = "INSERT INTO pago_compra (id_compra, monto_pagado, fecha_pago, id_metodo, id_moneda, referencia_pago, estado, usuario_creacion) 
                VALUES (?, ?, ?, ?, ?, ?, 'activo', ?)";
                
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("idsiisi", $id_compra, $monto_pagado, $fecha_actual, $id_metodo, $id_moneda, $referencia_pago, $usuario);
        $stmt->execute();
        
        $conexion->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Abono/Pago registrado exitosamente.'
        ]);
        
    } catch (Exception $e) {
        $conexion->rollback(); 
        
        echo json_encode([
            'success' => false, 
            'message' => 'Error: ' . $e->getMessage()
        ]);
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
            throw new Exception("ACCESO DENEGADO: Solo un usuario con nivel de Administrador puede anular pagos.");
        }
        
        // 2. ANULACIÓN LÓGICA
        // Al pasar a estado eliminado, la suma de total_pagado ignorará este registro, 
        // restaurando automáticamente el balance pendiente de la orden de compra.
        $sql = "UPDATE pago_compra SET estado = 'eliminado' WHERE id_pago_compra = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la actualización en la base de datos.");
        }
        
        $conexion->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Pago anulado correctamente por el Administrador. El balance de la orden de compra ha sido restaurado.'
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