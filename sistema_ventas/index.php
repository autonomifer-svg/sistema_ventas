<?php
// INCLUSIÓN DE ARCHIVOS Y VERIFICACIÓN DE SEGURIDAD
// --------------------------------------------------

// Incluye el archivo de configuración global. 
// Este archivo probablemente inicia la sesión (session_start()), define constantes como la URL base ($base_url)
// y puede que cargue otras funciones esenciales para el sistema.
require_once('includes/config.php');

// Llama a la función requireAuth(). 
// Esta es una medida de seguridad crucial que verifica si el usuario ha iniciado sesión.
// Si el usuario no está autenticado, esta función probablemente lo redirigirá a la página de login (login.php)
// para evitar el acceso no autorizado a esta página.
requireAuth(); // Requiere autenticación

// Incluye el archivo del encabezado de la página.
// Este archivo contiene la parte superior de la estructura HTML (<html>, <head>, <body>, la barra de navegación, etc.).
// Se incluye después de la lógica principal para que la página se muestre solo si el usuario está autenticado.
include('includes/header.php');
?>

<style>
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px;
    padding: 50px 30px;
    margin-bottom: 40px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="25" cy="75" r="0.5" fill="white" opacity="0.1"/><circle cx="75" cy="25" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    animation: float 20s infinite linear;
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2;
}

@keyframes float {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
}

.module-card {
    border: none;
    border-radius: 20px;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    height: 100%;
    position: relative;
    overflow: hidden;
    background: white;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.module-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    transition: left 0.5s;
}

.module-card:hover::before {
    left: 100%;
}

.module-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
}

.card-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 2.5rem;
    color: white;
    position: relative;
    z-index: 2;
}

