<?php
// auth.php - Funciones de autenticación y seguridad

/**
 * Verifica si el usuario está autenticado.
 * 
 * Comprueba si la variable de sesión 'user_id' está definida.
 * Si no lo está, significa que el usuario no ha iniciado sesión.
 * En ese caso, lo redirige a la página de login y detiene la ejecución del script.
 * Esta función es esencial para proteger las páginas que requieren que el usuario esté conectado.
 */
function verificarAutenticacion() {
    if (!isset($_SESSION['user_id'])) {
        // Redirige al usuario a la página de inicio de sesión.
        header("Location: /login.php");
        // Detiene la ejecución del script para evitar que se muestre contenido protegido.
        exit;
    }
}

/**
 * Verifica si el usuario tiene un rol específico.
 * 
 * Compara el rol almacenado en la sesión ('user_role') con el rol requerido pasado como argumento.
 * Si los roles no coinciden, redirige al usuario a la página de inicio (index.php).
 * Esto es útil para restringir el acceso a ciertas áreas del sistema, como paneles de administración.
 * 
 * @param string $rolRequerido El rol que se requiere para acceder (ej. 'admin').
 */
function verificarRol($rolRequerido) {
    if ($_SESSION['user_role'] !== $rolRequerido) {
        // Si el rol no es el adecuado, se redirige a una página general.
        header("Location: /index.php");
        exit;
    }
}

/**
 * Obtiene la información del usuario actualmente autenticado.
 * 
 * Devuelve un array con los datos básicos del usuario que están almacenados en la sesión.
 * Esto evita tener que acceder directamente a la variable $_SESSION en diferentes partes del código.
 * 
 * @return array Un array asociativo con 'id', 'nombre' y 'rol' del usuario.
 */
function obtenerUsuarioActual() {
    return [
        'id'     => $_SESSION['user_id'],
        'nombre' => $_SESSION['user_name'],
        'rol'    => $_SESSION['user_role']
    ];
}

/**
 * Comprueba si el usuario actual es un administrador.
 * 
 * Es una función de ayuda (helper) que simplifica la verificación del rol de administrador.
 * Devuelve true si el rol del usuario en la sesión es 'admin', y false en caso contrario.
 * 
 * @return bool True si el usuario es administrador, false si no lo es.
 */
function esAdministrador() {
    return ($_SESSION['user_role'] === 'admin');
}
?>
