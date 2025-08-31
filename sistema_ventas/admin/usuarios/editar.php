<?php
session_start();
include('../../includes/header.php');
require_once('../../includes/conexion.php');
require_once('../../includes/auth.php');
require_once('../../includes/roles.php');

verificarAutenticacion();
if (!esAdministrador()) {
    header("Location: /index.php");
    exit;
}

$error = '';
$success = '';
$usuario_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$usuario_id) {
    header("Location: listar.php");
    exit;
}

// Obtener datos del usuario
$sql_get = "SELECT * FROM usuarios WHERE id = ?";
$stmt_get = $conexion->prepare($sql_get);
$stmt_get->bind_param("i", $usuario_id);
$stmt_get->execute();
$result_get = $stmt_get->get_result();
$usuario = $result_get->fetch_assoc();

if (!$usuario) {
    header("Location: listar.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $usuario_name = trim($_POST['usuario']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $rol = $_POST['rol'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones
    if (empty($nombre) || empty($usuario_name)) {
        $error = "Nombre y usuario son obligatorios";
    } elseif (!empty($password) && $password !== $confirm_password) {
        $error = "Las contraseñas no coinciden";
    } else {
        // Verificar si el usuario ya existe (excluyendo el actual)
        $sql_check = "SELECT id FROM usuarios WHERE usuario = ? AND id != ?";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("si", $usuario_name, $usuario_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $error = "El nombre de usuario ya está en uso";
        } else {
            // Actualizar usuario
            if (!empty($password)) {
                // Con nueva contraseña
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql_update = "UPDATE usuarios SET nombre = ?, usuario = ?, email = ?, password = ?, rol = ?, activo = ? WHERE id = ?";
                $stmt_update = $conexion->prepare($sql_update);
                $stmt_update->bind_param("sssssii", $nombre, $usuario_name, $email, $hashed_password, $rol, $activo, $usuario_id);
            } else {
                // Sin cambio de contraseña
                $sql_update = "UPDATE usuarios SET nombre = ?, usuario = ?, email = ?, rol = ?, activo = ? WHERE id = ?";
                $stmt_update = $conexion->prepare($sql_update);
                $stmt_update->bind_param("ssssii", $nombre, $usuario_name, $email, $rol, $activo, $usuario_id);
            }
            
            if ($stmt_update->execute()) {
                $success = "Usuario actualizado exitosamente";
                // Actualizar datos mostrados
                $usuario['nombre'] = $nombre;
                $usuario['usuario'] = $usuario_name;
                $usuario['email'] = $email;
                $usuario['rol'] = $rol;
                $usuario['activo'] = $activo;
            } else {
                $error = "Error al actualizar el usuario: " . $stmt_update->error;
            }
        }
    }
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Editar Usuario</h2>
        <a href="listar.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" name="nombre" class="form-control" 
                               value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre de Usuario *</label>
                        <input type="text" name="usuario" class="form-control" 
                               value="<?= htmlspecialchars($usuario['usuario']) ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($usuario['email'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Rol *</label>
                        <select name="rol" class="form-select" required>
                            <option value="admin" <?= $usuario['rol'] == 'admin' ? 'selected' : '' ?>>Administrador</option>
                            <option value="vendedor" <?= $usuario['rol'] == 'vendedor' ? 'selected' : '' ?>>Vendedor</option>
                            <option value="inventario" <?= $usuario['rol'] == 'inventario' ? 'selected' : '' ?>>Inventario</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nueva Contraseña (opcional)</label>
                        <input type="password" name="password" class="form-control">
                        <small class="text-muted">Déjalo vacío si no quieres cambiar la contraseña</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" name="confirm_password" class="form-control">
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="activo" class="form-check-input" id="activo" 
                               <?= ($usuario['activo'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="activo">
                            Usuario activo
                        </label>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-grid">
                            <a href="listar.php" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Información adicional -->
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <h5 class="mb-0">Información del Usuario</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>ID:</strong> <?= $usuario['id'] ?></p>
                    <p><strong>Fecha de Registro:</strong> <?= date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) ?></p>
                </div>
                <div class="col-md-6">
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
</div>

<?php include('../../includes/footer.php'); ?>