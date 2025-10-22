<?php
session_start();
// INCLUSIÓN DE ARCHIVOS Y VERIFICACIÓN DE ROLES
// -----------------------------------------------
include('../../includes/header.php');
require_once('../../includes/conexion.php');
require_once('../../includes/auth.php'); // Contiene funciones como verificarAutenticacion() y esAdministrador()
require_once('../../includes/roles.php'); // Probablemente contiene definiciones de roles.

// Se asegura de que el usuario esté logueado.
verificarAutenticacion();
// Se asegura de que solo los administradores puedan acceder a esta página.
if (!esAdministrador()) {
    header("Location: /index.php"); // Redirige a los no-administradores.
    exit;
}

// LÓGICA DE PAGINACIÓN
// ---------------------
$por_pagina = 10; // Número de usuarios por página.
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina - 1) * $por_pagina; // Calcula el offset para la consulta SQL.

// OBTENCIÓN DE USUARIOS
// ---------------------
// NOTA: Esta consulta utiliza una tabla `usuarios` que parece ser inconsistente con la tabla `usuario` usada en `login.php`.
// Los nombres de campos (id, nombre, usuario, rol) también difieren.
$sql = "SELECT id, nombre, usuario, email, rol, fecha_registro, ultimo_acceso 
        FROM usuarios 
        ORDER BY fecha_registro DESC
        LIMIT $inicio, $por_pagina";
$usuarios = $conexion->query($sql);

// OBTENER EL NÚMERO TOTAL DE USUARIOS PARA LA PAGINACIÓN
// -----------------------------------------------------
$total_usuarios = $conexion->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'];
$total_paginas = ceil($total_usuarios / $por_pagina); // Calcula el número total de páginas.
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestión de Usuarios</h2>
        <a href="crear.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Nuevo Usuario
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Registro</th>
                            <th>Último Acceso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                        <tr>
                            <td><?= $usuario['id'] ?></td>
                            <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                            <td><?= htmlspecialchars($usuario['usuario']) ?></td>
                            <td><?= ucfirst($usuario['rol']) ?></td>
                            <td><?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?></td>
                            <td><?= $usuario['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) : 'Nunca' ?></td>
                            <td>
                                <a href="editar.php?id=<?= $usuario['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="eliminar.php?id=<?= $usuario['id'] ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <nav aria-label="Paginación de usuarios">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?= $pagina == $i ? 'active' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<?php include('../../includes/footer.php'); ?>
