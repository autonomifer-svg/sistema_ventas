<?php
// auth.php - Funciones de autenticación y seguridad

function verificarAutenticacion() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit;
    }
}

function verificarRol($rolRequerido) {
    if ($_SESSION['user_role'] !== $rolRequerido) {
        header("Location: /index.php");
        exit;
    }
}

function obtenerUsuarioActual() {
    return [
        'id' => $_SESSION['user_id'],
        'nombre' => $_SESSION['user_name'],
        'rol' => $_SESSION['user_role']
    ];
}

function esAdministrador() {
    return ($_SESSION['user_role'] === 'admin');
}
?>