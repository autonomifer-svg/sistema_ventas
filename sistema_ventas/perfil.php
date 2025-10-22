<?php
// INCLUSIÓN DE ARCHIVOS Y VERIFICACIÓN DE SEGURIDAD
// --------------------------------------------------

// Incluye el archivo de configuración global, que probablemente inicia la sesión y define funciones y constantes.
require_once('includes/config.php');
// Llama a la función que verifica si el usuario ha iniciado sesión. Si no, lo redirige al login.
requireAuth();

// Incluye el encabezado de la página HTML, que contiene el <head>, la barra de navegación, etc.
// Se incluye después de la lógica de autenticación para asegurar que solo los usuarios logueados vean la página.
include('includes/header.php');
?>

<style>
.profile-container {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: calc(100vh - 76px);
    padding: 2rem 0;
}

.profile-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: none;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: all 0.3s ease;
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.profile-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
}

.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.profile-header::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    animation: shine 3s infinite;
}

@keyframes shine {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
}

.profile-header h5 {
    margin: 0;
    font-weight: 600;
    position: relative;
    z-index: 2;
}

.avatar-container {
    position: relative;
    display: inline-block;
    margin: 1.5rem 0;
}

.avatar-circle {
    width: 120px;
    height: 120px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: white;
    border: 4px solid rgba(255, 255, 255, 0.3);
    transition: all 0.3s ease;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.avatar-circle:hover {
    border-color: rgba(255, 255, 255, 0.6);
    transform: scale(1.1);
}

.status-badge {
    position: absolute;
    bottom: 10px;
    right: 10px;
    width: 24px;
    height: 24px;
    background: #28a745;
    border-radius: 50%;
    border: 3px solid white;
    animation: statusPulse 2s infinite;
}

@keyframes statusPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.user-info {
    text-align: center;
    position: relative;
    z-index: 2;
}

.user-name {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0.5rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.user-role {
    font-size: 1rem;
    opacity: 0.9;
    font-weight: 500;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.25rem 1rem;
    border-radius: 15px;
    display: inline-block;
    margin: 0.5rem 0;
}

.user-id {
    font-size: 0.9rem;
    opacity: 0.8;
    font-family: 'Courier New', monospace;
}

.info-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: none;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
    animation: fadeInUp 0.6s ease-out;
}

.info-card:nth-child(2) {
    animation-delay: 0.2s;
}

.info-card:nth-child(3) {
    animation-delay: 0.4s;
}

.info-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
}

.card-header-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.25rem 1.5rem;
    border: none;
    position: relative;
    overflow: hidden;
}

.card-header-custom::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.card-header-custom:hover::before {
    left: 100%;
}

.card-header-custom h5 {
    margin: 0;
    font-weight: 600;
    display: flex;
    align-items: center;
    position: relative;
    z-index: 2;
}

.card-header-custom i {
    margin-right: 0.5rem;
    font-size: 1.2rem;
}

.form-control-custom {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: rgba(248, 249, 250, 0.8);
}

.form-control-custom:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.15);
    background: white;
    transform: translateY(-1px);
}

.form-control-custom:read-only {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-color: #dee2e6;
    cursor: not-allowed;
}

.btn-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    color: white;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-custom::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.2);
    transition: left 0.3s;
}

.btn-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    color: white;
}

.btn-custom:hover::before {
    left: 100%;
}

.btn-custom:active {
    transform: translateY(0);
}

.form-label-custom {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
}

.form-label-custom i {
    margin-right: 0.5rem;
    color: #667eea;
}

.password-strength {
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 0.5rem;
}

