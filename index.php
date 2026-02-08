<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Taller</title>
    <link rel="stylesheet" href="Archivo.css">
</head>
<body style="--bg-img: url('img/fondo.webp')">

    <div class="page">
        <form class="login-box" method="POST" action="controller/verificar_login.php">
            <h2>Iniciar sesión</h2>

            <!-- 🔴 MENSAJE DE ERROR -->
            <?php if (isset($_GET['error'])): ?>
                <div class="error">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <label>Usuario</label>
            <input type="text" name="username" required>

            <label>Contraseña</label>
            <input type="password" name="password_hash" required>

            <button class="btn-login" type="submit">
                Iniciar sesión
            </button>
        </form>
    </div>

    <footer>
        <p>&copy; Diaz & Pantaleon Soluciones, Derechos reservados 2026.</p>
    </footer>

</body>
</html>

