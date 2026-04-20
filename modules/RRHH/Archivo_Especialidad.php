<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? 0;

switch ($action) {
    case 'listar':
        try {
            $sql = "SELECT id_especialidad, nombre, descripcion, estado 
                    FROM Especialidad 
                    WHERE estado != 'eliminado' 
                    ORDER BY nombre ASC";
            $res = $conexion->query($sql);
            echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'guardar':
        try {
            $id = (int)$_POST['id_especialidad'];
            $nombre = $_POST['nombre'];
            $desc = $_POST['descripcion'];

            if ($id > 0) {
                $sql = "UPDATE Especialidad SET nombre=?, descripcion=? WHERE id_especialidad=?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("ssi", $nombre, $desc, $id);
            } else {
                $sql = "INSERT INTO Especialidad (nombre, descripcion, usuario_creacion) VALUES (?, ?, ?)";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("ssi", $nombre, $desc, $id_usuario);
            }

            $stmt->execute();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'cambiar_estado':
        try {
            $id = (int)$_POST['id'];
            $nuevo_estado = $_POST['estado'];
            $sql = "UPDATE Especialidad SET estado = ? WHERE id_especialidad = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("si", $nuevo_estado, $id);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'eliminar':
        try {
            $id = (int)$_POST['id'];
            $conexion->query("UPDATE Especialidad SET estado = 'eliminado' WHERE id_especialidad = $id");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
}
?>