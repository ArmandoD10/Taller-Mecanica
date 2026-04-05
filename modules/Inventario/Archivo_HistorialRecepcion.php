<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listar($conexion);
        break;
    case 'obtener':
        obtener_detalles($conexion);
        break;
    case 'anular':
        anular_recepcion($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function listar($conexion) {
    // Modificamos la consulta para calcular dinámicamente el valor recibido o traer el valor total de la orden
    $sql = "SELECT r.id_recepcion, r.num_conduze, r.estado,
                   c.id_compra, c.monto as monto_orden, p.nombre_comercial, mo.codigo_ISO as moneda,
                   (SELECT SUM(cantidad_recibida * precio) 
                    FROM detalle_compra 
                    WHERE id_compra = r.id_compra) as monto_recibido,
                   (SELECT fecha_movimiento 
                    FROM movimiento_inventario_almacen m 
                    WHERE m.id_compra = r.id_compra 
                    ORDER BY sec_movimiento_invent ASC LIMIT 1) as fecha_recepcion
            FROM recepcion_compra r
            JOIN compra c ON r.id_compra = c.id_compra
            JOIN proveedor p ON r.id_proveedor = p.id_proveedor
            JOIN moneda mo ON r.id_moneda = mo.id_moneda
            ORDER BY r.id_recepcion DESC";
            
    $res = $conexion->query($sql);
    
    if (!$res) {
        echo json_encode(['success' => false, 'message' => 'Error en la consulta de base de datos.']);
        return;
    }
    
    echo json_encode([
        'success' => true, 
        'data' => $res->fetch_all(MYSQLI_ASSOC)
    ]);
}

function obtener_detalles($conexion) {
    $id_compra = (int)$_GET['id_compra'];
    
    // Consultamos la orden de compra y los artículos recibidos
    $sql_detalles = "SELECT d.id_articulo, a.nombre, a.num_serie, 
                            d.cantidad_pedida, d.cantidad_recibida, 
                            d.precio, (d.cantidad_recibida * d.precio) as subtotal_recibido
                     FROM detalle_compra d 
                     JOIN repuesto_articulo a ON d.id_articulo = a.id_articulo 
                     WHERE d.id_compra = ?";
                     
    $stmt = $conexion->prepare($sql_detalles);
    $stmt->bind_param("i", $id_compra);
    $stmt->execute();
    
    $detalles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'data' => $detalles
    ]);
}

function anular_recepcion($conexion) {
    $id_recepcion = (int)$_POST['id_recepcion'];
    $id_compra = (int)$_POST['id_compra'];
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
            throw new Exception("ACCESO DENEGADO: Solo un usuario con nivel de Administrador puede anular recepciones de almacén.");
        }
        
        // 2. ANULACIÓN DE LA RECEPCIÓN LÓGICA
        $sql_anular = "UPDATE recepcion_compra SET estado = 'eliminado' WHERE id_recepcion = ?";
        $stmt_anular = $conexion->prepare($sql_anular);
        $stmt_anular->bind_param("i", $id_recepcion);
        
        if (!$stmt_anular->execute()) {
            throw new Exception("Error al anular el registro de recepción.");
        }
        
        // 3. ANULAR LOS MOVIMIENTOS DE INVENTARIO VINCULADOS A ESTA COMPRA
        $sql_movimientos = "UPDATE movimiento_inventario_almacen SET estado = 'eliminado' WHERE id_compra = ?";
        $stmt_mov = $conexion->prepare($sql_movimientos);
        $stmt_mov->bind_param("i", $id_compra);
        $stmt_mov->execute();
        
        // 4. RESTAURAR CANTIDADES RECIBIDAS EN LA ORDEN DE COMPRA (Poner en 0)
        $sql_restaurar = "UPDATE detalle_compra SET cantidad_recibida = 0, estado = 'espera' WHERE id_compra = ?";
        $stmt_restaurar = $conexion->prepare($sql_restaurar);
        $stmt_restaurar->bind_param("i", $id_compra);
        $stmt_restaurar->execute();
        
        $conexion->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Recepción anulada correctamente. Los artículos han sido retirados del inventario virtual.'
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