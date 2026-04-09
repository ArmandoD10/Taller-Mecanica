<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar_tipos':
        $res = $conexion->query("SELECT * FROM Tipo_Oferta WHERE estado = 'activo'");
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        break;

    case 'listar_ofertas':
        $sql = "SELECT o.*, t.nombre as tipo_nombre 
                FROM Oferta o 
                JOIN Tipo_Oferta t ON o.id_tipo = t.id_tipo 
                WHERE o.estado != 'eliminado' ORDER BY o.id_oferta DESC";
        $res = $conexion->query($sql);
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        break;

    case 'obtener':
        $id = (int)$_GET['id_oferta'];
        $sql = "SELECT * FROM Oferta WHERE id_oferta = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_assoc()]);
        break;

    case 'guardar':
        guardarOferta($conexion);
        break;

    case 'eliminar':
        $id = (int)$_POST['id_oferta'];
        $sql = "UPDATE Oferta SET estado = 'eliminado' WHERE id_oferta = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Oferta eliminada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
        }
        break;
}

function guardarOferta($conexion) {
    $id = $_POST['id_oferta'] ?? '';
    $nombre = $_POST['nombre_oferta'];
    $f_inicio = $_POST['fecha_inicio'];
    $f_fin = $_POST['fecha_fin'];
    $porciento = !empty($_POST['porciento']) ? $_POST['porciento'] : 0;
    
    // FORZAMOS EL TIPO A 1 (Descuento)
    $id_tipo = 1; 
    
    $estado = $_POST['estado_promo'] ?? 'activo'; // Ajustado al 'name' del radio en el HTML
    $usuario = $_SESSION['id_usuario'] ?? 1;

    try {
        $conexion->begin_transaction();
        
        if ($id == '') {
            $sql = "INSERT INTO Oferta (nombre_oferta, fecha_inicio, fecha_fin, porciento, id_tipo, fecha_creacion, estado, usuario_creacion) 
                    VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssdssi", $nombre, $f_inicio, $f_fin, $porciento, $id_tipo, $estado, $usuario);
        } else {
            $sql = "UPDATE Oferta SET nombre_oferta=?, fecha_inicio=?, fecha_fin=?, porciento=?, id_tipo=?, estado=? WHERE id_oferta=?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssdssi", $nombre, $f_inicio, $f_fin, $porciento, $id_tipo, $estado, $id);
        }
        
        $stmt->execute();
        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Oferta guardada correctamente como Descuento']);
        
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>