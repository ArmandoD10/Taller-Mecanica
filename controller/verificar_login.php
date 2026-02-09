<?php
session_start();
require_once __DIR__ . "/conexion.php";

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

$sql = "SELECT id_usuario, username, password_hash, nivel 
        FROM usuario 
        WHERE username = ? 
        LIMIT 1";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $usuario = $resultado->fetch_assoc();

     // 🔴 COMPARACIÓN NORMAL (TEXTO PLANO)
    if ($password === $usuario['password_hash']) {

        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['user'] = $usuario['username'];
        $_SESSION['nivel'] = $usuario['nivel'];

        header("Location: ../Menu.php");
        exit;
    }
}

// ❌ Login incorrecto
header("Location: ../index.php?error=Usuario o contraseña incorrectos");
exit;

