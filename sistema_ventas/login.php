<?php
// Activar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Registrar errores en log
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

require_once('includes/config.php');

// Si el usuario ya está logueado, redirigir
if (isset($_SESSION['user_id'])) {
    error_log("Usuario ya autenticado (ID: " . $_SESSION['user_id'] . "). Redirigiendo a index.php");
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    error_log("=== INICIO DE SESIÓN ===");
    error_log("Usuario: $usuario");
    error_log("Password recibido: " . (strlen($password) > 0 ? "Sí (" . strlen($password) . " caracteres)" : "No"));
    
    if (empty($usuario) || empty($password)) {
        $error = "Por favor completa todos los campos";
        error_log("Error: Campos vacíos");
    } else {
        // Conectar a la base de datos
        try {
            $conexion = conectarDB();
            
            $sql = "SELECT id, nombre, usuario, password, rol FROM usuarios WHERE usuario = ?";
            $stmt = $conexion->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Error en la preparación de la consulta: " . $conexion->error);
            }
            
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            
            error_log("Consulta ejecutada. Resultados encontrados: " . $result->num_rows);
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                error_log("Usuario encontrado en BD:");
                error_log("- ID: " . $user['id']);
                error_log("- Nombre: " . $user['nombre']);
                error_log("- Usuario: " . $user['usuario']);
                error_log("- Rol: " . $user['rol']);
                error_log("- Hash almacenado: " . substr($user['password'], 0, 20) . "...");
                
                // Verificar contraseña
                error_log("Verificando contraseña...");
                
                if (password_verify($password, $user['password'])) {
                    error_log("✅ Contraseña VÁLIDA para usuario: $usuario");
                    
                    // Regenerar ID de sesión por seguridad
                    session_regenerate_id(true);
                    
                    // Iniciar sesión
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nombre'];
                    $_SESSION['user_role'] = $user['rol'];
                    $_SESSION['login_time'] = time();
                    
                    error_log("✅ Sesión iniciada exitosamente:");
                    error_log("Variables de sesión: " . print_r($_SESSION, true));
                    
                    // Registrar acceso
                    try {
                        $sql_log = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?";
                        $stmt_log = $conexion->prepare($sql_log);
                        if ($stmt_log) {
                            $stmt_log->bind_param("i", $user['id']);
                            $stmt_log->execute();
                            $stmt_log->close();
                            error_log("✅ Último acceso actualizado");
                        }
                    } catch (Exception $e) {
                        error_log("⚠️ Error al actualizar último acceso: " . $e->getMessage());
                    }
                    
                    $stmt->close();
                    $conexion->close();
                    
                    // Redirigir al dashboard
                    error_log("🚀 Redirigiendo a index.php");
                    redirect('index.php');
                    
                } else {
                    error_log("❌ Contraseña INCORRECTA para usuario: $usuario");
                    $error = "Credenciales incorrectas";
                }
            } else {
                error_log("❌ Usuario NO ENCONTRADO: $usuario");
                $error = "Usuario no encontrado";
            }
            
            if (isset($stmt)) $stmt->close();
            if (isset($conexion)) $conexion->close();
            
        } catch (Exception $e) {
            error_log("💥 Excepción en login: " . $e->getMessage());
            $error = "Error del sistema. Por favor intente más tarde.";
        }
    }
} else {
    error_log("Acceso a login.php - Método GET");
}

include('includes/header.php');
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="text-center">Acceso al Sistema</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Panel de información para testing -->
                    <div class="alert alert-info">
                        <h6>Para testing:</h6>
                        <small>
                            <strong>Base URL:</strong> <?= htmlspecialchars($base_url) ?><br>
                            <strong>Sesión ID:</strong> <?= session_id() ?><br>
                            <strong>Estado de sesión:</strong> <?= session_status() === PHP_SESSION_ACTIVE ? 'Activa' : 'Inactiva' ?>
                        </small>
                    </div>
                    
                    <form method="POST" id="loginForm">
                        <div class="mb-3">
                            <label class="form-label">Usuario</label>
                            <input type="text" name="usuario" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" 
                                   required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right"></i> Ingresar
                        </button>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <small>Sistema de Ventas - Artículos de Limpieza</small>
                    <div class="mt-2">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#helpModal">
                            ¿Problemas para acceder?
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de ayuda -->
<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ayuda de acceso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Si tienes problemas para acceder:</strong></p>
                <ol>
                    <li>Verifica que tu usuario y contraseña sean correctos</li>
                    <li>Asegúrate de no tener espacios al principio o final</li>
                    <li>Verifica que las mayúsculas y minúsculas sean correctas</li>
                    <li>Contacta al administrador si el problema persiste</li>
                </ol>
                <hr>
                <p><strong>Contacto:</strong> admin@limpiezatotal.com</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Mostrar/ocultar contraseña
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
});

// Validación de formulario
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const usuario = document.querySelector('input[name="usuario"]').value.trim();
    const password = document.getElementById('password').value.trim();
    
    if (!usuario || !password) {
        e.preventDefault();
        alert('Por favor completa todos los campos');
    }
});
</script>

<?php include('includes/footer.php'); ?>