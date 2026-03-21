<?php
session_start();
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/log_auditoria.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password_hash'] ?? '');

if ($username === '' || $password === '') {
    header("Location: ../index.php?error=Campos vacíos");
    exit;
}

$sql = "SELECT id_usuario, username, password_hash, id_nivel
        FROM usuario 
        WHERE username = ? 
        LIMIT 1";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $usuario = $resultado->fetch_assoc();

    if ($password === $usuario['password_hash']) {

        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['user'] = $usuario['username'];
        $_SESSION['id_nivel'] = $usuario['id_nivel'];

        // 🔥 OBTENER MÓDULOS (CORREGIDO A TU MODELO)
        $sqlPermisos = "SELECT m.nombre
                        FROM permiso_nivel pn
                        JOIN modulo m ON pn.id_modulo = m.id_modulo
                        WHERE pn.id_nivel = ?
                        AND pn.estado = 'Activo'";

        $stmtPermisos = $conexion->prepare($sqlPermisos);
        $stmtPermisos->bind_param("i", $usuario['id_nivel']);
        $stmtPermisos->execute();

        $resultPermisos = $stmtPermisos->get_result();

        $modulos = [];

        while($fila = $resultPermisos->fetch_assoc()){
            $modulos[] = $fila['nombre'];
        }

        // 🔥 GUARDAR EN SESIÓN
        $_SESSION['modulos'] = $modulos;

        // 🔴 LOG
        registrarEvento($usuario['id_usuario'], "Login");

        // 🔥 REDIRECCIÓN (AL FINAL)
        header("Location: ../Menu.php");
        exit;
    }
}

// ❌ Login incorrecto
header("Location: ../index.php?error=Usuario o contraseña incorrectos");
exit;