<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

// Reporte estricto para atrapar errores
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$action = $_GET['action'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? 0;

switch ($action) {
    case 'listar':
        try {
            $sql = "SELECT id_politica, nombre, tiempo_cobertura, unidad_tiempo, 
                           IFNULL(kilometraje_cobertura, 'N/A') as km, estado 
                    FROM politica_garantia 
                    WHERE estado != 'eliminado' 
                    ORDER BY id_politica DESC";
            $res = $conexion->query($sql);
            echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error SQL: ' . $e->getMessage()]);
        }
        break;

    case 'guardar':
        try {
            $id = (int)$_POST['id_politica'];
            $nombre = $_POST['nombre'];
            $desc = $_POST['descripcion'];
            $tiempo = (int)$_POST['tiempo_cobertura'];
            $unidad = $_POST['unidad_tiempo'];
            $km = !empty($_POST['kilometraje_cobertura']) ? (int)$_POST['kilometraje_cobertura'] : null;

            if ($id > 0) {
                $sql = "UPDATE politica_garantia SET nombre=?, descripcion=?, tiempo_cobertura=?, unidad_tiempo=?, kilometraje_cobertura=? WHERE id_politica=?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("ssisii", $nombre, $desc, $tiempo, $unidad, $km, $id);
            } else {
                $sql = "INSERT INTO politica_garantia (nombre, descripcion, tiempo_cobertura, unidad_tiempo, kilometraje_cobertura, usuario_creacion) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("ssisii", $nombre, $desc, $tiempo, $unidad, $km, $id_usuario);
            }

            $stmt->execute();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // NUEVO CASO: Activar / Desactivar
    case 'cambiar_estado':
        try {
            $id = (int)$_POST['id'];
            $nuevo_estado = $_POST['estado']; // Recibe 'activo' o 'inactivo'
            
            if (in_array($nuevo_estado, ['activo', 'inactivo'])) {
                $sql = "UPDATE politica_garantia SET estado = ? WHERE id_politica = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("si", $nuevo_estado, $id);
                $stmt->execute();
                
                echo json_encode(['success' => true]);
            } else {
                throw new Exception("Estado no válido.");
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'eliminar':
        try {
            $id = (int)$_POST['id'];
            $conexion->query("UPDATE politica_garantia SET estado = 'eliminado' WHERE id_politica = $id");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
}
?>