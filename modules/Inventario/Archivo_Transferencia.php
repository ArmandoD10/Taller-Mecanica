<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_usuario_sesion = $_SESSION['id_usuario'] ?? 0;

// 1. OBTENER INFORMACIÓN DEL EMPLEADO Y SUCURSAL (Variables Globales)
$id_empleado_actual = 0;
$id_sucursal_user = 0; // <--- ESTA ES LA QUE DABA ERROR

if ($id_usuario_sesion > 0) {
    $sql_info = "SELECT eu.id_empleado, es.id_sucursal 
                 FROM empleado_usuario eu
                 INNER JOIN empleado_sucursal es ON eu.id_empleado = es.id_empleado
                 WHERE eu.id_usuario = ? 
                   AND es.estado = 'activo' 
                   AND es.fecha_fin IS NULL 
                 LIMIT 1";

    $stmt_info = $conexion->prepare($sql_info);
    $stmt_info->bind_param("i", $id_usuario_sesion);
    $stmt_info->execute();
    $res_info = $stmt_info->get_result()->fetch_assoc();

    if ($res_info) {
        $id_empleado_actual = $res_info['id_empleado'];
        $id_sucursal_user = $res_info['id_sucursal'];
    }
}

// Verificación de seguridad antes de procesar cualquier acción
if ($action == 'crear_solicitud' && ($id_empleado_actual == 0 || $id_sucursal_user == 0)) {
    echo json_encode([
        'success' => false, 
        'message' => "Error de sesión: Empleado($id_empleado_actual) o Sucursal($id_sucursal_user) no identificados."
    ]);
    exit;
}