.productos-gradient { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.clientes-gradient { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.ventas-gradient { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.historial-gradient { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.reportes-gradient { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
.perfil-gradient { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }

.card-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 15px;
}

.card-text {
    color: #718096;
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 25px;
}

.btn-modern {
    border-radius: 25px;
    padding: 12px 30px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    text-decoration: none;
    display: inline-block;
    position: relative;
    overflow: hidden;
}

.btn-primary-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary-modern:hover {
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    color: white;
}

.btn-success-modern {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
}

.btn-success-modern:hover {
    background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(72, 187, 120, 0.4);
    color: white;
}

.btn-info-modern {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.btn-info-modern:hover {
    background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(79, 172, 254, 0.4);
    color: white;
}

.btn-warning-modern {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
}

.btn-warning-modern:hover {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(251, 191, 36, 0.4);
    color: white;
}

.stats-section {
    background: white;
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 40px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.stat-item {
    text-align: center;
    padding: 20px;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 5px;
}

.stat-label {
    color: #718096;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.welcome-text {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 30px;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .hero-section {
        padding: 30px 20px;
    }
    
    .module-card:hover {
        transform: translateY(-5px) scale(1.01);
    }
    
    .card-icon {
        width: 60px;
        height: 60px;
        font-size: 2rem;
        margin-bottom: 15px;
    }
    
    .stat-number {
        font-size: 2rem;
    }
}

.pulse-animation {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>

<div class="container-fluid px-4">
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <h1 class="display-4 fw-bold mb-4">
                <i class="bi bi-shop"></i> Sistema de Ventas Yami
            </h1>
            <p class="welcome-text">
                Bienvenido al sistema de gestión más moderno para tu negocio de productos de limpieza.<br>
                Administra tu inventario, clientes y ventas de manera eficiente.
            </p>
            <a href="<?= $base_url ?>ventas/nueva.php" class="btn btn-success-modern btn-lg pulse-animation">
                <i class="bi bi-plus-circle"></i> Iniciar Nueva Venta
            </a>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-section">
        <div class="row">
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number">
                        <i class="bi bi-box-seam"></i> 0
                    </div>
                    <div class="stat-label">Productos</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number">
                        <i class="bi bi-people"></i> 0
                    </div>
                    <div class="stat-label">Clientes</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number">
                        <i class="bi bi-cart-check"></i> 0
                    </div>
                    <div class="stat-label">Ventas Hoy</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number">
                        <i class="bi bi-currency-dollar"></i> 0
                    </div>
                    <div class="stat-label">Ingresos Hoy</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modules Grid -->
    <div class="row g-4">
        <!-- Productos -->
        <div class="col-lg-4 col-md-6">
            <div class="card module-card">
                <div class="card-body p-4 text-center">
                    <div class="card-icon productos-gradient">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h5 class="card-title">Productos</h5>
                    <p class="card-text">Administra tu inventario completo de productos de limpieza. Controla stock, precios y categorías.</p>
                    <a href="<?= $base_url ?>productos/listar.php" class="btn btn-primary-modern btn-modern">
                        <i class="bi bi-arrow-right"></i> Gestionar Productos
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Clientes -->
        <div class="col-lg-4 col-md-6">
            <div class="card module-card">
                <div class="card-body p-4 text-center">
                    <div class="card-icon clientes-gradient">
                        <i class="bi bi-people"></i>
                    </div>
                    <h5 class="card-title">Clientes</h5>
                    <p class="card-text">Gestiona tu base de datos de clientes. Registra información de contacto y historial de compras.</p>
                    <a href="<?= $base_url ?>clientes/listar.php" class="btn btn-primary-modern btn-modern">
                        <i class="bi bi-arrow-right"></i> Gestionar Clientes
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Nueva Venta -->
        <div class="col-lg-4 col-md-6">
            <div class="card module-card">
                <div class="card-body p-4 text-center">
                    <div class="card-icon ventas-gradient">
                        <i class="bi bi-cart-plus"></i>
                    </div>
                    <h5 class="card-title">Nueva Venta</h5>
                    <p class="card-text">Procesa nuevas ventas de manera rápida y eficiente. Sistema intuitivo de carrito de compras.</p>
                    <a href="<?= $base_url ?>ventas/nueva.php" class="btn btn-success-modern btn-modern">
                        <i class="bi bi-plus-circle"></i> Realizar Venta
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Historial de Ventas -->
        <div class="col-lg-4 col-md-6">
            <div class="card module-card">
                <div class="card-body p-4 text-center">
                    <div class="card-icon historial-gradient">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h5 class="card-title">Historial de Ventas</h5>
                    <p class="card-text">Consulta el registro completo de todas tus transacciones y movimientos comerciales.</p>
                    <a href="<?= $base_url ?>ventas/historial.php" class="btn btn-info-modern btn-modern">
                        <i class="bi bi-search"></i> Ver Historial
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Reportes -->
        <div class="col-lg-4 col-md-6">
            <div class="card module-card">
                <div class="card-body p-4 text-center">
                    <div class="card-icon reportes-gradient">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <h5 class="card-title">Reportes y Estadísticas</h5>
                    <p class="card-text">Genera reportes detallados de ventas, productos más vendidos y análisis de rendimiento.</p>
                    <a href="<?= $base_url ?>reportes/ventas.php" class="btn btn-warning-modern btn-modern">
                        <i class="bi bi-bar-chart"></i> Ver Reportes
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Mi Perfil -->
        <div class="col-lg-4 col-md-6">
            <div class="card module-card">
                <div class="card-body p-4 text-center">
                    <div class="card-icon perfil-gradient">
                        <i class="bi bi-person-gear"></i>
                    </div>
                    <h5 class="card-title">Mi Perfil</h5>
                    <p class="card-text">Administra tu información personal, configuraciones y preferencias del sistema.</p>
                    <a href="<?= $base_url ?>perfil.php" class="btn btn-primary-modern btn-modern">
                        <i class="bi bi-gear"></i> Configurar Perfil
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="mt-5 pt-4 border-top">
        <h3 class="text-center mb-4 text-muted">Acciones Rápidas</h3>
        <div class="row justify-content-center">
            <div class="col-auto mb-2">
                <a href="<?= $base_url ?>ventas/nueva.php" class="btn btn-success-modern btn-modern">
                    <i class="bi bi-plus-lg"></i> Nueva Venta
                </a>
            </div>
            <div class="col-auto mb-2">
                <a href="<?= $base_url ?>productos/crear.php" class="btn btn-primary-modern btn-modern">
                    <i class="bi bi-box-seam"></i> Agregar Producto
                </a>
            </div>
            <div class="col-auto mb-2">
                <a href="<?= $base_url ?>clientes/crear.php" class="btn btn-info-modern btn-modern">
                    <i class="bi bi-person-plus"></i> Nuevo Cliente
                </a>
            </div>
            <div class="col-auto mb-2">
                <a href="<?= $base_url ?>ventas/historial.php" class="btn btn-warning-modern btn-modern">
                    <i class="bi bi-clock-history"></i> Ver Ventas
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animación de números (simulación - puedes conectar con datos reales)
    const statNumbers = document.querySelectorAll('.stat-number');
    
    // Efecto de hover en las tarjetas de módulos
    const moduleCards = document.querySelectorAll('.module-card');
    moduleCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.zIndex = '10';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.zIndex = '1';
        });
    });

    // Efecto de particle/floating animation
    function createFloatingElement() {
        const hero = document.querySelector('.hero-section');
        const element = document.createElement('div');
        element.style.position = 'absolute';
        element.style.width = Math.random() * 10 + 5 + 'px';
        element.style.height = element.style.width;
        element.style.background = 'rgba(255,255,255,0.1)';
        element.style.borderRadius = '50%';
        element.style.left = Math.random() * 100 + '%';
        element.style.top = '100%';
        element.style.pointerEvents = 'none';
        element.style.zIndex = '1';
        
        hero.appendChild(element);
        
        const duration = Math.random() * 3000 + 2000;
        element.animate([
            { transform: 'translateY(0px) translateX(0px)', opacity: 0 },
            { transform: `translateY(-${hero.offsetHeight + 100}px) translateX(${Math.random() * 200 - 100}px)`, opacity: 1 },
            { transform: `translateY(-${hero.offsetHeight + 200}px) translateX(${Math.random() * 300 - 150}px)`, opacity: 0 }
        ], {
            duration: duration,
            easing: 'ease-out'
        }).addEventListener('finish', () => {
            element.remove();
        });
    }

    // Crear elementos flotantes cada cierto tiempo
    setInterval(createFloatingElement, 800);
});
</script>

<?php include('includes/footer.php'); ?>
