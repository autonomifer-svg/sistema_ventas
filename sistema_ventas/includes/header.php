<?php
// INCLUSIÓN DEL ARCHIVO DE CONFIGURACIÓN
// ---------------------------------------
// Se incluye para tener acceso a variables globales como $base_url, que es crucial para construir las URLs de los enlaces.
require_once(__DIR__ . '/../includes/config.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Ventas - Artículos de Limpieza</title>
    <!-- Inclusión de Bootstrap y Bootstrap Icons desde un CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Estilos personalizados */
        body { padding-top: 56px; }
        .user-avatar { /* ... */ }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?= $base_url ?>index.php">
                <i class="bi bi-shop"></i> Limpieza Total
            </a>
            <!-- Botón para menú responsive en móviles -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php 
                // LÓGICA CONDICIONAL PARA LA BARRA DE NAVEGACIÓN
                // ------------------------------------------------
                // Se comprueba si existe una sesión de usuario activa.
                if (isset($_SESSION['user_id'])): 
                ?>
                    <!-- MENÚ PARA USUARIOS AUTENTICADOS -->
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>productos/listar.php">Productos</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>clientes/listar.php">Clientes</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>ventas/nueva.php">Ventas</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>ventas/historial.php">Historial</a></li>
                    </ul>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <span class="user-avatar">
                                    <?php 
                                    // GENERACIÓN DE INICIALES PARA EL AVATAR
                                    // ---------------------------------------
                                    $iniciales = '';
                                    if(isset($_SESSION['user_name'])) {
                                        $partes = explode(' ', $_SESSION['user_name']);
                                        foreach ($partes as $parte) {
                                            $iniciales .= strtoupper(substr($parte, 0, 1));
                                            if (strlen($iniciales) >= 2) break; // Limita a 2 iniciales.
                                        }
                                    }
                                    echo $iniciales;
                                    ?>
                                </span>
                                <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= $base_url ?>perfil.php">Mi perfil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>logout.php">Cerrar sesión</a></li>
                            </ul>
                        </li>
                    </ul>
                <?php else: ?>
                    <!-- MENÚ PARA USUARIOS NO AUTENTICADOS (PÚBLICO) -->
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_url ?>login.php">Iniciar sesión</a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
