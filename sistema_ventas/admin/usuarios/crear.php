<?php
require_once(__DIR__ . '/../../includes/config.php');
requireAdmin(); // Si es necesario

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $usuario = trim($_POST['usuario']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $rol = $_POST['rol'];
    
    // Validaciones
    if (empty($nombre) || empty($usuario) || empty($password)) {
        $error = "Nombre, usuario y contraseña son obligatorios";
    } elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden";
    } else {
        // Verificar si el usuario ya existe
        $sql_check = "SELECT id FROM usuarios WHERE usuario = ?";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("s", $usuario);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $error = "El nombre de usuario ya está en uso";
        } else {
            // Crear usuario
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql_insert = "INSERT INTO usuarios (nombre, usuario, email, password, rol) 
                           VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conexion->prepare($sql_insert);
            $stmt_insert->bind_param("sssss", $nombre, $usuario, $email, $hashed_password, $rol);
            
            if ($stmt_insert->execute()) {
                $success = "Usuario creado exitosamente";
                // Limpiar formulario
                $nombre = $usuario = $email = '';
            } else {
                $error = "Error al crear el usuario: " . $stmt_insert->error;
            }
        }
    }
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Crear Nuevo Usuario</h2>
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
                               value="<?= isset($nombre) ? htmlspecialchars($nombre) : '' ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre de Usuario *</label>
                        <input type="text" name="usuario" class="form-control" 
                               value="<?= isset($usuario) ? htmlspecialchars($usuario) : '' ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Rol *</label>
                        <select name="rol" class="form-select" required>
                            <option value="admin">Administrador</option>
                            <option value="vendedor" selected>Vendedor</option>
                            <option value="inventario">Inventario</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contraseña *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirmar Contraseña *</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('../../includes/footer.php'); ?>