.strength-bar {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-weak { background: #dc3545; width: 25%; }
.strength-fair { background: #ffc107; width: 50%; }
.strength-good { background: #28a745; width: 75%; }
.strength-strong { background: #20c997; width: 100%; }

.floating-particles {
    position: absolute;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: -1;
}

.particle {
    position: absolute;
    width: 4px;
    height: 4px;
    background: rgba(102, 126, 234, 0.3);
    border-radius: 50%;
    animation: float 8s linear infinite;
}

.particle:nth-child(1) { left: 10%; animation-delay: 0s; }
.particle:nth-child(2) { left: 20%; animation-delay: 1s; }
.particle:nth-child(3) { left: 30%; animation-delay: 2s; }
.particle:nth-child(4) { left: 40%; animation-delay: 3s; }
.particle:nth-child(5) { left: 50%; animation-delay: 4s; }
.particle:nth-child(6) { left: 60%; animation-delay: 5s; }
.particle:nth-child(7) { left: 70%; animation-delay: 6s; }
.particle:nth-child(8) { left: 80%; animation-delay: 7s; }

@keyframes float {
    0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { transform: translateY(-10vh) rotate(360deg); opacity: 0; }
}

/* Responsive */
@media (max-width: 768px) {
    .profile-container {
        padding: 1rem 0;
    }
    
    .avatar-circle {
        width: 100px;
        height: 100px;
        font-size: 2.5rem;
    }
    
    .user-name {
        font-size: 1.25rem;
    }
}
</style>

<div class="profile-container">
    <!-- Partículas flotantes -->
    <div class="floating-particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="profile-card">
                    <div class="profile-header">
                        <h5>
                            <i class="bi bi-person-badge me-2"></i>
                            Perfil de Usuario
                        </h5>
                        <div class="user-info">
                            <div class="avatar-container">
                                <div class="avatar-circle">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div class="status-badge" title="Usuario activo"></div>
                            </div>
                            <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                            <div class="user-role"><?= ucfirst($_SESSION['user_role']) ?></div>
                            <div class="user-id">
                                <i class="bi bi-hash"></i>
                                ID: <?= $_SESSION['user_id'] ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas adicionales -->
                <div class="info-card mt-4">
                    <div class="card-header-custom">
                        <h5>
                            <i class="bi bi-graph-up"></i>
                            Estadísticas
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row text-center">
                            <div class="col-4">
                                <div style="color: #667eea; font-size: 1.5rem; font-weight: bold;">15</div>
                                <small class="text-muted">Ventas</small>
                            </div>
                            <div class="col-4">
                                <div style="color: #28a745; font-size: 1.5rem; font-weight: bold;">98%</div>
                                <small class="text-muted">Eficiencia</small>
                            </div>
                            <div class="col-4">
                                <div style="color: #ffc107; font-size: 1.5rem; font-weight: bold;">4.8</div>
                                <small class="text-muted">Rating</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="info-card">
                    <div class="card-header-custom">
                        <h5>
                            <i class="bi bi-person-lines-fill"></i>
                            Información Personal
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label-custom">
                                        <i class="bi bi-person"></i>
                                        Nombre Completo
                                    </label>
                                    <input type="text" class="form-control form-control-custom" 
                                           value="<?= htmlspecialchars($_SESSION['user_name']) ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label-custom">
                                        <i class="bi bi-shield-check"></i>
                                        Rol del Sistema
                                    </label>
                                    <input type="text" class="form-control form-control-custom" 
                                           value="<?= ucfirst($_SESSION['user_role']) ?>" readonly>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label-custom">
                                        <i class="bi bi-calendar-check"></i>
                                        Último Acceso
                                    </label>
                                    <input type="text" class="form-control form-control-custom" 
                                           value="<?= date('d/m/Y H:i', $_SESSION['login_time'] ?? time()) ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label-custom">
                                        <i class="bi bi-clock"></i>
                                        Estado de Sesión
                                    </label>
                                    <input type="text" class="form-control form-control-custom" 
                                           value="✅ Activa" readonly style="color: #28a745; font-weight: 600;">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="card-header-custom">
                        <h5>
                            <i class="bi bi-key"></i>
                            Cambiar Contraseña
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form id="passwordForm">
                            <div class="mb-3">
                                <label class="form-label-custom">
                                    <i class="bi bi-lock"></i>
                                    Contraseña Actual
                                </label>
                                <div class="position-relative">
                                    <input type="password" id="currentPassword" class="form-control form-control-custom" required>
                                    <button class="password-toggle" type="button" onclick="togglePassword('currentPassword')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label-custom">
                                    <i class="bi bi-shield-lock"></i>
                                    Nueva Contraseña
                                </label>
                                <div class="position-relative">
                                    <input type="password" id="newPassword" class="form-control form-control-custom" required>
                                    <button class="password-toggle" type="button" onclick="togglePassword('newPassword')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength">
                                    <div class="strength-bar" id="strengthBar"></div>
                                </div>
                                <small id="strengthText" class="text-muted"></small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label-custom">
                                    <i class="bi bi-check-circle"></i>
                                    Confirmar Nueva Contraseña
                                </label>
                                <div class="position-relative">
                                    <input type="password" id="confirmPassword" class="form-control form-control-custom" required>
                                    <button class="password-toggle" type="button" onclick="togglePassword('confirmPassword')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <small id="matchText" class="text-muted"></small>
                            </div>
                            <div class="d-flex gap-3">
                                <button type="submit" class="btn btn-custom">
                                    <i class="bi bi-check-lg me-2"></i>
                                    Actualizar Contraseña
                                </button>
                                <button type="reset" class="btn btn-outline-secondary" style="border-radius: 12px;">
                                    <i class="bi bi-arrow-clockwise me-2"></i>
                                    Limpiar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Nueva sección de preferencias -->
                <div class="info-card">
                    <div class="card-header-custom">
                        <h5>
                            <i class="bi bi-gear"></i>
                            Preferencias del Sistema
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label-custom">
                                        <i class="bi bi-palette"></i>
                                        Tema
                                    </label>
                                    <select class="form-control form-control-custom">
                                        <option>Claro</option>
                                        <option>Oscuro</option>
                                        <option>Automático</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label-custom">
                                        <i class="bi bi-translate"></i>
                                        Idioma
                                    </label>
                                    <select class="form-control form-control-custom">
                                        <option>Español</option>
                                        <option>English</option>
                                        <option>Português</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="notifications" checked>
                            <label class="form-check-label" for="notifications">
                                <i class="bi bi-bell me-2"></i>
                                Recibir notificaciones
                            </label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="autoSave" checked>
                            <label class="form-check-label" for="autoSave">
                                <i class="bi bi-cloud-check me-2"></i>
                                Guardado automático
                            </label>
                        </div>
                        <button type="button" class="btn btn-custom">
                            <i class="bi bi-floppy me-2"></i>
                            Guardar Preferencias
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.password-toggle {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6c757d;
    z-index: 5;
    cursor: pointer;
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: #667eea;
}

.form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}
</style>

<script>
// Función para mostrar/ocultar contraseñas
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// Verificador de fortaleza de contraseña
document.getElementById('newPassword').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    let strength = 0;
    let text = '';
    let className = '';
    
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    switch (strength) {
        case 0:
        case 1:
            text = 'Muy débil';
            className = 'strength-weak';
            break;
        case 2:
            text = 'Débil';
            className = 'strength-weak';
            break;
        case 3:
            text = 'Regular';
            className = 'strength-fair';
            break;
        case 4:
            text = 'Buena';
            className = 'strength-good';
            break;
        case 5:
            text = 'Muy fuerte';
            className = 'strength-strong';
            break;
    }
    
    strengthBar.className = `strength-bar ${className}`;
    strengthText.textContent = password.length > 0 ? `Fortaleza: ${text}` : '';
    strengthText.style.color = strength >= 3 ? '#28a745' : '#dc3545';
});

// Verificador de coincidencia de contraseñas
document.getElementById('confirmPassword').addEventListener('input', function() {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = this.value;
    const matchText = document.getElementById('matchText');
    
    if (confirmPassword.length > 0) {
        if (newPassword === confirmPassword) {
            matchText.textContent = '✅ Las contraseñas coinciden';
            matchText.style.color = '#28a745';
        } else {
            matchText.textContent = '❌ Las contraseñas no coinciden';
            matchText.style.color = '#dc3545';
        }
    } else {
        matchText.textContent = '';
    }
});

// Manejar envío del formulario de contraseña
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (!currentPassword || !newPassword || !confirmPassword) {
        showNotification('Por favor completa todos los campos', 'error');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showNotification('Las contraseñas nuevas no coinciden', 'error');
        return;
    }
    
    if (newPassword.length < 8) {
        showNotification('La nueva contraseña debe tener al menos 8 caracteres', 'error');
        return;
    }
    
    // Aquí iría la lógica para actualizar la contraseña
    showNotification('Contraseña actualizada exitosamente', 'success');
    this.reset();
});

// Función para mostrar notificaciones
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Animaciones de entrada
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.info-card, .profile-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
});
</script>

<?php include('includes/footer.php'); ?>

