<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? 0;

if ($id_usuario == 0) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

switch ($action) {
    case 'cargar_datos':
        $sql = "SELECT id_usuario, username, correo_org FROM Usuario WHERE id_usuario = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $res]);
        break;

    case 'actualizar_password':
        $pass_actual = $_POST['pass_actual'] ?? '';
        $pass_nueva = $_POST['pass_nueva'] ?? '';
        
        // 1. Validar contraseña actual y restricción de tiempo
        $sql = "SELECT password_hash, fecha_ultimo_cambio_pass FROM Usuario WHERE id_usuario = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user['password_hash'] !== $pass_actual) {
            echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta.']);
            exit;
        }

        // 2. Validar periodo de 2 días
        if ($user['fecha_ultimo_cambio_pass'] != null) {
            $fecha_ultimo = new DateTime($user['fecha_ultimo_cambio_pass']);
            $ahora = new DateTime();
            $diferencia = $ahora->diff($fecha_ultimo);
            
            if ($diferencia->days < 2) {
                echo json_encode(['success' => false, 'message' => 'Por seguridad, debe esperar 2 días entre cambios de contraseña.']);
                exit;
            }
        }

        // 3. Actualizar
        $sqlUpd = "UPDATE Usuario SET password_hash = ?, fecha_ultimo_cambio_pass = NOW() WHERE id_usuario = ?";
        $stmtUpd = $conexion->prepare($sqlUpd);
        $stmtUpd->bind_param("si", $pass_nueva, $id_usuario);
        
        if ($stmtUpd->execute()) {
            echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
        }
        break;
}