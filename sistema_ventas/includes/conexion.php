<?php
$host = "localhost"; // Normalmente es localhost
$user = "root"; // Usuario por defecto en XAMPP
$password = "admin043656"; // Contraseña por defecto (vacía en XAMPP)
$database = "sistema_ventas";

$conexion = new mysqli($host, $user, $password, $database);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
?>