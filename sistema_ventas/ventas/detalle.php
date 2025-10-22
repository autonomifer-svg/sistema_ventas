<?php
// INCLUSIÓN DE ARCHIVOS NECESARIOS
// ---------------------------------
include('../includes/header.php');
require_once('../includes/conexion.php');

// OBTENCIÓN Y VALIDACIÓN DEL ID DE LA VENTA
// -----------------------------------------
$venta_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$venta_id) {
    header("Location: historial.php?error=ID de venta no válido");
    exit;
}

// OBTENCIÓN DE LA INFORMACIÓN PRINCIPAL DE LA VENTA
// -------------------------------------------------
// Se consulta la tabla `salida` y se une con otras tablas para obtener información legible (nombre del cliente, sucursal, etc.).
$sql_venta = "SELECT s.*, c.Nombre AS cliente, c.Telefono, c.Direccion,
              suc.Nombre as Sucursal, fp.FormaPago
              FROM salida s
              JOIN clientes c ON s.NroCliente = c.NroCliente
              LEFT JOIN sucursal suc ON s.NroSucursal = suc.NroSucursal
              LEFT JOIN formapago fp ON s.IdFormaPago = fp.IdFormaPago
              WHERE s.IdSalida = ?";
$stmt_venta = $conexion->prepare($sql_venta);
$stmt_venta->bind_param("i", $venta_id);
$stmt_venta->execute();
$venta = $stmt_venta->get_result()->fetch_assoc();

// Si no se encuentra la venta, redirigir al historial.
if (!$venta) {
    header("Location: historial.php?error=Venta no encontrada");
    exit;
}

// OBTENCIÓN DE LOS DETALLES (PRODUCTOS) DE LA VENTA
// ---------------------------------------------------
$sql_detalle = "SELECT ds.*, p.Descripcion AS producto 
                FROM detallesalida ds
                JOIN productos p ON ds.CodigoNum = p.CodigoNum
                WHERE ds.IdSalida = ?";
$stmt_detalle = $conexion->prepare($sql_detalle);
$stmt_detalle->bind_param("i", $venta_id);
$stmt_detalle->execute();
$detalles = $stmt_detalle->get_result();

// CÁLCULO DE ESTADÍSTICAS ADICIONALES PARA LA VISTA
// --------------------------------------------------
$total_productos = 0; // Contador de cuántos tipos de productos diferentes hay en la venta.
$cantidad_total = 0;  // Suma de la cantidad de todos los productos.
$detalles_array = []; // Se guarda el resultado en un array para poder iterarlo en la tabla.

while ($detalle = $detalles->fetch_assoc()) {
    $detalles_array[] = $detalle;
    $total_productos++;
    $cantidad_total += $detalle['Cantidad'];
}
?>

<style>
.gradient-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-radius: 15px;
    margin-bottom: 2rem;
}

.detail-card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.3s ease;
}

.detail-card:hover {
    transform: translateY(-5px);
}

.client-card {
    background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);
    border-left: 5px solid #28a745;
}

.sale-card {
    background: linear-gradient(135deg, #fff8f0 0%, #ffeaa7 100%);
    border-left: 5px solid #ffc107;
}

.card-header-modern {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
    padding: 1.5rem;
    font-weight: 600;
}

.card-body-modern {
    padding: 2rem;
}

.info-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.info-item:last-child {
    border-bottom: none;
}

.info-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin-right: 1rem;
    font-size: 1.2rem;
}

.info-content {
    flex: 1;
}

.info-label {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    text-transform: uppercase;
    font-weight: 600;
}

.info-value {
    font-size: 1rem;
    color: #2d3748;
    font-weight: 500;
}

.stats-row {
    background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 2px solid #e8f5e8;
}

.stat-item {
    text-align: center;
    padding: 1rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #28a745;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    font-weight: 500;
}

.products-card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    font-weight: 600;
}

