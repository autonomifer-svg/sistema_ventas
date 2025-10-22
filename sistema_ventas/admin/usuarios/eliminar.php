<?php
session_start();
// INCLUSIÓN DE ARCHIVOS Y VERIFICACIÓN DE ROLES
// -----------------------------------------------
include('../../includes/header.php');
require_once('../../includes/conexion.php');
require_once('../../includes/auth.php');
require_once('../../includes/roles.php');

verificarAutenticacion();
if (!esAdministrador()) {
    header("Location: /index.php");
    exit;
}

// INICIALIZACIÓN Y OBTENCIÓN DEL ID DE USUARIO
// ---------------------------------------------
$error = '';
$usuario_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$usuario_id) {
    header("Location: listar.php?error=ID no válido");
    exit;
}

// OBTENER DATOS DEL USUARIO A ELIMINAR
// -------------------------------------
// NOTA: Se utiliza la tabla `usuarios`, que es inconsistente con `login.php`.
$sql_get = "SELECT * FROM usuarios WHERE id = ?";
$stmt_get = $conexion->prepare($sql_get);
$stmt_get->bind_param("i", $usuario_id);
$stmt_get->execute();
$usuario = $stmt_get->get_result()->fetch_assoc();

if (!$usuario) {
    header("Location: listar.php?error=Usuario no encontrado");
    exit;
}

// VALIDACIONES DE SEGURIDAD
// ---------------------------

// 1. Evitar que un administrador se elimine a sí mismo.
if ($usuario_id == $_SESSION['user_id']) { // Se asume que el id de usuario en sesión es 'user_id'
    $error = "No puedes eliminar tu propio usuario.";
}

// 2. Verificar si el usuario tiene ventas asociadas para advertir al administrador.
// NOTA: Se utiliza la tabla `ventas`, inconsistente con la tabla `salida` usada en otras partes.
$sql_ventas = "SELECT COUNT(*) as total FROM ventas WHERE usuario_id = ?";
$stmt_ventas = $conexion->prepare($sql_ventas);
$stmt_ventas->bind_param("i", $usuario_id);
$stmt_ventas->execute();
$total_ventas = $stmt_ventas->get_result()->fetch_assoc()['total'];

// PROCESAMIENTO DE LA ELIMINACIÓN (MÉTODO POST)
// -----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_eliminacion']) && empty($error)) {
    // Si el usuario tiene ventas y no se fuerza la eliminación, mostrar error.
    if ($total_ventas > 0 && !isset($_POST['forzar_eliminacion'])) {
        $error = "Este usuario tiene ventas asociadas. Debes marcar la casilla para forzar la eliminación.";
    } else {
        // Se utiliza una transacción para asegurar la integridad de los datos.
        $conexion->begin_transaction();
        try {
            // Si hay ventas asociadas, se desvinculan del usuario (se pone el `usuario_id` a NULL).
            // Esto evita errores de clave foránea y mantiene el registro de la venta.
            if ($total_ventas > 0) {
                $sql_update_ventas = "UPDATE ventas SET usuario_id = NULL WHERE usuario_id = ?";
                $stmt_update_ventas = $conexion->prepare($sql_update_ventas);
                $stmt_update_ventas->bind_param("i", $usuario_id);
                $stmt_update_ventas->execute();
            }
            
            // Finalmente, se elimina el usuario.
            $sql_delete = "DELETE FROM usuarios WHERE id = ?";
            $stmt_delete = $conexion->prepare($sql_delete);
            $stmt_delete->bind_param("i", $usuario_id);
            
            if ($stmt_delete->execute()) {
                $conexion->commit(); // Se confirman los cambios.
                $_SESSION['mensaje_success'] = "Usuario eliminado exitosamente";
                header("Location: listar.php");
                exit;
            } else {
                throw new Exception("Error al ejecutar la eliminación.");
            }
        } catch (Exception $e) {
            $conexion->rollback(); // Se revierten los cambios si algo falla.
            $error = "Error en la transacción: " . $e->getMessage();
        }
    }
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Eliminar Usuario</h2>
        <a href="listar.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    
    <div class="card shadow-sm border-danger">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Confirmar Eliminación</h5>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <div class="alert alert-warning">
                <strong>¡Atención!</strong> Esta acción no se puede deshacer. Estás a punto de eliminar el siguiente usuario:
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ID:</strong> <?= $usuario['id'] ?></p>
                            <p><strong>Nombre:</strong> <?= htmlspecialchars($usuario['nombre']) ?></p>
                            <p><strong>Usuario:</strong> <?= htmlspecialchars($usuario['usuario']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($usuario['email'] ?? 'No especificado') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Rol:</strong> <?= ucfirst($usuario['rol']) ?></p>
                            <p><strong>Fecha de Registro:</strong> <?= date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) ?></p>
                            <p><strong>Último Acceso:</strong> <?= $usuario['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) : 'Nunca' ?></p>
                            <p><strong>Estado:</strong> 
                                <span class="badge <?= ($usuario['activo'] ?? 1) ? 'bg-success' : 'bg-danger' ?>">
                                    <?= ($usuario['activo'] ?? 1) ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($total_ventas > 0): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Información importante:</strong> Este usuario tiene <strong><?= $total_ventas ?></strong> venta(s) asociada(s). 
                    Si procedes con la eliminación, estas ventas quedarán sin usuario asignado pero se mantendrán en el sistema.
                </div>
            <?php endif; ?>
            
            <?php if ($usuario_id != $_SESSION['usuario_id']): ?>
                <form method="POST">
                    <?php if ($total_ventas > 0): ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="forzar_eliminacion" id="forzar_eliminacion" required>
                            <label class="form-check-label text-danger" for="forzar_eliminacion">
                                <strong>Entiendo que este usuario tiene ventas asociadas y confirmo que quiero eliminarlo de todas formas</strong>
                            </label>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="confirmar_eliminacion" id="confirmar_eliminacion" required>
                        <label class="form-check-label text-danger" for="confirmar_eliminacion">
                            <strong>Confirmo que quiero eliminar permanentemente este usuario</strong>
                        </label>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger btn-lg" id="btn-eliminar" disabled>
                                    <i class="bi bi-trash"></i> Eliminar Usuario
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-grid">
                                <a href="listar.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-danger">
                    <strong>No puedes eliminar tu propio usuario.</strong> Si necesitas eliminar este usuario, pídele a otro administrador que lo haga.
                </div>
                <div class="d-grid">
                    <a href="listar.php" class="btn btn-secondary btn-lg">
                        <i class="bi bi-arrow-left"></i> Volver al Listado
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    const btnEliminar = document.getElementById('btn-eliminar');
    
    function verificarCheckboxes() {
        let todosCheck = true;
        checkboxes.forEach(function(checkbox) {
            if (!checkbox.checked) {
                todosCheck = false;
            }
        });
        
        if (btnEliminar) {
            btnEliminar.disabled = !todosCheck;
        }
    }
    
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', verificarCheckboxes);
    });
});
</script>

<?php include('../../includes/footer.php'); ?>
