<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'buscar_articulo':
        $q = $_GET['q'];
        // Ajusta los nombres de columnas segun tu tabla de articulos
        $sql = "SELECT id_articulo, nombre, precio_venta, imagen FROM repuesto_articulo 
                WHERE nombre LIKE '%$q%' AND estado = 'activo' LIMIT 5";
        $res = $conexion->query($sql);
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        break;

   case 'guardar':
    // Capturamos el usuario de la sesión
    $id_usuario_sesion = $_SESSION['id_usuario'] ?? 1; // 1 por defecto si no hay sesión
    $id_paquete = $_POST['id_paquete'] ?? ''; 
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $estado = $_POST['estado']; 
    $total = (float)$_POST['total'];
    $articulos = json_decode($_POST['items'], true);

    $conexion->begin_transaction();

    try {
        if (empty($id_paquete)) {
            // INSERT NUEVO con usuario_creacion
            $stmt = $conexion->prepare("INSERT INTO Paquete_Servicio (nombre_paquete, precio_total, estado, usuario_creacion) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sdsi", $nombre, $total, $estado, $id_usuario_sesion);
        } else {
            // UPDATE EXISTENTE
            $stmt = $conexion->prepare("UPDATE Paquete_Servicio SET nombre_paquete = ?, precio_total = ?, estado = ? WHERE id_paquete = ?");
            $stmt->bind_param("sdsi", $nombre, $total, $estado, $id_paquete);
            
            // Limpiamos detalle para actualizar items
            $conexion->query("DELETE FROM Paquete_Detalle_Articulo WHERE id_paquete = $id_paquete");
        }

        $stmt->execute();
        $id_final = empty($id_paquete) ? $conexion->insert_id : $id_paquete;

        // Insertar los artículos del combo
        $stmtI = $conexion->prepare("INSERT INTO Paquete_Detalle_Articulo (id_paquete, id_articulo, cantidad, precio_unidad_con_descuento) VALUES (?, ?, ?, ?)");
        
        foreach ($articulos as $art) {
            $id_art = (int)$art['id'];
            $cant = (int)$art['cantidad'];
            $precio_con_desc = (float)$art['precio'] * 0.98;

            $stmtI->bind_param("iiid", $id_final, $id_art, $cant, $precio_con_desc);
            $stmtI->execute();
        }

        $conexion->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

    case 'listar':
    // Usamos una subconsulta para traer la imagen del primer producto del paquete
    $sql = "SELECT p.*, 
            (SELECT ra.imagen FROM Paquete_Detalle_Articulo pda 
             JOIN repuesto_articulo ra ON pda.id_articulo = ra.id_articulo 
             WHERE pda.id_paquete = p.id_paquete LIMIT 1) as imagen_portada
            FROM Paquete_Servicio p 
            WHERE p.estado != 'eliminado' 
            ORDER BY p.id_paquete DESC";
            
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
    break;

    // Dentro del switch ($action) en Archivo_Paquetes.php
// Dentro del switch ($action) en Archivo_Paquetes.php

case 'ver_detalle':
    $id = (int)$_GET['id'];
    // 🔥 ACTUALIZADO: Incluimos id_articulo, precio e imagen para poder reconstruir el objeto en JS
    $sql = "SELECT ra.id_articulo, ra.nombre, ra.precio_venta as precio, ra.imagen, pda.cantidad 
            FROM Paquete_Detalle_Articulo pda 
            JOIN repuesto_articulo ra ON pda.id_articulo = ra.id_articulo 
            WHERE pda.id_paquete = $id";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'items' => $res->fetch_all(MYSQLI_ASSOC)]);
    break;

case 'cambiar_estado':
    $id = (int)$_POST['id'];
    $nuevo_estado = $_POST['estado']; // 'activo' o 'inactivo'
    $sql = "UPDATE Paquete_Servicio SET estado = '$nuevo_estado' WHERE id_paquete = $id";
    if($conexion->query($sql)) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false]);
    break;

case 'eliminar':
    $id = (int)$_POST['id'];
    // Marcamos como eliminado para no borrar el historial de ventas
    $sql = "UPDATE Paquete_Servicio SET estado = 'eliminado' WHERE id_paquete = $id";
    if($conexion->query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conexion->error]);
    }
    break;
}