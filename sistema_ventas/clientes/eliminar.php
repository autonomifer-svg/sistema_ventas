<?php
include('../includes/header.php');
require_once('../includes/conexion.php');

$error = '';
$cliente_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$cliente_id) {
    header("Location: listar.php?error=ID de cliente no válido");
    exit;
}

// Obtener datos del cliente
$sql_get = "SELECT * FROM clientes WHERE id = ?";
$stmt_get = $conexion->prepare($sql_get);
$stmt_get->bind_param("i", $cliente_id);
$stmt_get->execute();
$result_get = $stmt_get->get_result();
$cliente = $result_get->fetch_assoc();

if (!$cliente) {
    header("Location: listar.php?error=Cliente no encontrado");
    exit;
}

// Verificar si el cliente tiene ventas asociadas
$sql_ventas = "SELECT COUNT(*) as total FROM ventas WHERE cliente_id = ?";
$stmt_ventas = $conexion->prepare($sql_ventas);
$stmt_ventas->bind_param("i", $cliente_id);
$stmt_ventas->execute();
$result_ventas = $stmt_ventas->get_result();
$total_ventas = $result_ventas->fetch_assoc()['total'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_eliminacion'])) {
    if ($total_ventas > 0 && !isset($_POST['forzar_eliminacion'])) {
        $error = "Este cliente tiene $total_ventas venta(s) asociada(s). Marca la casilla para confirmar la eliminación forzada.";
    } else {
        // Proceder con la eliminación
        $conexion->begin_transaction();
        
        try {
            // Si se fuerza la eliminación y hay ventas, se pueden transferir o marcar como eliminadas
            if ($total_ventas > 0) {
                // Opción: Marcar las ventas como de "cliente eliminado"
                $sql_update_ventas = "UPDATE ventas SET cliente_id = NULL WHERE cliente_id = ?";
                $stmt_update_ventas = $conexion->prepare($sql_update_ventas);
                $stmt_update_ventas->bind_param("i", $cliente_id);
                $stmt_update_ventas->execute();
            }
            
            // Eliminar el cliente
            $sql_delete = "DELETE FROM clientes WHERE id = ?";
            $stmt_delete = $conexion->prepare($sql_delete);
            $stmt_delete->bind_param("i", $cliente_id);
            
            if ($stmt_delete->execute()) {
                $conexion->commit();
                header("Location: listar.php?success=Cliente eliminado exitosamente");
                exit;
            } else {
                throw new Exception("Error al eliminar el cliente");
            }
        } catch (Exception $e) {
            $conexion->rollback();
            $error = "Error al eliminar el cliente: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Eliminar Cliente</h2>
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
            <strong>¡Atención!</strong> Esta acción no se puede deshacer. Estás a punto de eliminar el siguiente cliente:
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>ID:</strong> <?= $cliente['id'] ?></p>
                        <p><strong>Nombre:</strong> <?= htmlspecialchars($cliente['nombre']) ?></p>
                        <p><strong>Teléfono:</strong> <?= htmlspecialchars($cliente['telefono'] ?? 'No especificado') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Dirección:</strong> <?= htmlspecialchars($cliente['direccion'] ?? 'No especificado') ?></p>
                        <p><strong>Fecha de Registro:</strong> <?= date('d/m/Y H:i', strtotime($cliente['fecha_registro'])) ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($total_ventas > 0): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Información importante:</strong> Este cliente tiene <strong><?= $total_ventas ?></strong> venta(s) asociada(s). 
                Si procedes con la eliminación, estas ventas quedarán sin cliente asignado pero se mantendrán en el sistema.
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <?php if ($total_ventas > 0): ?>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="forzar_eliminacion" id="forzar_eliminacion" required>
                    <label class="form-check-label text-danger" for="forzar_eliminacion">
                        <strong>Entiendo que este cliente tiene ventas asociadas y confirmo que quiero eliminarlo de todas formas</strong>
                    </label>
                </div>
            <?php endif; ?>
            
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="confirmar_eliminacion" id="confirmar_eliminacion" required>
                <label class="form-check-label text-danger" for="confirmar_eliminacion">
                    <strong>Confirmo que quiero eliminar permanentemente este cliente</strong>
                </label>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-danger btn-lg" id="btn-eliminar" disabled>
                            <i class="bi bi-trash"></i> Eliminar Cliente
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

<?php include('../includes/footer.php'); ?>
