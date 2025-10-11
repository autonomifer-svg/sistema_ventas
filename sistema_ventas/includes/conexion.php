<?php
// Configuración de conexión a la nueva base de datos
$host = "localhost";
$user = "root";
$password = "admin043656";
$database = "datos"; // Nueva base de datos

$conexion = new mysqli($host, $user, $password, $database);

// Configurar charset para evitar problemas con caracteres especiales
$conexion->set_charset("utf8mb4");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
?>
