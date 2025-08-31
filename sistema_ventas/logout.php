<?php
require_once('includes/config.php');

// Guardar información del usuario antes de destruir la sesión
$userName = $_SESSION['user_name'] ?? 'Usuario';
$loginTime = $_SESSION['login_time'] ?? time();

// Calcular duración de la sesión
$sessionDuration = time() - $loginTime;
$hours = floor($sessionDuration / 3600);
$minutes = floor(($sessionDuration % 3600) / 60);

// Destruir todas las variables de sesión
$_SESSION = array();

// Borrar la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

include('includes/header.php');
?>

<style>
.logout-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

.logout-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border: none;
    border-radius: 25px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    max-width: 500px;
    width: 100%;
    text-align: center;
    animation: fadeInScale 0.8s ease-out;
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.8) translateY(30px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.logout-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 3rem 2rem;
    color: white;
    position: relative;
    overflow: hidden;
}

.logout-header::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
}

.goodbye-icon {
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 3rem;
    color: white;
    animation: wave 2s ease-in-out infinite;
    position: relative;
    z-index: 2;
}

@keyframes wave {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(20deg); }
    75% { transform: rotate(-20deg); }
}

.logout-title {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
    position: relative;
    z-index: 2;
}

.logout-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
    position: relative;
    z-index: 2;
}

.logout-body {
    padding: 2.5rem;
}

.user-info-section {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.user-info-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.session-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-3px);
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #667eea;
    display: block;
}

.stat-label {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: center;
}

.btn-primary-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 15px;
    padding: 1rem 2.5rem;
    font-weight: 600;
    font-size: 1.1rem;
    color: white;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-primary-custom::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.2);
    transition: left 0.3s;
}

.btn-primary-custom:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

.btn-primary-custom:hover::before {
    left: 100%;
}

.redirect-info {
    color: #6c757d;
    font-size: 0.9rem;
    margin-top: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.spinner-custom {
    width: 16px;
    height: 16px;
    border: 2px solid #dee2e6;
    border-top: 2px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.floating-elements {
    position: absolute;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.floating-element {
    position: absolute;
    opacity: 0.1;
    animation: floatAround 10s infinite ease-in-out;
    color: white;
}

.floating-element:nth-child(1) { top: 10%; left: 10%; animation-delay: 0s; }
.floating-element:nth-child(2) { top: 20%; right: 15%; animation-delay: 2s; }
.floating-element:nth-child(3) { bottom: 30%; left: 20%; animation-delay: 4s; }
.floating-element:nth-child(4) { bottom: 20%; right: 10%; animation-delay: 6s; }
.floating-element:nth-child(5) { top: 60%; left: 50%; animation-delay: 8s; }

@keyframes floatAround {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    33% { transform: translateY(-30px) rotate(120deg); }
    66% { transform: translateY(15px) rotate(240deg); }
}

/* Responsive */
@media (max-width: 576px) {
    .logout-container {
        padding: 10px;
    }
    
    .logout-header {
        padding: 2rem 1.5rem;
    }
    
    .logout-title {
        font-size: 1.5rem;
    }
    
    .logout-body {
        padding: 1.5rem;
    }
    
    .session-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
}
</style>

<div class="logout-container">
    <!-- Elementos flotantes decorativos -->
    <div class="floating-elements">
        <div class="floating-element">
            <i class="bi bi-heart-fill" style="font-size: 2rem;"></i>
        </div>
        <div class="floating-element">
            <i class="bi bi-star-fill" style="font-size: 1.5rem;"></i>
        </div>
        <div class="floating-element">
            <i class="bi bi-emoji-smile" style="font-size: 2.5rem;"></i>
        </div>
        <div class="floating-element">
            <i class="bi bi-check-circle-fill" style="font-size: 2rem;"></i>
        </div>
        <div class="floating-element">
            <i class="bi bi-shield-check" style="font-size: 1.8rem;"></i>
        </div>
    </div>

    <div class="logout-card">
        <div class="logout-header">
            <div class="goodbye-icon">
                <i class="bi bi-hand-thumbs-up"></i>
            </div>
            <h1 class="logout-title">¡Hasta Pronto!</h1>
            <p class="logout-subtitle">Tu sesión se ha cerrado exitosamente</p>
        </div>
        
        <div class="logout-body">
            <div class="user-info-section">
                <h5 style="color: #495057; margin-bottom: 1rem;">
                    <i class="bi bi-person-check me-2" style="color: #667eea;"></i>
                    Resumen de tu Sesión
                </h5>
                
                <div style="color: #6c757d; font-size: 1.1rem; margin-bottom: 0.5rem;">
                    <strong style="color: #495057;"><?= htmlspecialchars($userName) ?></strong>
                </div>
                
                <div class="session-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?= $hours ?>h</span>
                        <div class="stat-label">Minutos activo</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">✅</span>
                        <div class="stat-label">Sesión segura</div>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <a href="login.php" class="btn-primary-custom" id="loginBtn">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Iniciar Sesión Nuevamente
                </a>
                
                <div class="redirect-info" id="redirectInfo" style="display: none;">
                    <div class="spinner-custom"></div>
                    <span>Redirigiendo automáticamente en <span id="countdown">5</span> segundos...</span>
                </div>
            </div>

            <!-- Mensaje de agradecimiento -->
            <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(102, 126, 234, 0.1); border-radius: 15px; border-left: 4px solid #667eea;">
                <h6 style="color: #667eea; margin-bottom: 0.5rem;">
                    <i class="bi bi-heart me-2"></i>¡Gracias por usar nuestro sistema!
                </h6>
                <p style="margin: 0; color: #6c757d; font-size: 0.9rem;">
                    Esperamos que hayas tenido una excelente experiencia. Tu seguridad y privacidad son importantes para nosotros.
                </p>
            </div>

            <!-- Consejos de seguridad -->
            <div style="margin-top: 1.5rem; text-align: left;">
                <h6 style="color: #495057; margin-bottom: 1rem;">
                    <i class="bi bi-shield-lock me-2" style="color: #28a745;"></i>
                    Consejos de Seguridad
                </h6>
                <div style="font-size: 0.85rem; color: #6c757d;">
                    <div style="display: flex; align-items: flex-start; margin-bottom: 0.5rem;">
                        <i class="bi bi-check-circle-fill me-2" style="color: #28a745; margin-top: 0.1rem;"></i>
                        <span>Siempre cierra tu sesión al terminar</span>
                    </div>
                    <div style="display: flex; align-items: flex-start; margin-bottom: 0.5rem;">
                        <i class="bi bi-check-circle-fill me-2" style="color: #28a745; margin-top: 0.1rem;"></i>
                        <span>No compartas tus credenciales de acceso</span>
                    </div>
                    <div style="display: flex; align-items: flex-start;">
                        <i class="bi bi-check-circle-fill me-2" style="color: #28a745; margin-top: 0.1rem;"></i>
                        <span>Mantén tu contraseña actualizada</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script de redirección automática -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar información de redirección después de 3 segundos
    setTimeout(() => {
        const redirectInfo = document.getElementById('redirectInfo');
        const loginBtn = document.getElementById('loginBtn');
        
        redirectInfo.style.display = 'flex';
        loginBtn.style.opacity = '0.7';
        loginBtn.style.pointerEvents = 'none';
        
        // Iniciar countdown
        let countdown = 5;
        const countdownElement = document.getElementById('countdown');
        
        const countdownInterval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                // Redirigir con efecto de desvanecimiento
                document.body.style.transition = 'opacity 0.5s ease-out';
                document.body.style.opacity = '0';
                
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 500);
            }
        }, 1000);
        
    }, 3000);
    
    // Efecto de partículas de celebración
    createConfetti();
});