.table-header th {
    border: none;
    padding: 1.25rem 1rem;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.modern-table {
    margin: 0;
}

.modern-table tbody tr {
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s ease;
}

.modern-table tbody tr:hover {
    background-color: #f8fff9;
}

.modern-table td {
    border: none;
    padding: 1.25rem 1rem;
    vertical-align: middle;
}

.product-name {
    font-weight: 600;
    color: #2d3748;
}

.price-badge {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.quantity-badge {
    background: linear-gradient(135deg, #17a2b8 0%, #6610f2 100%);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 15px;
    font-weight: 600;
    font-size: 0.85rem;
}

.total-display {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    font-weight: bold;
    font-size: 1.1rem;
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
}

.btn-back {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    color: white;
    transition: all 0.3s ease;
}

.btn-back:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(108, 117, 125, 0.3);
    color: white;
}

.receipt-number {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-weight: bold;
    display: inline-block;
}
</style>

<!-- Header con gradiente -->
<div class="gradient-header text-white p-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-2"><i class="bi bi-receipt me-2"></i>Detalle de Venta</h2>
            <div class="receipt-number">
                <i class="bi bi-hash me-1"></i><?= $venta_id ?>
            </div>
        </div>
        <a href="historial.php" class="btn btn-light btn-lg">
            <i class="bi bi-arrow-left me-2"></i>Volver al Historial
        </a>
    </div>
</div>

<!-- Estadísticas rápidas -->
<div class="stats-row">
    <div class="row">
        <div class="col-md-3">
            <div class="stat-item">
                <div class="stat-number"><?= $total_productos ?></div>
                <div class="stat-label">Productos Diferentes</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-item">
                <div class="stat-number"><?= number_format($cantidad_total, 2) ?></div>
                <div class="stat-label">Cantidad Total</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-item">
                <div class="stat-number">$<?= number_format($venta['Total'], 2) ?></div>
                <div class="stat-label">Total Venta</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-item">
                <div class="stat-number"><?= date('d/m/Y', strtotime($venta['Fecha'])) ?></div>
                <div class="stat-label">Fecha</div>
            </div>
        </div>
    </div>
</div>

<!-- Información de cliente y venta -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card detail-card client-card h-100">
            <div class="card-header-modern">
                <i class="bi bi-person-circle me-2"></i>Información del Cliente
            </div>
            <div class="card-body-modern">
                <div class="info-item">
                    <div class="info-icon">
                        <i class="bi bi-person"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Nombre del Cliente</div>
                        <div class="info-value"><?= htmlspecialchars($venta['cliente']) ?></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="bi bi-telephone"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Teléfono</div>
                        <div class="info-value"><?= htmlspecialchars($venta['Telefono'] ?: 'No especificado') ?></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Dirección</div>
                        <div class="info-value"><?= htmlspecialchars($venta['Direccion'] ?: 'No especificado') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card detail-card sale-card h-100">
            <div class="card-header-modern" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);">
                <i class="bi bi-receipt-cutoff me-2"></i>Información de la Venta
            </div>
            <div class="card-body-modern">
                <div class="info-item">
                    <div class="info-icon" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);">
                        <i class="bi bi-calendar"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Fecha y Hora</div>
                        <div class="info-value"><?= date('d/m/Y H:i', strtotime($venta['Fecha'])) ?></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);">
                        <i class="bi bi-shop"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Sucursal</div>
                        <div class="info-value"><?= htmlspecialchars($venta['Sucursal'] ?? 'No especificado') ?></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);">
                        <i class="bi bi-credit-card"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Forma de Pago</div>
                        <div class="info-value"><?= htmlspecialchars($venta['FormaPago'] ?? 'No especificado') ?></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Total de la Venta</div>
                        <div class="info-value">
                            <span class="total-display">$<?= number_format($venta['Total'], 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de productos -->
<div class="card products-card">
    <div class="card-header-modern">
        <i class="bi bi-box-seam me-2"></i>Productos Vendidos
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table modern-table mb-0">
                <thead class="table-header">
                    <tr>
                        <th>Producto</th>
                        <th class="text-center">Precio Unitario</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles_array as $detalle): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-2" style="width: 35px; height: 35px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">
                                    <?= strtoupper(substr($detalle['producto'], 0, 1)) ?>
                                </div>
                                <span class="product-name"><?= htmlspecialchars($detalle['producto']) ?></span>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="price-badge">$<?= number_format($detalle['Preciounidad'], 2) ?></span>
                        </td>
                        <td class="text-center">
                            <span class="quantity-badge"><?= number_format($detalle['Cantidad'], 2) ?></span>
                        </td>
                        <td class="text-end">
                            <strong style="color: #28a745; font-size: 1.1rem;">$<?= number_format($detalle['Totalin'], 2) ?></strong>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);">
                    <tr>
                        <td colspan="3" class="text-end" style="padding: 1.5rem; font-weight: 600; font-size: 1.2rem;">
                            Total de la Venta:
                        </td>
                        <td class="text-end" style="padding: 1.5rem;">
                            <span class="total-display" style="font-size: 1.3rem;">$<?= number_format($venta['Total'], 2) ?></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Información adicional de impuestos -->
<?php if ($venta['Iva'] > 0 || $venta['Iva5'] > 0 || $venta['Exenta'] > 0): ?>
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card" style="border: none; border-radius: 15px; background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);">
            <div class="card-body">
                <h5 class="mb-3"><i class="bi bi-calculator me-2"></i>Desglose de Impuestos</h5>
                <div class="row text-center">
                    <?php if ($venta['Exenta'] > 0): ?>
                    <div class="col-md-4">
                        <h6 class="text-muted">Exentas</h6>
                        <h4 class="text-success">$<?= number_format($venta['Exenta'], 2) ?></h4>
                    </div>
                    <?php endif; ?>
                    <?php if ($venta['Iva5'] > 0): ?>
                    <div class="col-md-4">
                        <h6 class="text-muted">IVA 5%</h6>
                        <h4 class="text-success">$<?= number_format($venta['Iva5'], 2) ?></h4>
                    </div>
                    <?php endif; ?>
                    <?php if ($venta['Iva'] > 0): ?>
                    <div class="col-md-4">
                        <h6 class="text-muted">IVA 10%</h6>
                        <h4 class="text-success">$<?= number_format($venta['Iva'], 2) ?></h4>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Botón de regreso con estilo -->
<div class="text-center mt-4 mb-4">
    <a href="historial.php" class="btn btn-back btn-lg">
        <i class="bi bi-arrow-left me-2"></i>Volver al Historial de Ventas
    </a>
</div>

<script>
// Animaciones de entrada
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.detail-card, .products-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 150);
    });
    
    // Efecto de conteo para los números
    const statNumbers = document.querySelectorAll('.stat-number');
    statNumbers.forEach(stat => {
        const finalValue = stat.textContent.replace(/[^0-9.]/g, '');
        if (!isNaN(finalValue) && finalValue !== '') {
            let currentValue = 0;
            const increment = Math.ceil(parseFloat(finalValue) / 20);
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= parseFloat(finalValue)) {
                    stat.textContent = stat.textContent; // Restore original
                    clearInterval(timer);
                }
            }, 50);
        }
    });
});
</script>

<?php include('../includes/footer.php'); ?>

