<?php
// CONFIGURACIÓN INICIAL Y DEPURACIÓN
// -------------------------------------

// Muestra todos los errores de PHP, útil para la fase de desarrollo.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Habilita el registro de errores en un archivo para no exponerlos en producción.
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log'); // Guarda los errores en 'error.log' en el mismo directorio.

// Incluye el archivo de configuración principal, que probablemente inicia la sesión y define funciones útiles.
require_once('includes/config.php');

// REDIRECCIÓN SI EL USUARIO YA ESTÁ AUTENTICADO
// ---------------------------------------------

// Comprueba si ya existe una sesión de usuario activa.
if (isset($_SESSION['user_id'])) {
    // Si es así, registra el evento y redirige al usuario a la página principal para evitar que vuelva a iniciar sesión.
    error_log("Usuario ya autenticado (ID: " . $_SESSION['user_id'] . "). Redirigiendo a index.php");
    redirect('index.php');
}

// INICIALIZACIÓN DE VARIABLES
// ---------------------------

$error = '';     // Variable para almacenar mensajes de error que se mostrarán al usuario.
$success = ''; // Variable para almacenar mensajes de éxito.

// PROCESAMIENTO DEL FORMULARIO DE LOGIN (CUANDO SE ENVÍA POR POST)
// ----------------------------------------------------------------

// Verifica si la solicitud al servidor se hizo usando el método POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoge y limpia los datos del formulario. 'trim' elimina espacios en blanco al inicio y al final.
    // El operador '??' de fusión de null asigna un string vacío si el campo no existe, para evitar errores.
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Registro en log para depuración.
    error_log("=== INICIO DE SESIÓN ===");
    error_log("Intento de login para el usuario: $usuario");

    // VALIDACIÓN DE CAMPOS
    // ---------------------
    
    // Comprueba si el usuario o la contraseña están vacíos.
    if (empty($usuario) || empty($password)) {
        $error = "Por favor completa todos los campos";
        error_log("Error de validación: El usuario o la contraseña están vacíos.");
    } else {
        // PROCESO DE AUTENTICACIÓN CON BASE DE DATOS
        // -----------------------------------------
        try {
            // Establece la conexión con la base de datos.
            $conexion = conectarDB();
            
            // Si la conexión falla, lanza una excepción.
            if (!$conexion) {
                throw new Exception("No se pudo conectar a la base de datos");
            }
            
            // Prepara la consulta SQL para buscar al usuario.
            // Se busca un usuario activo (Inactivo = 0) que coincida con el nombre de usuario proporcionado.
            // Usar consultas preparadas (con '?') es una medida de seguridad crucial para prevenir inyecciones SQL.
            $sql = "SELECT IdUsuario, NombreUsuario, Clave, Nivel, NombreInterno, Inactivo 
                    FROM usuario 
                    WHERE NombreUsuario = ? AND Inactivo = 0";
            
            $stmt = $conexion->prepare($sql);
            
            // Si la preparación de la consulta falla, lanza una excepción.
            if (!$stmt) {
                throw new Exception("Error en la preparación de la consulta: " . $conexion->error);
            }
            
            // Vincula el valor de la variable $usuario al primer '?' de la consulta preparada.
            // "s" indica que el tipo de dato es un string (cadena de texto).
            $stmt->bind_param("s", $usuario);
            
            // Ejecuta la consulta.
            $stmt->execute();
            
            // Obtiene el conjunto de resultados de la consulta.
            $result = $stmt->get_result();
            
            error_log("Consulta de usuario ejecutada. Filas encontradas: " . $result->num_rows);
            
            // VERIFICACIÓN DEL USUARIO Y CONTRASEÑA
            // -------------------------------------

            // Si se encontró exactamente un usuario, procede a verificar la contraseña.
            if ($result->num_rows === 1) {
                // Obtiene los datos del usuario como un array asociativo.
                $user = $result->fetch_assoc();
                
                // VERIFICACIÓN DE CONTRASEÑA (SOPORTA MÚLTIPLES MÉTODOS)
                // ---------------------------------------------------------
                $password_valida = false;
                
                // 1. Intenta verificar con 'password_verify'. Este es el método más seguro y moderno (para contraseñas hasheadas con password_hash).
                if (password_verify($password, $user['Clave'])) {
                    $password_valida = true;
                    error_log("Contraseña verificada con éxito usando password_verify (hash seguro).");
                } 
                // 2. Si falla, intenta con MD5. Esto da soporte a sistemas antiguos (legacy) que guardaban contraseñas con MD5 (inseguro).
                elseif (md5($password) === $user['Clave']) {
                    $password_valida = true;
                    error_log("Contraseña verificada con MD5 (método legacy, inseguro).");
                }
                // 3. Como último recurso, compara en texto plano. Extremadamente inseguro, solo para compatibilidad con sistemas muy antiguos.
                elseif ($password === $user['Clave']) {
                    $password_valida = true;
                    error_log("ADVERTENCIA: Contraseña verificada en texto plano (¡MUY INSEGURO!).");
                }
                
                // SI LA CONTRASEÑA ES VÁLIDA, INICIA LA SESIÓN
                // ---------------------------------------------
                if ($password_valida) {
                    // Regenera el ID de la sesión para prevenir ataques de fijación de sesión.
                    session_regenerate_id(true);
                    
                    // Almacena los datos del usuario en la variable de sesión '$_SESSION'.
                    // Esto permite que el usuario permanezca autenticado en otras páginas.
                    $_SESSION['user_id'] = $user['IdUsuario'];
                    $_SESSION['user_name'] = $user['NombreInterno'];
                    $_SESSION['user_username'] = $user['NombreUsuario'];
                    $_SESSION['user_role'] = ($user['Nivel'] == 1) ? 'admin' : 'user'; // Asigna rol 'admin' o 'user' según el nivel.
                    $_SESSION['user_level'] = $user['Nivel'];
                    $_SESSION['login_time'] = time(); // Guarda la marca de tiempo del inicio de sesión.
                    
                    error_log("Sesión iniciada para el usuario ID: " . $user['IdUsuario']);
                    
                    // Cierra la consulta y la conexión a la base de datos.
                    $stmt->close();
                    $conexion->close();
                    
                    // Redirige al usuario a la página principal del sistema.
                    error_log("Redirigiendo a index.php tras login exitoso.");
                    redirect('index.php');
                    
                } else {
                    // Si la contraseña no es correcta, guarda un mensaje de error.
                    error_log("Contraseña INCORRECTA para el usuario: $usuario");
                    $error = "Credenciales incorrectas";
                }
            } else {
                // Si no se encontró ningún usuario o está inactivo, guarda un mensaje de error.
                error_log("Usuario no encontrado o inactivo: $usuario");
                $error = "Usuario no encontrado o inactivo";
            }
            
            // Cierra los recursos de la base de datos si todavía están abiertos.
            if (isset($stmt)) $stmt->close();
            if (isset($conexion)) $conexion->close();
            
        } catch (Exception $e) {
            // Captura cualquier excepción que haya ocurrido durante el proceso y la registra.
            error_log("Excepción capturada en login.php: " . $e->getMessage());
            $error = "Error del sistema. Por favor intente más tarde.";
        }
    }
} else {
    // Si la página se carga con un método que no es POST (ej. GET), simplemente se registra el acceso.
    error_log("Acceso a login.php con método GET.");
}