switch ($action) {
    case 'listar_mis_pedidos':
    // Cambiamos el JOIN para buscar en detalle_transferencia (dt)
    $sql = "SELECT 
                t.id_transferencia, 
                t.estado, 
                t.fecha_solicitud, 
                dt.cantidad, 
                ra.nombre as producto, 
                ra.imagen, 
                s.nombre as sucursal_origen 
            FROM Transferencia t
            INNER JOIN Detalle_Transferencia dt ON t.id_transferencia = dt.id_transferencia
            INNER JOIN Repuesto_Articulo ra ON dt.id_articulo = ra.id_articulo
            INNER JOIN Sucursal s ON t.id_sucursal_origen = s.id_sucursal
            WHERE t.id_sucursal_destino = ? 
              AND t.estado IN ('pendiente', 'en_transito')
            ORDER BY t.fecha_solicitud DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_sucursal_user); // Usamos la variable que definimos al inicio
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    break;

    case 'listar_por_despachar':
    $sql = "SELECT 
                t.id_transferencia, 
                t.fecha_solicitud, 
                dt.cantidad, 
                ra.nombre as producto, 
                ra.imagen, 
                s.nombre as sucursal_destino 
            FROM Transferencia t
            INNER JOIN Detalle_Transferencia dt ON t.id_transferencia = dt.id_transferencia
            INNER JOIN Repuesto_Articulo ra ON dt.id_articulo = ra.id_articulo
            INNER JOIN Sucursal s ON t.id_sucursal_destino = s.id_sucursal
            WHERE t.id_sucursal_origen = ? 
              AND t.estado = 'pendiente'
            ORDER BY t.fecha_solicitud DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_sucursal_user);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    break;
    

    case 'recibir':
    $id_trans = (int)$_GET['id'];
    $conexion->begin_transaction();
    try {
        // 1. Obtener datos del DESTINO real
        $sql = "SELECT t.id_sucursal_destino, dt.id_articulo, dt.cantidad 
                FROM Transferencia t
                INNER JOIN Detalle_Transferencia dt ON t.id_transferencia = dt.id_transferencia
                WHERE t.id_transferencia = ? LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_trans);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        $id_dest = $res['id_sucursal_destino'];
        $id_art = $res['id_articulo'];
        $cant = $res['cantidad'];

        // 2. SUMAR al inventario de la sucursal DESTINO en la góndola 1
        // CRUCIAL: Debemos buscar el ID de la góndola 1 que pertenece a ESA sucursal
        $sqlGondola = "SELECT g.id_gondola FROM Gondola g 
                       INNER JOIN Almacen a ON g.id_almacen = a.id_almacen 
                       WHERE a.id_sucursal = ? AND g.numero = 1 LIMIT 1";
        $stmtG = $conexion->prepare($sqlGondola);
        $stmtG->bind_param("i", $id_dest);
        $stmtG->execute();
        $id_gondola_recepcion = $stmtG->get_result()->fetch_assoc()['id_gondola'] ?? 0;

        if($id_gondola_recepcion == 0) throw new Exception("No se encontró área de recepción en sucursal destino.");

        $sqlSumar = "INSERT INTO Inventario (id_articulo, id_gondola, nivel, cantidad, estado) 
                     VALUES (?, ?, 1, ?, 'activo') 
                     ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)";
        $stmtSumar = $conexion->prepare($sqlSumar);
        $stmtSumar->bind_param("iii", $id_art, $id_gondola_recepcion, $cant);
        $stmtSumar->execute();

        // 3. Finalizar
        $conexion->query("UPDATE Transferencia SET estado = 'completado', fecha_recepcion = CURRENT_TIMESTAMP WHERE id_transferencia = $id_trans");

        $conexion->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

    case 'crear_solicitud':
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // VALIDACIÓN CRÍTICA: Si el ID de sucursal destino es 0, no podemos seguir
    if ($id_sucursal_user == 0) {
        echo json_encode(['success' => false, 'message' => 'Error: Tu usuario no tiene una sucursal destino asignada (ID 0).']);
        break;
    }

    $conexion->begin_transaction();
    try {
        $id_articulo = (int)$data['id_articulo'];
        $cantidad = (int)$data['cantidad'];
        
        foreach ($data['sucursales'] as $nombreSucursal) {
            // 1. Obtener ID de la sucursal de origen (A quien le pedimos)
            $stmtS = $conexion->prepare("SELECT id_sucursal FROM sucursal WHERE nombre = ? LIMIT 1");
            $stmtS->bind_param("s", $nombreSucursal);
            $stmtS->execute();
            $resS = $stmtS->get_result()->fetch_assoc();
            $id_origen = $resS['id_sucursal'] ?? 0;

            if ($id_origen > 0) {
                // 2. INSERTAR CABECERA
                // id_sucursal_user es TU sucursal (destino)
                // id_empleado_actual es TU ID de empleado
                $sqlCab = "INSERT INTO Transferencia (id_sucursal_origen, id_sucursal_destino, id_usuario_solicita, estado) 
                           VALUES (?, ?, ?, 'pendiente')";
                $stmtCab = $conexion->prepare($sqlCab);
                $stmtCab->bind_param("iii", $id_origen, $id_sucursal_user, $id_empleado_actual);
                
                if (!$stmtCab->execute()) {
                    throw new Exception("Error en BD: " . $conexion->error);
                }

                $id_nueva_trans = $conexion->insert_id;

                // 3. INSERTAR DETALLE
                $sqlDet = "INSERT INTO Detalle_Transferencia (id_transferencia, id_articulo, cantidad, id_gondola_origen) 
                           VALUES (?, ?, ?, 0)";
                $stmtDet = $conexion->prepare($sqlDet);
                $stmtDet->bind_param("iii", $id_nueva_trans, $id_articulo, $cantidad);
                $stmtDet->execute();
            }
        }
        
        $conexion->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

    case 'despachar':
    $id_trans = (int)$_GET['id'];
    $conexion->begin_transaction();
    try {
        // 1. Obtener datos de la transferencia
        $sqlInfo = "SELECT t.id_sucursal_origen, dt.id_articulo, dt.cantidad 
                    FROM Transferencia t
                    INNER JOIN Detalle_Transferencia dt ON t.id_transferencia = dt.id_transferencia
                    WHERE t.id_transferencia = ? LIMIT 1";
        $stmtInfo = $conexion->prepare($sqlInfo);
        $stmtInfo->bind_param("i", $id_trans);
        $stmtInfo->execute();
        $res = $stmtInfo->get_result()->fetch_assoc();

        if (!$res) throw new Exception("No se encontró la transferencia.");

        $id_origen = $res['id_sucursal_origen'];
        $id_art = $res['id_articulo'];
        $cant = $res['cantidad'];

        // 2. BUSCAR GÓNDOLA CON STOCK
        // 2. BUSCAR GÓNDOLA CON STOCK
            $sqlStock = "SELECT i.id_gondola, i.cantidad 
                        FROM Inventario i
                        INNER JOIN Gondola g ON i.id_gondola = g.id_gondola
                        INNER JOIN Almacen a ON g.id_almacen = a.id_almacen
                        WHERE i.id_articulo = ? 
                        AND a.id_sucursal = ? 
                        AND i.cantidad >= ?
                        -- Quitamos el filtro de id_gondola != 1 para que busque en TODO
                        LIMIT 1";
        
        $stmtStock = $conexion->prepare($sqlStock);
        $stmtStock->bind_param("iii", $id_art, $id_origen, $cant); // corregido ->
        $stmtStock->execute(); // corregido ->
        $resStock = $stmtStock->get_result()->fetch_assoc();

        if (!$resStock) {
            throw new Exception("La sucursal de origen no tiene stock suficiente.");
        }

        $id_gondola_origen = $resStock['id_gondola'];

        // 3. RESTAR EL STOCK
        $sqlRestar = "UPDATE Inventario SET cantidad = cantidad - ? 
                      WHERE id_articulo = ? AND id_gondola = ?";
        $stmtRestar = $conexion->prepare($sqlRestar);
        $stmtRestar->bind_param("iii", $cant, $id_art, $id_gondola_origen);
        
        if (!$stmtRestar->execute()) {
            throw new Exception("Error al restar stock.");
        }

        // 4. ACTUALIZAR DETALLE Y CABECERA
        // Corregido: Usamos bind_param también aquí para mayor seguridad
        $stmtDet = $conexion->prepare("UPDATE Detalle_Transferencia SET id_gondola_origen = ? WHERE id_transferencia = ?");
        $stmtDet->bind_param("ii", $id_gondola_origen, $id_trans);
        $stmtDet->execute();
        
        $sqlUpd = "UPDATE Transferencia SET 
                    estado = 'en_transito', 
                    fecha_envio = CURRENT_TIMESTAMP, 
                    id_usuario_despacha = ? 
                   WHERE id_transferencia = ?";
        $stmtUpd = $conexion->prepare($sqlUpd);
        $stmtUpd->bind_param("ii", $id_empleado_actual, $id_trans);
        $stmtUpd->execute();

        $conexion->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

    case 'listar_historial':
    $sql = "SELECT 
                t.id_transferencia, 
                t.fecha_recepcion as fecha_final, 
                dt.cantidad, 
                ra.nombre as producto, 
                s_orig.nombre as sucursal_origen,
                s_dest.nombre as sucursal_destino
            FROM Transferencia t
            INNER JOIN Detalle_Transferencia dt ON t.id_transferencia = dt.id_transferencia
            INNER JOIN Repuesto_Articulo ra ON dt.id_articulo = ra.id_articulo
            INNER JOIN Sucursal s_orig ON t.id_sucursal_origen = s_orig.id_sucursal
            INNER JOIN Sucursal s_dest ON t.id_sucursal_destino = s_dest.id_sucursal
            WHERE (t.id_sucursal_origen = ? OR t.id_sucursal_destino = ?) 
              AND t.estado = 'completado'
            ORDER BY t.fecha_recepcion DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $id_sucursal_user, $id_sucursal_user);
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    break;
}