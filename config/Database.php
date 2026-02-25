<?php
class Database {
    public static function conectar() {
        $host = "localhost";
        $usuario = "root";
        $contrasena = "";
        $base_datos = "taller";

        // Creamos la conexión usando MySQLi orientado a objetos
        $conexion = new mysqli($host, $usuario, $contrasena, $base_datos);

        if ($conexion->connect_error) {
            die("Error en la conexión: " . $conexion->connect_error);
        }

        // Establecer charset para evitar errores con tildes (ñ, á, etc.)
        $conexion->set_charset("utf8");

        return $conexion;
    }
}
?>