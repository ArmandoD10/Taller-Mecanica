<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listar($conexion);
        break;
    case 'buscar_empleado':
        buscarEmpleado($conexion);
        break;

    // Añade este caso dentro del switch en Archivo_Directorio.php
case 'listar_tipos':
    $sql = "SELECT id_tipo, nombre, categoria FROM Tipo_Documento WHERE estado = 'activo'";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
    break;
    case 'guardar':
        guardar($conexion);
        break;

// Añade este caso al switch ($action)
case 'eliminar':
    $id = (int)$_POST['id_documento'];
    // Realizamos un borrado lógico cambiando el estado
    $sql = "UPDATE documento SET estado = 'eliminado' WHERE id_documento = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Documento removido del directorio']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al intentar eliminar']);
    }
    break;

    // Dentro del switch ($action)
case 'guardar_tipo':
    $id = $_POST['id_tipo_doc'] ?? '';
    $nombre = $_POST['nombre_tipo'];
    $categoria = $_POST['cat_tipo'];

    if ($id == '') {
        $sql = "INSERT INTO Tipo_Documento (nombre, categoria, fecha_creacion) VALUES (?, ?, NOW())";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ss", $nombre, $categoria);
    } else {
        $sql = "UPDATE Tipo_Documento SET nombre = ?, categoria = ? WHERE id_tipo = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssi", $nombre, $categoria, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tipo de documento actualizado']);
    } else {
        echo json_encode(['success' => false, 'message' => $conexion->error]);
    }
    break;

case 'eliminar_tipo':
    $id = (int)$_POST['id_tipo'];
    $sql = "UPDATE Tipo_Documento SET estado = 'eliminado' WHERE id_tipo = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    echo json_encode(['success' => $stmt->execute()]);
    break;

        // Agrega esto dentro del switch ($action) en Archivo_Directorio.php
case 'obtener_detalle':
        $id = (int)$_GET['id'];
        // Hacemos el JOIN desde Documento -> Empleado -> Persona
        $sql = "SELECT 
                    d.*, 
                    det.contenido_html, 
                    p.nombre as nombre_empleado, 
                    p.cedula as cedula_empleado
                FROM documento d 
                INNER JOIN detalle_documento det ON d.id_documento = det.id_documento 
                LEFT JOIN Empleado e ON d.id_empleado = e.id_empleado
                LEFT JOIN Persona p ON e.id_persona = p.id_persona
                WHERE d.id_documento = $id";
                
        $res = $conexion->query($sql);
        if ($res && $res->num_rows > 0) {
            echo json_encode(['success' => true, 'data' => $res->fetch_assoc()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Documento no encontrado']);
        }
        break;
}

function listar($conexion) {
    $sql = "SELECT d.*, t.nombre as tipo_nombre, t.categoria 
            FROM documento d 
            INNER JOIN Tipo_Documento t ON d.id_tipo = t.id_tipo 
            WHERE d.estado = 'activo'";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function buscarEmpleado($conexion) {
    $q = $_GET['q'] ?? '';
    // Concatenamos los 4 campos de la tabla Persona con espacios
    $sql = "SELECT 
                e.id_empleado, 
                CONCAT_WS(' ', p.nombre, p.nombre_dos, p.apellido_p, p.apellido_m) AS nombre_completo, 
                p.cedula 
            FROM Empleado e 
            INNER JOIN Persona p ON e.id_persona = p.id_persona 
            WHERE CONCAT(p.nombre, ' ', p.apellido_p) LIKE '%$q%' 
            AND e.estado = 'activo' 
            LIMIT 5";
            
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function guardar($conexion) {
    $id_tipo = $_POST['id_tipo'];
    $titulo = $_POST['titulo'];
    $contenido = $_POST['cuerpo_doc']; // El HTML generado
    $id_empleado = $_POST['id_empleado'] ?? null;
    $usuario = $_SESSION['id_usuario'] ?? 1;

    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare("INSERT INTO documento (id_tipo, id_empleado, titulo, usuario_creacion) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisi", $id_tipo, $id_empleado, $titulo, $usuario);
        $stmt->execute();
        $id_doc = $conexion->insert_id;

        $stmt2 = $conexion->prepare("INSERT INTO detalle_documento (id_documento, contenido_html) VALUES (?, ?)");
        $stmt2->bind_param("is", $id_doc, $contenido);
        $stmt2->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Documento generado correctamente']);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>