<?php
require_once(__DIR__ . '/../includes/config.php');
requireAuth();

$success = '';
$error = '';
$producto = null;

// Verificar que se proporcione un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('productos/listar.php?error=ID de producto inválido');
}

$id = intval($_GET['id']);

// Obtener datos del producto
try {
    $conexion = conectarDB();
    
    $sql = "SELECT * FROM productos WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error en la preparación: " . $conexion->error);
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conexion->close();
        redirect('productos/listar.php?error=Producto no encontrado');
    }
    
    $producto = $result->fetch_assoc();
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error al obtener producto: " . $e->getMessage());
    redirect('productos/listar.php?error=Error al cargar producto');
}

// Actualizar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);

    // Validaciones
    if (empty($nombre)) {
        $error = "El nombre del producto es obligatorio";
    } elseif ($precio <= 0) {
        $error = "El precio debe ser mayor a 0";
    } elseif ($stock < 0) {
        $error = "El stock no puede ser negativo";
    } else {
        try {
            $sql = "UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ? WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Error en la preparación: " . $conexion->error);
            }
            
            $stmt->bind_param("ssdii", $nombre, $descripcion, $precio, $stock, $id);
            
            if ($stmt->execute()) {
                $success = "Producto actualizado exitosamente";
                
                // Actualizar datos locales para mostrar en el formulario
                $producto['nombre'] = $nombre;
                $producto['descripcion'] = $descripcion;
                $producto['precio'] = $precio;
                $producto['stock'] = $stock;
                
                error_log("Producto actualizado: ID=$id, Nombre='$nombre'");
            } else {
                throw new Exception("Error al ejecutar: " . $stmt->error);
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $error = "Error al actualizar el producto: " . $e->getMessage();
            error_log("Error en actualizar producto: " . $e->getMessage());
        }
    }
}

if (isset($conexion)) {
    $conexion->close();
}

include(__DIR__ . '/../includes/header.php');
?>

<div class="row">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-pencil"></i> Editar Producto</h2>
            <div>
                <a href="<?= $base_url ?>productos/listar.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver a Lista
                </a>
                <a href="<?= $base_url ?>productos/crear.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Nuevo Producto
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Actualizar Información</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" id="editarForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nombre del Producto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nombre" 
                                       value="<?= htmlspecialchars($producto['nombre']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Precio ($) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" class="form-control" name="precio" 
                                       value="<?= $producto['precio'] ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Stock <span class="text-danger">*</span></label>
                                <input type="number" min="0" class="form-control" name="stock" 
                                       value="<?= $producto['stock'] ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="4"><?= htmlspecialchars($producto['descripcion'] ?? '') ?></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?= $base_url ?>productos/listar.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Actualizar Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Información</h6>
            </div>
            <div class="card-body">
                <p><strong>ID del Producto:</strong> <?= $producto['id'] ?></p>
                <?php if (isset($producto['fecha_creacion']) && $producto['fecha_creacion']): ?>
                    <p><strong>Creado:</strong> <?= date('d/m/Y H:i', strtotime($producto['fecha_creacion'])) ?></p>
                <?php endif; ?>
                
                <hr>
                
                <h6>Estado del Stock:</h6>
                <?php 
                $stock = $producto['stock'];
                if ($stock <= 0): ?>
                    <span class="badge bg-danger">Sin Stock</span>
                    <p class="small text-muted mt-1">⚠️ Producto agotado</p>
                <?php elseif ($stock <= 10): ?>
                    <span class="badge bg-warning text-dark">Stock Bajo</span>
                    <p class="small text-muted mt-1">⚠️ Considerar reposición</p>
                <?php else: ?>
                    <span class="badge bg-success">Stock Normal</span>
                    <p class="small text-muted mt-1">✅ Stock suficiente</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bi bi-trash"></i> Zona Peligrosa</h6>
            </div>
            <div class="card-body">
                <p class="small">Si ya no necesitas este producto, puedes eliminarlo permanentemente.</p>
                <a href="<?= $base_url ?>productos/eliminar.php?id=<?= $producto['id'] ?>" 
                   class="btn btn-outline-danger btn-sm"
                   onclick="return confirm('⚠️ ¿Estás seguro de eliminar este producto?\n\nEsta acción no se puede deshacer.\n\nProducto: <?= htmlspecialchars($producto['nombre']) ?>')">
                    <i class="bi bi-trash"></i> Eliminar Producto
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Validación del formulario
document.getElementById('editarForm').addEventListener('submit', function(e) {
    const nombre = document.querySelector('input[name="nombre"]').value.trim();
    const precio = parseFloat(document.querySelector('input[name="precio"]').value);
    const stock = parseInt(document.querySelector('input[name="stock"]').value);
    
    if (!nombre) {
        alert('El nombre del producto es obligatorio');
        e.preventDefault();
        return;
    }
    
    if (precio <= 0) {
        alert('El precio debe ser mayor a 0');
        e.preventDefault();
        return;
    }
    
    if (stock < 0) {
        alert('El stock no puede ser negativo');
        e.preventDefault();
        return;
    }
});
</script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>