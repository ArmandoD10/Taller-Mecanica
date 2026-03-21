<?php
session_start();
require_once __DIR__ . "/controller/log_auditoria.php";

// Obtener id antes de destruir sesión
$id_usuario = $_SESSION['id_usuario'] ?? null;

if ($id_usuario) {
    // 🔥 Insertar evento logout
    registrarEvento($id_usuario, "Logout");
}

// Ahora destruir sesión
session_unset();
session_destroy();

// Evitar cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

header("Location: index.php");
exit();