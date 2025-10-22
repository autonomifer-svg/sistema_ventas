<?php
// INCLUSIÓN DE ARCHIVOS Y VERIFICACIÓN DE SEGURIDAD
// --------------------------------------------------
require_once(__DIR__ . '/../includes/config.php');
requireAuth();
require_once(__DIR__ . '/../includes/conexion.php');

// INICIALIZACIÓN Y VALIDACIÓN DEL ID
// -----------------------------------
$error = '';
$cliente_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$cliente_id) {
    header("Location: listar.php?error=ID de cliente no válido");
    exit;
}

// OBTENCIÓN DE DATOS DEL CLIENTE
// --------------------------------
$sql_get = "SELECT * FROM clientes WHERE NroCliente = ?";
$stmt_get = $conexion->prepare($sql_get);
$stmt_get->bind_param("i", $cliente_id);
$stmt_get->execute();
$result_get = $stmt_get->get_result();
$cliente = $result_get->fetch_assoc();

if (!$cliente) {
    header("Location: listar.php?error=Cliente no encontrado");
    exit;
}

// VERIFICACIÓN DE VENTAS ASOCIADAS
// ----------------------------------
// Se comprueba si el cliente tiene registros en la tabla de ventas (`salida`).
// Esto es crucial para no permitir un borrado que rompa la integridad de los datos.
$sql_ventas = "SELECT COUNT(*) as total FROM salida WHERE NroCliente = ?";
$stmt_ventas = $conexion->prepare($sql_ventas);
$stmt_ventas->bind_param("i", $cliente_id);
$stmt_ventas->execute();
$total_ventas = $stmt_ventas->get_result()->fetch_assoc()['total'];

// LÓGICA DE INACTIVACIÓN (AL ENVIAR EL FORMULARIO)
// -------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_eliminacion'])) {
    // Si el cliente tiene ventas y no se marcó la casilla de forzar, se muestra un error.
    if ($total_ventas > 0 && !isset($_POST['forzar_eliminacion'])) {
        $error = "Este cliente tiene $total_ventas venta(s) asociada(s). Marca la casilla para confirmar la inactivación forzada.";
    } else {
        // BORRADO LÓGICO: En lugar de un DELETE, se hace un UPDATE para marcar al cliente como inactivo.
        // Esto preserva el historial de ventas y mantiene la integridad de la base de datos.
        $sql_inactivar = "UPDATE clientes SET Inactivo = 1 WHERE NroCliente = ?";
        $stmt_inactivar = $conexion->prepare($sql_inactivar);
        $stmt_inactivar->bind_param("i", $cliente_id);
        
        if ($stmt_inactivar->execute()) {
            // Si la inactivación es exitosa, redirige al listado con un mensaje de éxito.
            header("Location: listar.php?success=Cliente inactivado exitosamente");
            exit;
        } else {
            $error = "Error al inactivar el cliente: " . $stmt_inactivar->error;
        }
    }
}

// INCLUSIÓN DEL ENCABEZADO HTML
// -----------------------------
include(__DIR__ . '/../includes/header.php');
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Inactivar Cliente</h2>
        <a href="listar.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm border-warning">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Confirmar Inactivación</h5>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="alert alert-info">
                <strong>ℹ️ Información:</strong> Este cliente será marcado como <strong>inactivo</strong> en lugar de eliminarse. 
                No aparecerá en las listas principales pero sus datos históricos se conservarán.
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ID:</strong> <?= $cliente['NroCliente'] ?></p>
                            <p><strong>Nombre:</strong> <?= htmlspecialchars($cliente['Nombre']) ?></p>
                            <p><strong>RUC:</strong> <?= htmlspecialchars($cliente['Ruc'] ?? 'No especificado') ?></p>
                            <p><strong>Teléfono:</strong> <?= htmlspecialchars($cliente['Telefono'] ?? 'No especificado') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Razón Social:</strong> <?= htmlspecialchars($cliente['RazonSocial'] ?? 'No especificado') ?></p>
                            <p><strong>Dirección:</strong> <?= htmlspecialchars($cliente['Direccion'] ?? 'No especificado') ?></p>
                            <p><strong>Fecha de Registro:</strong> <?= isset($cliente['FechaAct']) ? date('d/m/Y H:i', strtotime($cliente['FechaAct'])) : 'No disponible' ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($total_ventas > 0): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-circle"></i>
                    <strong>Atención:</strong> Este cliente tiene <strong><?= $total_ventas ?></strong> venta(s) asociada(s). 
                    Al inactivarlo, no podrás realizar nuevas ventas a este cliente, pero su historial se mantendrá intacto.
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <?php if ($total_ventas > 0): ?>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="forzar_eliminacion" id="forzar_eliminacion" required>
                        <label class="form-check-label text-warning" for="forzar_eliminacion">
                            <strong>Entiendo que este cliente tiene ventas asociadas y confirmo que quiero inactivarlo</strong>
                        </label>
                    </div>
                <?php endif; ?>
                
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="confirmar_eliminacion" id="confirmar_eliminacion" required>
                    <label class="form-check-label text-warning" for="confirmar_eliminacion">
                        <strong>Confirmo que quiero inactivar este cliente</strong>
                    </label>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning btn-lg" id="btn-inactivar" disabled>
                                <i class="bi bi-x-circle"></i> Inactivar Cliente
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-grid">
                            <a href="listar.php" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-arrow-left-circle"></i> Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-info-circle"></i> ¿Qué significa inactivar?</h6>
        </div>
        <div class="card-body">
            <ul class="mb-0">
                <li>El cliente no aparecerá en las listas de selección para nuevas ventas</li>
                <li>Su historial de ventas se mantiene intacto</li>
                <li>Puedes reactivarlo en cualquier momento desde la base de datos</li>
                <li>No se pierden datos históricos ni relaciones con ventas existentes</li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    const btnInactivar = document.getElementById('btn-inactivar');
    
    function verificarCheckboxes() {
        let todosCheck = true;
        checkboxes.forEach(function(checkbox) {
            if (!checkbox.checked) {
                todosCheck = false;
            }
        });
        
        btnInactivar.disabled = !todosCheck;
    }
    
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', verificarCheckboxes);
    });
});
</script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>
