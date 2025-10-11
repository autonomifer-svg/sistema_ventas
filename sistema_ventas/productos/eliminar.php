<?php
require_once('../includes/config.php');
requireAuth();
require_once('../includes/conexion.php');

$error = '';
$producto_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$producto_id) {
    redirect('productos/listar.php?error=ID de producto no válido');
}

// Obtener datos del producto
$sql_get = "SELECT * FROM productos WHERE CodigoNum = ?";
$stmt_get = $conexion->prepare($sql_get);
$stmt_get->bind_param("i", $producto_id);
$stmt_get->execute();
$result_get = $stmt_get->get_result();
$producto = $result_get->fetch_assoc();

if (!$producto) {
    redirect('productos/listar.php?error=Producto no encontrado');
}

// Verificar si el producto tiene ventas asociadas
$sql_ventas = "SELECT COUNT(*) as total FROM detallesalida WHERE CodigoNum = ?";
$stmt_ventas = $conexion->prepare($sql_ventas);
$stmt_ventas->bind_param("i", $producto_id);
$stmt_ventas->execute();
$result_ventas = $stmt_ventas->get_result();
$total_ventas = $result_ventas->fetch_assoc()['total'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_eliminacion'])) {
    if ($total_ventas > 0 && !isset($_POST['forzar_eliminacion'])) {
        $error = "Este producto tiene $total_ventas venta(s) asociada(s). Marca la casilla para confirmar la inactivación forzada.";
    } else {
        // Proceder con la inactivación (no eliminar, solo marcar como suspendido)
        $conexion->begin_transaction();
        
        try {
            // Marcar como suspendido en lugar de eliminar
            $sql_suspender = "UPDATE productos SET Suspendido = 1 WHERE CodigoNum = ?";
            $stmt_suspender = $conexion->prepare($sql_suspender);
            $stmt_suspender->bind_param("i", $producto_id);
            
            if ($stmt_suspender->execute()) {
                $conexion->commit();
                redirect('productos/listar.php?success=Producto marcado como suspendido exitosamente');
            } else {
                throw new Exception("Error al suspender el producto");
            }
        } catch (Exception $e) {
            $conexion->rollback();
            $error = "Error al eliminar el producto: " . $e->getMessage();
        }
    }
}

include(__DIR__ . '/../includes/header.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Eliminar Producto</h2>
    <a href="<?= $base_url ?>productos/listar.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="card shadow-sm border-danger">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Confirmar Eliminación</h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="alert alert-warning">
            <strong>¡Atención!</strong> Estás a punto de marcar como suspendido el siguiente producto:
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Código Numérico:</strong> <?= $producto['CodigoNum'] ?></p>
                        <p><strong>Código:</strong> <?= htmlspecialchars($producto['Codigo']) ?></p>
                        <p><strong>Descripción:</strong> <?= htmlspecialchars($producto['Descripcion']) ?></p>
                        <p><strong>Precio:</strong> $<?= number_format($producto['PrecioVenta'], 2) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Stock:</strong> <?= number_format($producto['100'], 2) ?></p>
                        <p><strong>Precio Costo:</strong> $<?= number_format($producto['PrecioCosto'], 2) ?></p>
                        <?php if (isset($producto['FechaAct'])): ?>
                            <p><strong>Última Actualización:</strong> <?= date('d/m/Y H:i', strtotime($producto['FechaAct'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($total_ventas > 0): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Información importante:</strong> Este producto tiene <strong><?= $total_ventas ?></strong> venta(s) asociada(s). 
                Si procedes, el producto será marcado como <strong>SUSPENDIDO</strong> pero sus ventas históricas se mantendrán.
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                <strong>Sin ventas asociadas:</strong> Este producto no tiene ventas registradas. Puede ser suspendido de forma segura.
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <?php if ($total_ventas > 0): ?>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="forzar_eliminacion" id="forzar_eliminacion" required>
                    <label class="form-check-label text-danger" for="forzar_eliminacion">
                        <strong>Entiendo que este producto tiene ventas asociadas y confirmo que quiero suspenderlo</strong>
                    </label>
                </div>
            <?php endif; ?>
            
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="confirmar_eliminacion" id="confirmar_eliminacion" required>
                <label class="form-check-label text-danger" for="confirmar_eliminacion">
                    <strong>Confirmo que quiero marcar como suspendido este producto</strong>
                </label>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-danger btn-lg" id="btn-eliminar" disabled>
                            <i class="bi bi-x-circle"></i> Suspender Producto
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-grid">
                        <a href="<?= $base_url ?>productos/listar.php" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header bg-info text-white">
        <h6 class="mb-0"><i class="bi bi-lightbulb"></i> ¿Qué significa suspender?</h6>
    </div>
    <div class="card-body">
        <ul class="mb-0">
            <li>El producto <strong>NO se eliminará</strong> de la base de datos</li>
            <li>El producto <strong>NO aparecerá</strong> en listados activos</li>
            <li>Las ventas históricas <strong>se mantendrán intactas</strong></li>
            <li>Puedes <strong>reactivarlo</strong> en cualquier momento desde la base de datos</li>
        </ul>
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
        
        btnEliminar.disabled = !todosCheck;
    }
    
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', verificarCheckboxes);
    });
});
</script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>
