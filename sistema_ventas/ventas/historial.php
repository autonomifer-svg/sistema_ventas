<?php
require_once(__DIR__ . '/../includes/config.php');
requireAuth();
require_once(__DIR__ . '/../includes/conexion.php');

include(__DIR__ . '/../includes/header.php');

// Obtener parámetros de filtro
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;

// Construir consulta con filtros
$sql = "SELECT s.IdSalida, c.Nombre AS cliente, s.Fecha, s.Total 
        FROM salida s
        INNER JOIN clientes c ON s.NroCliente = c.NroCliente
        WHERE s.Anulado = 0";

$params = [];
$types = '';

if (!empty($fecha_inicio)) {
    $sql .= " AND DATE(s.Fecha) >= ?";
    $params[] = $fecha_inicio;
    $types .= 's';
}

if (!empty($fecha_fin)) {
    $sql .= " AND DATE(s.Fecha) <= ?";
    $params[] = $fecha_fin;
    $types .= 's';
}

if ($cliente_id > 0) {
    $sql .= " AND s.NroCliente = ?";
    $params[] = $cliente_id;
    $types .= 'i';
}

$sql .= " ORDER BY s.Fecha DESC";

// Preparar y ejecutar consulta
$stmt = $conexion->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$ventas = $stmt->get_result();

// Calcular total general
$total_general = 0;
$contador = 0;

// Obtener clientes para el filtro
$sql_clientes = "SELECT NroCliente, Nombre FROM clientes WHERE Inactivo = 0 ORDER BY Nombre";
$result_clientes = $conexion->query($sql_clientes);
$todos_clientes = [];
if ($result_clientes && $result_clientes->num_rows > 0) {
    while ($row = $result_clientes->fetch_assoc()) {
        $todos_clientes[] = $row;
    }
}

// Calcular estadísticas adicionales
$ventas_hoy = 0;
$ventas_mes = 0;
$hoy = date('Y-m-d');
$primer_dia_mes = date('Y-m-01');

// Obtener ventas de hoy
$sql_hoy = "SELECT COUNT(*) as ventas_hoy FROM salida WHERE DATE(Fecha) = ? AND Anulado = 0";
$stmt_hoy = $conexion->prepare($sql_hoy);
$stmt_hoy->bind_param('s', $hoy);
$stmt_hoy->execute();
$result_hoy = $stmt_hoy->get_result()->fetch_assoc();
$ventas_hoy = $result_hoy['ventas_hoy'];

// Obtener ventas del mes
$sql_mes = "SELECT COUNT(*) as ventas_mes FROM salida WHERE DATE(Fecha) >= ? AND Anulado = 0";
$stmt_mes = $conexion->prepare($sql_mes);
$stmt_mes->bind_param('s', $primer_dia_mes);
$stmt_mes->execute();
$result_mes = $stmt_mes->get_result()->fetch_assoc();
$ventas_mes = $result_mes['ventas_mes'];
?>

<style>
.gradient-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-radius: 15px;
    margin-bottom: 2rem;
}

.stats-card {
    border: none;
    border-radius: 15px;
    background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);
    border-left: 4px solid #28a745;
    transition: transform 0.2s ease;
}

.stats-card:hover {
    transform: translateY(-2px);
}

.stats-number {
    font-size: 2rem;
    font-weight: bold;
    color: #28a745;
    margin: 0;
}

.stats-label {
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    font-weight: 500;
    margin: 0;
}

.filter-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.main-table-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    font-weight: 600;
    border: none;
}

.table-header th {
    border: none;
    padding: 1rem;
    font-size: 0.85rem;
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
    padding: 1rem;
    vertical-align: middle;
}

.btn-action {
    border-radius: 8px;
    border: none;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-view {
    background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
    color: white;
}

.btn-view:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(23, 162, 184, 0.3);
    color: white;
}

.btn-report {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: white;
}

.btn-report:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(255, 193, 7, 0.3);
    color: white;
}

.summary-card {
    background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);
    border: 2px solid #28a745;
    border-radius: 15px;
    padding: 1.5rem;
}

.amount-display {
    font-size: 2rem;
    font-weight: bold;
    color: #28a745;
}

.empty-state {
    padding: 3rem;
    text-align: center;
    background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);
    border-radius: 15px;
}

.empty-state-icon {
    font-size: 4rem;
    color: #28a745;
    margin-bottom: 1rem;
}

.filter-input {
    border: 2px solid #e8f5e8;
    border-radius: 10px;
    transition: border-color 0.2s ease;
}

