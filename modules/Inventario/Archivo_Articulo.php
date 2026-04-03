<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

// Obtener la acción de la URL
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listar_articulos($conexion);
        break;
    case 'obtener':
        obtener_articulo($conexion);
        break;
    case 'guardar':
        guardar_articulo($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

/**
 * Lista todos los artículos con un JOIN para obtener el nombre del proveedor
 */
function listar_articulos($conexion) {
    $sql = "SELECT a.*, p.nombre_comercial AS nombre_proveedor 
            FROM Repuesto_Articulo a
            LEFT JOIN Proveedor p ON a.id_proveedor = p.id_proveedor
            WHERE a.estado != 'eliminado'
            ORDER BY a.id_articulo DESC";
            
    $res = $conexion->query($sql);
    $data = $res->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
}

/**
 * Obtiene los datos de un solo artículo para edición
 */
function obtener_articulo($conexion) {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM Repuesto_Articulo WHERE id_articulo = $id";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_assoc()]);
}

/**
 * Guarda o Actualiza un artículo y gestiona la subida de imágenes
 */
function guardar_articulo($conexion) {
    // Recolección de datos del FormData
    $id = $_POST['id_articulo'] ?? '';
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $serie = $conexion->real_escape_string($_POST['num_serie']);
    $costo = $_POST['precio_costo'] ?: 0;
    $id_proveedor = $_POST['id_proveedor'];
    $id_marca = $_POST['id_marca_producto'];
    $estado = $_POST['estado'] ?? 'activo';
    $usuario = $_SESSION['id_usuario'] ?? 1; // Usuario por defecto si no hay sesión
    $fecha_cad = !empty($_POST['fecha_caducidad']) ? "'".$_POST['fecha_caducidad']."'" : "NULL";

    // Iniciamos transacción para seguridad de datos
    $conexion->begin_transaction();

    try {
        if (empty($id)) {
            // INSERTAR NUEVO REGISTRO
            $sql = "INSERT INTO Repuesto_Articulo 
                    (nombre, num_serie, precio_costo, id_proveedor, id_marca_producto, estado, fecha_caducidad, usuario_creacion, fecha_creacion) 
                    VALUES (?, ?, ?, ?, ?, ?, $fecha_cad, ?, NOW())";
            
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ssdiisi", $nombre, $serie, $costo, $id_proveedor, $id_marca, $estado, $usuario);
            $stmt->execute();
            $id_final = $conexion->insert_id;
            $mensaje = "Artículo registrado correctamente";
        } else {
            // ACTUALIZAR REGISTRO EXISTENTE
            $sql = "UPDATE Repuesto_Articulo SET 
                    nombre = ?, num_serie = ?, precio_costo = ?, id_proveedor = ?, 
                    id_marca_producto = ?, estado = ?, fecha_caducidad = $fecha_cad 
                    WHERE id_articulo = ?";
            
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ssdiisi", $nombre, $serie, $costo, $id_proveedor, $id_marca, $estado, $id);
            $stmt->execute();
            $id_final = $id;
            $mensaje = "Artículo actualizado correctamente";
        }

        // --- LÓGICA DE IMAGEN COMPLEJA ---
        if (isset($_FILES['imagen_file']) && $_FILES['imagen_file']['error'] === UPLOAD_ERR_OK) {
            
            $file_tmp = $_FILES['imagen_file']['tmp_name'];
            $file_name = $_FILES['imagen_file']['name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Definimos el nombre único: repuesto_ID.ext
            $nuevo_nombre = "repuesto_" . $id_final . "." . $ext;
            
            // Rutas (Asegúrate que la carpeta /Taller/img/ exista y tenga permisos de escritura)
            $directorio_destino = $_SERVER['DOCUMENT_ROOT'] . "/Taller/img/";
            $ruta_completa = $directorio_destino . $nuevo_nombre;
            $ruta_db = "/Taller/img/" . $nuevo_nombre;

            // Si ya existe una imagen vieja, podrías borrarla aquí con unlink() si quisieras

            if (move_uploaded_file($file_tmp, $ruta_completa)) {
                // Actualizamos la ruta relativa en la base de datos
                $conexion->query("UPDATE Repuesto_Articulo SET imagen = '$ruta_db' WHERE id_articulo = $id_final");
            } else {
                throw new Exception("Error al mover la imagen al servidor.");
            }
        }

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => $mensaje, 'id' => $id_final]);

    } catch (Exception $e) {
        $conexion->rollback(); // Si algo falla, deshace los cambios en la DB
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>