// INCLUSIÓN DE LA CABECERA HTML
// -----------------------------
// Incluye el archivo que contiene la parte superior de la página web (<html>, <head>, etc.).
include('includes/header.php');
?>

<style>
.login-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.login-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: none;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    max-width: 450px;
    width: 100%;
    animation: slideInUp 0.6s ease-out;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.login-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem 1.5rem;
    text-align: center;
    border: none;
}

.login-header h3 {
    margin: 0;
    font-weight: 600;
    font-size: 1.5rem;
    color: white;
}

.brand-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.8rem;
    color: white;
}

.login-body {
    padding: 2rem 1.5rem;
}

.form-floating {
    margin-bottom: 1.5rem;
}

.form-control {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: rgba(248, 249, 250, 0.8);
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.15);
    background: white;
}

.btn-login {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 12px;
    padding: 0.875rem;
    font-weight: 600;
    font-size: 1rem;
    width: 100%;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.btn-login:active {
    transform: translateY(0);
}

.password-toggle {
    background: transparent;
    border: none;
    color: #6c757d;
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    z-index: 5;
    font-size: 1.1rem;
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: #667eea;
}

.alert-custom {
    border: none;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.alert-danger-custom {
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    color: white;
}

.alert-success-custom {
    background: linear-gradient(135deg, #51cf66, #40c057);
    color: white;
}

.info-panel {
    background: linear-gradient(135deg, #74b9ff, #0984e3);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    font-size: 0.85rem;
}

.login-footer {
    background: rgba(248, 249, 250, 0.8);
    padding: 1.5rem;
    text-align: center;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.help-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.help-link:hover {
    color: #764ba2;
    text-decoration: underline;
}

.floating-shapes {
    position: absolute;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: -1;
}

.shape {
    position: absolute;
    opacity: 0.1;
    animation: float 6s ease-in-out infinite;
}

.shape-1 {
    top: 10%;
    left: 20%;
    animation-delay: 0s;
}

.shape-2 {
    top: 70%;
    right: 20%;
    animation-delay: 2s;
}

.shape-3 {
    bottom: 20%;
    left: 10%;
    animation-delay: 4s;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    50% {
        transform: translateY(-20px) rotate(10deg);
    }
}

/* Responsive */
@media (max-width: 576px) {
    .login-container {
        padding: 10px;
    }
    
    .login-header,
    .login-body {
        padding: 1.5rem 1rem;
    }
}
</style>

<div class="login-container">
    <!-- Formas flotantes decorativas -->
    <div class="floating-shapes">
        <div class="shape shape-1">
            <i class="bi bi-droplet-fill" style="font-size: 3rem; color: white;"></i>
        </div>
        <div class="shape shape-2">
            <i class="bi bi-box-fill" style="font-size: 2.5rem; color: white;"></i>
        </div>
        <div class="shape shape-3">
            <i class="bi bi-star-fill" style="font-size: 2rem; color: white;"></i>
        </div>
    </div>

    <div class="login-card">
        <div class="login-header">
            <div class="brand-icon">
                <i class="bi bi-shop"></i>
            </div>
            <h3>Limpieza Total</h3>
            <p style="margin: 0.5rem 0 0; opacity: 0.9; font-size: 0.9rem;">Sistema de Gestión</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger-custom alert-custom">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success-custom alert-custom">
                    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <div class="form-floating">
                    <input type="text" name="usuario" id="usuario" class="form-control" 
                           placeholder="Usuario" value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" 
                           required autofocus>
                    <label for="usuario">
                        <i class="bi bi-person me-2"></i>Usuario
                    </label>
                </div>
                
                <div class="form-floating position-relative">
                    <input type="password" name="password" id="password" class="form-control" 
                           placeholder="Contraseña" required>
                    <label for="password">
                        <i class="bi bi-lock me-2"></i>Contraseña
                    </label>
                    <button class="password-toggle" type="button" id="togglePassword">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Acceder al Sistema
                </button>
            </form>
        </div>
        
        <div class="login-footer">
            <small style="color: #6c757d;">Sistema de Ventas - Artículos de Limpieza</small>
            <div class="mt-2">
                <a href="#" class="help-link" data-bs-toggle="modal" data-bs-target="#helpModal">
                    <i class="bi bi-question-circle me-1"></i>¿Problemas para acceder?
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal de ayuda -->
<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px 15px 0 0;">
                <h5 class="modal-title">
                    <i class="bi bi-life-preserver me-2"></i>Ayuda de Acceso
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <div class="alert" style="background: rgba(102, 126, 234, 0.1); border: 1px solid rgba(102, 126, 234, 0.2); border-radius: 10px;">
                    <h6 style="color: #667eea; margin-bottom: 1rem;">
                        <i class="bi bi-lightbulb me-2"></i>Si tienes problemas para acceder:
                    </h6>
                    <ol style="margin-bottom: 0;">
                        <li>Verifica que tu usuario y contraseña sean correctos</li>
                        <li>Asegúrate de no tener espacios al principio o final</li>
                        <li>Verifica que las mayúsculas y minúsculas sean correctas</li>
                        <li>La contraseña puede ser: hash, MD5, o texto plano</li>
                        <li>Contacta al administrador si el problema persiste</li>
                    </ol>
                </div>
                <hr style="margin: 1.5rem 0;">
                <div class="text-center">
                    <i class="bi bi-envelope" style="font-size: 2rem; color: #667eea; margin-bottom: 0.5rem;"></i>
                    <p><strong>Contacto de Soporte:</strong></p>
                    <p style="color: #667eea; font-weight: 500;">admin@limpiezatotal.com</p>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(0, 0, 0, 0.05);">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">
                    <i class="bi bi-x-circle me-2"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Mostrar/ocultar contraseña
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    
    passwordInput.setAttribute('type', type);
    
    icon.style.transform = 'scale(0.8)';
    setTimeout(() => {
        icon.className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        icon.style.transform = 'scale(1)';
    }, 150);
});

// Validación de formulario
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const usuario = document.querySelector('input[name="usuario"]').value.trim();
    const password = document.getElementById('password').value.trim();
    const submitBtn = this.querySelector('button[type="submit"]');
    
    if (!usuario || !password) {
        e.preventDefault();
        
        submitBtn.style.animation = 'shake 0.5s ease-in-out';
        setTimeout(() => {
            submitBtn.style.animation = '';
        }, 500);
        
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger-custom alert-custom';
        alertDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Por favor completa todos los campos';
        
        const form = document.getElementById('loginForm');
        form.parentNode.insertBefore(alertDiv, form);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
    } else {
        submitBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>Verificando...';
        submitBtn.disabled = true;
    }
});

// Animación de shake
const style = document.createElement('style');
style.textContent = `
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}
`;
document.head.appendChild(style);

// Efectos de focus
document.querySelectorAll('.form-control').forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'translateY(-2px)';
        this.parentElement.style.transition = 'transform 0.3s ease';
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'translateY(0)';
    });
});
</script>

<?php include('includes/footer.php'); ?>