.filter-input:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}
</style>

<!-- Header con gradiente -->
<div class="gradient-header text-white p-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-2"><i class="bi bi-clock-history me-2"></i>Historial de Ventas</h2>
            <p class="mb-0 opacity-75">Revisa y analiza todas las transacciones realizadas</p>
        </div>
        <a href="<?= $base_url ?>ventas/nueva.php" class="btn btn-light btn-lg">
            <i class="bi bi-plus-circle me-2"></i>Nueva Venta
        </a>
    </div>
</div>

<?php if (isset($_SESSION['mensaje_exito'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill"></i> <?= $_SESSION['mensaje_exito'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['mensaje_exito']); ?>
<?php endif; ?>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <h2 class="stats-number"><?= $ventas_hoy ?></h2>
                <p class="stats-label">Ventas Hoy</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <h2 class="stats-number"><?= $ventas_mes ?></h2>
                <p class="stats-label">Ventas Este Mes</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <h2 class="stats-number"><?= date('Y') ?></h2>
                <p class="stats-label">Año Actual</p>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card filter-card mb-4">
    <div class="card-header" style="background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%); border: none;">
        <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtros de Búsqueda</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-bold">Fecha Inicio</label>
                <input type="date" class="form-control filter-input" name="fecha_inicio" value="<?= $fecha_inicio ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Fecha Fin</label>
                <input type="date" class="form-control filter-input" name="fecha_fin" value="<?= $fecha_fin ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Cliente</label>
                <select class="form-select filter-input" name="cliente_id">
                    <option value="0">Todos los clientes</option>
                    <?php foreach ($todos_clientes as $cliente): ?>
                    <?php $selected = ($cliente['NroCliente'] == $cliente_id) ? 'selected' : ''; ?>
                    <option value="<?= $cliente['NroCliente'] ?>" <?= $selected ?>>
                        <?= htmlspecialchars($cliente['Nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-action w-100" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                    <i class="bi bi-search me-1"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card main-table-card">
    <div class="card-body p-0">
        <?php if ($ventas->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table modern-table mb-0">
                    <thead class="table-header">
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($venta = $ventas->fetch_assoc()): 
                            $total_general += $venta['Total'];
                            $contador++;
                        ?>
                        <tr>
                            <td><strong>#<?= $venta['IdSalida'] ?></strong></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2" style="width: 35px; height: 35px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">
                                        <?= strtoupper(substr($venta['cliente'], 0, 1)) ?>
                                    </div>
                                    <span><?= htmlspecialchars($venta['cliente']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div><?= date('d/m/Y', strtotime($venta['Fecha'])) ?></div>
                                    <small class="text-muted"><?= date('H:i', strtotime($venta['Fecha'])) ?></small>
                                </div>
                            </td>
                            <td class="text-end">
                                <span class="badge bg-success fs-6">$<?= number_format($venta['Total'], 2) ?></span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="<?= $base_url ?>ventas/detalle.php?id=<?= $venta['IdSalida'] ?>" 
                                       class="btn btn-sm btn-view" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="p-3 bg-light border-top">
                <div class="summary-card">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-2"><i class="bi bi-graph-up me-2"></i>Resumen del período</h5>
                            <p class="mb-1">Ventas mostradas: <strong><?= $contador ?></strong></p>
                            <p class="mb-0 text-muted">Promedio por venta: <strong>$<?= $contador > 0 ? number_format($total_general / $contador, 2) : '0.00' ?></strong></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p class="mb-1 text-muted">Total Acumulado</p>
                            <h2 class="amount-display">$<?= number_format($total_general, 2) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card-body">
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <h4 class="text-muted">No se encontraron ventas</h4>
                    <p class="text-muted mb-4">
                        <?php if (!empty($fecha_inicio) || !empty($fecha_fin) || $cliente_id > 0): ?>
                            No hay ventas que coincidan con los filtros seleccionados.
                        <?php else: ?>
                            Aún no se han registrado ventas en el sistema.
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($fecha_inicio) || !empty($fecha_fin) || $cliente_id > 0): ?>
                        <a href="historial.php" class="btn btn-action me-2" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                            Ver todas las ventas
                        </a>
                    <?php endif; ?>
                    <a href="<?= $base_url ?>ventas/nueva.php" class="btn btn-action" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                        <i class="bi bi-plus-circle me-2"></i> Crear primera venta
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include(__DIR__ . '/../includes/footer.php'); ?>
