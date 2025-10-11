<?php
include('../includes/header.php');
require_once('../includes/conexion.php');

// Obtener el ID del cliente
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener datos del cliente
$sql = "SELECT * FROM clientes WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();

// Si no se encuentra el cliente
if (!$cliente) {
    header("Location: listar.php?error=Cliente no encontrado");
    exit;
}

// Actualizar cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];

    // Validación básica
    $errores = [];
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    }
    
    // Si no hay errores, actualizar en la base de datos
    if (empty($errores)) {
        $sql = "UPDATE clientes SET nombre = ?, telefono = ?, direccion = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssi", $nombre, $telefono, $direccion, $id);
        
        if ($stmt->execute()) {
            // Actualizar datos locales
            $cliente['nombre'] = $nombre;
            $cliente['telefono'] = $telefono;
            $cliente['direccion'] = $direccion;
            
            $mensaje = "Cliente actualizado exitosamente";
        } else {
            $errores[] = "Error al actualizar el cliente: " . $stmt->error;
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Editar Cliente</h2>
    <a href="listar.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <strong>Errores:</strong>
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (isset($mensaje)): ?>
            <div class="alert alert-success">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="nombre" required
                       value="<?= htmlspecialchars($cliente['nombre']) ?>">
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" class="form-control" name="telefono" 
                           value="<?= htmlspecialchars($cliente['telefono']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Dirección</label>
                    <input type="text" class="form-control" name="direccion" 
                           value="<?= htmlspecialchars($cliente['direccion']) ?>">
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <a href="listar.php" class="btn btn-outline-secondary me-md-2">
                    <i class="bi bi-x-circle"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
