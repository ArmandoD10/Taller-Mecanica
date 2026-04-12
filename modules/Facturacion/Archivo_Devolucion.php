<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'buscar_factura':
        buscar_factura($conexion);
        break;
    case 'procesar_devolucion':
        procesar_devolucion($conexion);
        break;
}

function buscar_factura($conexion) {
    $id_factura = $_POST['id_factura'] ?? 0;
    
    // Agregamos f.id_orden a la consulta
    $sql = "SELECT 
        f.id_factura,
        f.id_orden,
        f.monto_total,
        f.fecha_emision,
        f.estado,
        DATEDIFF(NOW(), f.fecha_emision) as dias_transcurridos,
        IFNULL(p.nombre, 'Cliente Contado') as cliente_nombre
    FROM Factura_Central f
    LEFT JOIN Cliente c ON f.id_cliente = c.id_cliente
    LEFT JOIN Persona p ON c.id_persona = p.id_persona
    WHERE f.id_factura = ?;";
            
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_factura);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if($f = $res->fetch_assoc()) {
        
        // --- VALIDACIÓN DE ORDEN ACTIVA ---
        // Si la factura tiene un id_orden, significa que viene de Taller/Servicio
        if (!empty($f['id_orden'])) {
            echo json_encode([
                'success' => false, 
                'message' => 'Esta factura es de una orden (Taller). Debe ser procesada en la gestión de garantía, no como devolución de mercancía.'
            ]);
            return; // Detenemos la ejecución
        }

        // Si pasa la validación, enviamos los datos normalmente
        echo json_encode(['success' => true, 'data' => $f]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Factura no encontrada']);
    }
}

function procesar_devolucion($conexion) {
    // 1. CAPTURA DE DATOS (Nombres exactos del FormData)
    $id_factura = $_POST['id_factura'] ?? null;
    $user_admin = $_POST['user_admin'] ?? '';
    $pass_admin = $_POST['pass_admin'] ?? '';
    $motivo     = $_POST['motivo'] ?? '';
    $buen_estado= $_POST['buen_estado'] ?? 'false';
    $monto       = $_POST['monto'] ?? 0;
    $id_usuario_creacion = $_SESSION['id_usuario'] ?? null;

    // 2. VALIDACIÓN DE CREDENCIALES ADMIN
    $sqlAdmin = "SELECT id_usuario FROM Usuario WHERE username = ? AND password_hash = ? AND id_nivel = 1 LIMIT 1";
    $stmtAdmin = $conexion->prepare($sqlAdmin);
    $stmtAdmin->bind_param("ss", $user_admin, $pass_admin);
    $stmtAdmin->execute();
    $resAdmin = $stmtAdmin->get_result();

    if ($resAdmin->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Credenciales de administrador inválidas']);
        exit;
    }
    $id_admin = $resAdmin->fetch_assoc()['id_usuario'];

    // 3. PROCESO DE DEVOLUCIÓN
    $conexion->begin_transaction();
    try {
        // A. Insertar en tabla Devolucion
        $estado_txt = ($buen_estado === 'true') ? 'Buen Estado' : 'Dañado/Uso';
        $sqlDev = "INSERT INTO Devolucion (id_factura, id_usuario_admin, motivo, estado_producto, monto_devuelto, estado, usuario_creacion) 
                   VALUES (?, ?, ?, ?, ?, 'activo', ?)";
        $stmt = $conexion->prepare($sqlDev);
        $stmt->bind_param("iissdi", $id_factura, $id_admin, $motivo, $estado_txt, $monto, $id_usuario_creacion);
        $stmt->execute();

        // B. Actualizar Inventario (Solo si está en Buen Estado)
        if ($buen_estado === 'true') {
            $sqlItems = "SELECT id_articulo, cantidad FROM Detalle_Factura WHERE id_factura = ?";
            $stItems = $conexion->prepare($sqlItems);
            $stItems->bind_param("i", $id_factura);
            $stItems->execute();
            $items = $stItems->get_result();

            while ($item = $items->fetch_assoc()) {
                // Sumar al inventario de la sucursal actual
                $sqlInv = "UPDATE Inventario SET cantidad = cantidad + ? 
                           WHERE id_articulo = ? AND id_gondola IN (
                               SELECT id_gondola FROM Gondola g 
                               JOIN Almacen a ON g.id_almacen = a.id_almacen 
                               WHERE a.id_sucursal = ?
                           )";
                $stInv = $conexion->prepare($sqlInv);
                $sucursal = $_SESSION['id_sucursal'];
                $stInv->bind_param("iii", $item['cantidad'], $item['id_articulo'], $sucursal);
                $stInv->execute();
            }
        }

        // C. Inactivar Factura
        $sqlUpd = "UPDATE Factura_Central SET estado = 'inactivo' WHERE id_factura = ?";
        $stUpd = $conexion->prepare($sqlUpd);
        $stUpd->bind_param("i", $id_factura);
        $stUpd->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Devolución procesada y stock actualizado']);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}