// Función para crear efecto de confetti
function createConfetti() {
    const colors = ['#667eea', '#764ba2', '#28a745', '#ffc107', '#dc3545'];
    const container = document.querySelector('.logout-container');
    
    for (let i = 0; i < 50; i++) {
        setTimeout(() => {
            const confetti = document.createElement('div');
            confetti.style.cssText = `
                position: absolute;
                width: 6px;
                height: 6px;
                background: ${colors[Math.floor(Math.random() * colors.length)]};
                border-radius: 50%;
                pointer-events: none;
                z-index: 1000;
                left: ${Math.random() * 100}%;
                top: -10px;
                animation: confettiFall ${2 + Math.random() * 3}s linear forwards;
                opacity: 0.8;
            `;
            
            container.appendChild(confetti);
            
            // Remover después de la animación
            setTimeout(() => {
                confetti.remove();
            }, 5000);
        }, i * 100);
    }
}

// CSS para la animación de confetti
const style = document.createElement('style');
style.textContent = `
@keyframes confettiFall {
    0% {
        transform: translateY(0) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(100vh) rotate(720deg);
        opacity: 0;
    }
}

.logout-card {
    animation: fadeInScale 0.8s ease-out, floatGentle 3s ease-in-out infinite 1s;
}

@keyframes floatGentle {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}
`;
document.head.appendChild(style);

// Prevenir redirección si el usuario interactúa
document.getElementById('loginBtn').addEventListener('click', function() {
    // Cancelar cualquier redirección automática
    const redirectInfo = document.getElementById('redirectInfo');
    if (redirectInfo.style.display !== 'none') {
        location.href = 'login.php';
    }
});

// Efecto de ondas en el botón
document.getElementById('loginBtn').addEventListener('click', function(e) {
    const button = this;
    const ripple = document.createElement('span');
    const rect = button.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = e.clientX - rect.left - size / 2;
    const y = e.clientY - rect.top - size / 2;
    
    ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple 0.6s ease-out;
        pointer-events: none;
    `;
    
    button.style.position = 'relative';
    button.style.overflow = 'hidden';
    button.appendChild(ripple);
    
    setTimeout(() => ripple.remove(), 600);
});

// Animación de ripple
const rippleStyle = document.createElement('style');
rippleStyle.textContent = `
@keyframes ripple {
    to {
        transform: scale(2);
        opacity: 0;
    }
}
`;
document.head.appendChild(rippleStyle);
</script>

<?php 
// Pequeño delay para mostrar la pantalla antes de redirigir
// En un entorno real, esto se manejaría con JavaScript
// redirect('login.php'); - Comentado para mostrar la pantalla
?>

<?php include('includes/footer.php'); ?>-label">Horas conectado</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?= $minutes ?>m</span>
                        <div class="stat
