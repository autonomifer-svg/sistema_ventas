<?php
// Mover el procesamiento al principio del archivo
require_once(__DIR__ . '/../includes/config.php');
require_once(__DIR__ . '/../includes/conexion.php');
requireAuth();

$errores = [];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    // Validación básica
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    }
    
    if (strlen($nombre) > 100) {
        $errores[] = "El nombre no puede exceder 100 caracteres";
    }
    
    if (!empty($telefono) && strlen($telefono) > 20) {
        $errores[] = "El teléfono no puede exceder 20 caracteres";
    }
    
    if (!empty($direccion) && strlen($direccion) > 200) {
        $errores[] = "La dirección no puede exceder 200 caracteres";
    }
    
    // Si no hay errores, insertar en la base de datos
    if (empty($errores)) {
        $sql = "INSERT INTO clientes (nombre, telefono, direccion) VALUES (?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sss", $nombre, $telefono, $direccion);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje_exito'] = "Cliente registrado exitosamente";
            
            // Redirigir ANTES de enviar cualquier salida
            header("Location: listar.php");
            exit;
        } else {
            $errores[] = "Error al registrar el cliente: " . $stmt->error;
        }
    }
}

// Ahora incluir el header después del procesamiento
include(__DIR__ . '/../includes/header.php');
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Nuevo Cliente</h2>
        <a href="listar.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        <div class="alert alert-success"><?= $_SESSION['mensaje_exito'] ?></div>
        <?php unset($_SESSION['mensaje_exito']); ?>
    <?php endif; ?>
    
    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
            <strong>Por favor, corrige los siguientes errores:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-person-plus"></i> Registrar Cliente</h5>
        </div>
        <div class="card-body">
            <form method="POST" novalidate>
                <div class="mb-3">
                    <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= in_array('El nombre es obligatorio', $errores) ? 'is-invalid' : '' ?>" 
                           name="nombre" required maxlength="100"
                           placeholder="Ej: Juan Pérez García" 
                           value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>">
                    <div class="form-text">Campo obligatorio. Máximo 100 caracteres.</div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" name="telefono" maxlength="20"
                               placeholder="Ej: +595 21 123456" 
                               value="<?= isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : '' ?>">
                        <div class="form-text">Opcional. Máximo 20 caracteres.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Dirección</label>
                        <input type="text" class="form-control" name="direccion" maxlength="200"
                               placeholder="Ej: Calle Principal #123" 
                               value="<?= isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : '' ?>">
                        <div class="form-text">Opcional. Máximo 200 caracteres.</div>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="reset" class="btn btn-outline-secondary" onclick="return confirm('¿Estás seguro de limpiar el formulario?')">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Registrar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Información adicional -->
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Información</h6>
        </div>
        <div class="card-body">
            <small class="text-muted">
                <strong>Campos requeridos:</strong> Solo el nombre es obligatorio.<br>
                <strong>Validaciones:</strong> Se verificará la longitud de los campos antes de guardar.<br>
                <strong>Fecha de registro:</strong> Se asignará automáticamente la fecha y hora actual.
            </small>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../includes/footer.php'); ?>