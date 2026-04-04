<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? 0;

// 1. IDENTIFICACIÓN DE SUCURSAL (Crucial para filtrar los datos)
// Usamos Empleado_Sucursal como confirmaste
try {
    $sql_suc = "SELECT s.id_sucursal, s.nombre 
                FROM Sucursal s
                INNER JOIN Empleado_Sucursal es ON s.id_sucursal = es.id_sucursal
                INNER JOIN Empleado_Usuario eu ON es.id_empleado = eu.id_empleado
                WHERE eu.id_usuario = ? 
                AND es.estado = 'activo' 
                AND es.fecha_fin IS NULL 
                LIMIT 1";

    $stmt_s = $conexion->prepare($sql_suc);
    $stmt_s->bind_param("i", $id_usuario);
    $stmt_s->execute();
    $res_suc = $stmt_s->get_result()->fetch_assoc();

    $id_sucursal_user = $res_suc['id_sucursal'] ?? 0;
    $_SESSION['nombre_sucursal'] = $res_suc['nombre'] ?? 'Sucursal no encontrada';

} catch (Exception $e) {
    $id_sucursal_user = 0;
}

switch ($action) {
   case 'listar_pendientes_recepcion':
    $sql = "SELECT 
                dr.num_conduze, 
                dr.id_articulo, 
                ra.nombre, 
                ra.imagen, 
                i.cantidad as cantidad_recibida, -- USAR LA CANTIDAD DE INVENTARIO, NO DE RECEPCION
                a.nombre as almacen_nombre,
                rc.id_compra
            FROM Inventario i
            INNER JOIN Repuesto_Articulo ra ON i.id_articulo = ra.id_articulo
            INNER JOIN Gondola g ON i.id_gondola = g.id_gondola
            INNER JOIN Almacen a ON g.id_almacen = a.id_almacen
            LEFT JOIN Detalle_Recepcion dr ON i.id_articulo = dr.id_articulo
            LEFT JOIN Recepcion_Compra rc ON dr.num_conduze = rc.num_conduze
            WHERE i.id_gondola = 1 
            AND a.id_sucursal = ? 
            AND i.cantidad > 0 -- Si llega a 0, desaparece de la vista
            GROUP BY i.id_articulo, dr.num_conduze";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_sucursal_user);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    break;

    // Añadir este caso a tu switch en Archivo_MovimientoS.php
case 'get_sucursales':
    $sql = "SELECT id_sucursal, nombre FROM Sucursal WHERE estado = 'activo'";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
    break;

case 'buscar_sucursal':
    $term = $conexion->real_escape_string($_GET['term'] ?? '');
    $sql = "SELECT id_sucursal, nombre FROM Sucursal 
            WHERE nombre LIKE '%$term%' AND estado = 'activo' LIMIT 5";
    $res = $conexion->query($sql);
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    break;

case 'get_almacenes_por_sucursal':
    $id_suc = (int)$_GET['id_sucursal'];
    $sql = "SELECT id_almacen, nombre FROM Almacen WHERE id_sucursal = ? AND estado = 'activo'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_suc);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    break;

    case 'get_gondolas_almacen':
    // Recibimos el ID del almacén seleccionado en el modal
    $id_alm = (int)$_GET['id_almacen'];
    
    // Buscamos góndolas de ESE almacén específico
    $sql = "SELECT id_gondola, numero, niveles 
            FROM Gondola 
            WHERE id_almacen = ? 
            AND id_gondola != 1 
            AND estado = 'activo'";
            
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_alm);
    $stmt->execute();
    $datos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $datos]);
    break;

    case 'procesar_ubicacion':
    $data = json_decode(file_get_contents('php://input'), true);
    $id_compra_real = $data['id_compra']; // Recibimos el dato real

    try {
        $conexion->begin_transaction();
        
        // A. Restar de Recepción
        $stmt_out = $conexion->prepare("UPDATE Inventario SET cantidad = cantidad - ? WHERE id_articulo = ? AND id_gondola = 1");
        $stmt_out->bind_param("ii", $data['cantidad'], $data['id_articulo']);
        $stmt_out->execute();

        // B. Sumar a destino
        $stmt_in = $conexion->prepare("INSERT INTO Inventario (id_articulo, id_gondola, nivel, cantidad, estado) 
                                       VALUES (?, ?, ?, ?, 'activo') 
                                       ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad), nivel = VALUES(nivel)");
        $stmt_in->bind_param("iiii", $data['id_articulo'], $data['id_gondola'], $data['nivel'], $data['cantidad']);
        $stmt_in->execute();

        // C. Historial con el ID DE COMPRA REAL
        $sql_mov = "INSERT INTO Movimiento_Inventario_Almacen (id_compra, id_gondola, id_tipo_movimiento, estado) 
                    VALUES (?, ?, 1, 'activo')";
        $stmt_mov = $conexion->prepare($sql_mov);
        $stmt_mov->bind_param("ii", $id_compra_real, $data['id_gondola']); // Ya no es 0 ni NULL
        $stmt_mov->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Ubicado con éxito']);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;
}