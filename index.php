<?php
session_start();

// Credenciales de ejemplo (cambiar en producción)
$valid_user = 'armando';
$valid_pass = '1234';

$message = '';

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = isset($_POST['user']) ? trim($_POST['user']) : '';
    $pass = isset($_POST['password']) ? $_POST['password'] : '';

    if ($user === $valid_user && $pass === $valid_pass) {
        $_SESSION['user'] = $user;
        header('Location: index.php');
        exit;
    } else {
        $message = 'Usuario o contraseña incorrectos.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Taller</title>
    <link rel="stylesheet" href="Archivo.css">
</head>
<body style="--bg-img: url('img/fondo.webp')">
    <?php if (!empty($_SESSION['user'])): ?>
        <div class="welcome">
            <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['user']); ?> ✅</h2>
            <p>Has iniciado sesión correctamente.</p>
            <a class="btn-logout" href="?logout=1">Cerrar sesión</a>
        </div>
    <?php else: ?>
        <div class="page">
            <form class="login-box" method="post" action="">
                <h2>Iniciar sesión</h2>

                <?php if ($message): ?>
                    <div class="error"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <label for="user">Usuario:</label>
                <input id="user" name="user" type="text" required autocomplete="username">

                <label for="password">Contrasena:</label>
                <input id="password" name="password" type="password" required autocomplete="current-password">

                <button class="btn-login" type="submit">Iniciar sesión</button>
            </form>
        </div>
    <?php endif; ?>

    <footer>
        <p>&copy; Diaz & Pantaleon Soluciones, Derechos de software reservados 2026.</p>
    </footer>
</body>
</html>
