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

// 1. BUSCAR USUARIO
$sql = "SELECT id_usuario, username, password_hash, id_nivel
        FROM usuario 
        WHERE username = ? 
        AND estado = 'activo'
        LIMIT 1";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $usuario = $resultado->fetch_assoc();

    // 2. VALIDAR CONTRASEÑA
    if ($password === $usuario['password_hash']) {

        // --- DATOS BÁSICOS DE SESIÓN ---
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['user'] = $usuario['username'];
        $_SESSION['id_nivel'] = $usuario['id_nivel'];

        // 🔥 3. NUEVO: VINCULAR USUARIO -> EMPLEADO -> SUCURSAL
        // Esta consulta busca la sucursal ACTIVA donde trabaja el usuario logueado
        // 🔥 Consulta corregida con los nombres exactos de tu tabla
// 🔥 Esta es la consulta que debe ir en tu archivo de login
$sqlSucursal = "SELECT s.id_sucursal, s.nombre, eu.id_empleado
                FROM sucursal s
                INNER JOIN empleado_sucursal es ON s.id_sucursal = es.id_sucursal
                INNER JOIN Empleado_Usuario eu ON es.id_empleado = eu.id_empleado
                WHERE eu.id_usuario = ? 
                AND eu.estado = 'activo'  -- <--- ESTO evitará que entre con Armando (ID: 1)
                AND es.estado = 'activo' 
                AND es.fecha_fin IS NULL 
                ORDER BY eu.sec_empleado_usuario DESC 
                LIMIT 1";

        $stmtSuc = $conexion->prepare($sqlSucursal);
        $stmtSuc->bind_param("i", $usuario['id_usuario']);
        $stmtSuc->execute();
        $resSuc = $stmtSuc->get_result()->fetch_assoc();

        if ($resSuc) {
            // Guardamos los datos de la sucursal en la sesión
            $_SESSION['id_sucursal'] = $resSuc['id_sucursal'];
            $_SESSION['nombre_sucursal'] = $resSuc['nombre'];
        } else {
            // Si el usuario no tiene sucursal asignada
            $_SESSION['id_sucursal'] = 0;
            $_SESSION['nombre_sucursal'] = "Sin Sucursal";
        }

        // 🔥 4. OBTENER MÓDULOS DE PERMISO
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
        $_SESSION['modulos'] = $modulos;

        // 🔴 REGISTRAR AUDITORÍA
        registrarEvento($usuario['id_usuario'], "Login Exitoso en sucursal: " . $_SESSION['nombre_sucursal']);

        // 🔥 REDIRECCIÓN AL MENÚ PRINCIPAL
        header("Location: ../Menu.php");
        exit;
    }
}

// ❌ LOGIN INCORRECTO
header("Location: ../index.php?error=Usuario o contraseña incorrectos");
exit;