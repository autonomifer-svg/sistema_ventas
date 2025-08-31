<?php
// Configuración global de rutas y seguridad
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

// Lógica mejorada para el manejo de puertos
$port = '';
if (isset($_SERVER['SERVER_PORT'])) {
    $server_port = $_SERVER['SERVER_PORT'];
    // Solo agregar puerto si no es el estándar (80 para HTTP, 443 para HTTPS)
    if (($protocol === 'http://' && $server_port != '8080') || 
        ($protocol === 'https://' && $server_port != '443')) {
        $port = ':' . $server_port;
    }
}

$project_path = '/sistema_ventas/'; // Ajustar según tu instalación

$base_url = $protocol . $host . $port . $project_path;

// Configuración de sesión segura - DEBE ESTAR ANTES DE session_start()
#session_set_cookie_params([
#    'lifetime' => 86400, // 1 día
#    'path' => $project_path,
#    'domain' => '', // Dejar vacío para localhost
#    'secure' => ($protocol === 'https://'), // Seguro si es HTTPS
#    'httponly' => true,""    
#    'samesite' => 'Lax'
#]);

// Iniciar sesión solo si no está activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Función para redirigir con manejo de buffer y prevención de bloqueos
function redirect($path) {
    global $base_url;
    
    // Construir URL completa
    $location = $base_url . ltrim($path, '/');
    
    // Limpiar cualquier salida previa
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Encabezado de redirección
    header("Location: " . $location, true, 302);
    exit();
}

// Función para verificar autenticación
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        // Prevenir bucles de redirección
        $current_page = basename($_SERVER['SCRIPT_NAME']);
        if ($current_page !== 'login.php') {
            // Limpiar buffer antes de redirigir
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header("Location: login.php");
            exit();
        }
    }
}

// Función para verificar rol de administrador
function requireAdmin() {
    requireAuth();
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        // Limpiar buffer antes de redirigir
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header("Location: index.php");
        exit();
    }
}

// Conectar a la base de datos
function conectarDB() {
    $host = "localhost";
    $user = "root";
    $password = "admin043656";
    $database = "sistema_ventas";

    $conexion = new mysqli($host, $user, $password, $database);
    $conexion->set_charset("utf8mb4");

    if ($conexion->connect_error) {
        // Manejo de error limpio (sin HTML)
        error_log("Error de conexión a la base de datos: " . $conexion->connect_error);
        
        // Mostrar mensaje después de iniciar HTML
        $_SESSION['db_error'] = $conexion->connect_error;
        return false;
    }
    
    return $conexion;
}

// Función para manejar errores de base de datos
function handleDbError($error) {
    // Limpiar buffer si es necesario
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    die("<div class='alert alert-danger text-center'>
            <h3>Error del Sistema</h3>
            <p>No se pudo conectar a la base de datos. Por favor intente más tarde.</p>
            <p>Detalles técnicos: " . htmlspecialchars($error) . "</p>
         </div>");
}

// Prevención de ataques de redirección
function safeRedirect($path) {
    global $base_url;
    
    // Validar que la URL sea local
    $location = $base_url . ltrim($path, '/');
    if (strpos($location, $base_url) === 0) {
        redirect($path);
    } else {
        error_log("Intento de redirección a dominio externo: $location");
        redirect('index.php');
    }
}
?>