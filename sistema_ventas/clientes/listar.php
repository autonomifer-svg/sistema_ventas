<?php
include('../includes/header.php');
require_once('../includes/conexion.php');

// Manejar mensajes de éxito/error
$mensaje = '';
$tipo_mensaje = '';

if (isset($_GET['success'])) {
    $mensaje = $_GET['success'];
    $tipo_mensaje = 'success';
}

if (isset($_GET['error'])) {
    $mensaje = $_GET['error'];
    $tipo_mensaje = 'danger';
}

// Paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina - 1) * $por_pagina;

// Búsqueda
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';
$where_clause = '';
$params = [];
$types = '';

if (!empty($buscar)) {
    $where_clause = "WHERE nombre LIKE ? OR telefono LIKE ? OR direccion LIKE ?";
    $buscar_param = "%$buscar%";
    $params = [$buscar_param, $buscar_param, $buscar_param];
    $types = 'sss';
}

// Contar total de clientes
$sql_count = "SELECT COUNT(*) as total FROM clientes $where_clause";
$stmt_count = $conexion->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_clientes = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_clientes / $por_pagina);

// Contar clientes activos (opcional - puedes ajustar según tu lógica)
$sql_activos = "SELECT COUNT(*) as activos FROM clientes WHERE 1=1 $where_clause";
$stmt_activos = $conexion->prepare($sql_activos);
if (!empty($params)) {
    $stmt_activos->bind_param($types, ...$params);
}
$stmt_activos->execute();
$clientes_activos = $stmt_activos->get_result()->fetch_assoc()['activos'];

// Obtener clientes
$sql = "SELECT id, nombre, telefono, direccion, fecha_registro 
        FROM clientes 
        $where_clause 
        ORDER BY nombre ASC 
        LIMIT ?, ?";

$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $params[] = $inicio;
    $params[] = $por_pagina;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $inicio, $por_pagina);
}

$stmt->execute();
$resultado = $stmt->get_result();
?>

<style>
.gradient-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    margin-bottom: 2rem;
}

.stats-card {
    border: none;
    border-radius: 15px;
    background: linear-gradient(135deg, #f8f9ff 0%, #e8ecff 100%);
    border-left: 4px solid #667eea;
    transition: transform 0.2s ease;
}

.stats-card:hover {
    transform: translateY(-2px);
}

.stats-number {
    font-size: 2rem;
    font-weight: bold;
    color: #667eea;
    margin: 0;
}

.stats-label {
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    font-weight: 500;
    margin: 0;
}

.search-card {
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    background-color: #f8f9ff;
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

.btn-edit {
    background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%);
    color: white;
}

.btn-edit:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(255, 216, 155, 0.3);
}

.btn-delete {
    background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
    color: #721c24;
}

.btn-delete:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(255, 154, 158, 0.3);
}

.pagination .page-link {
    border: none;
    color: #667eea;
    border-radius: 8px;
    margin: 0 2px;
    transition: all 0.2s ease;
}

.pagination .page-link:hover {
    background-color: #667eea;
    color: white;
    transform: translateY(-1px);
}

.pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.empty-state {
    padding: 3rem;
    text-align: center;
    background: linear-gradient(135deg, #f8f9ff 0%, #e8ecff 100%);
    border-radius: 15px;
}

.empty-state-icon {
    font-size: 4rem;
    color: #667eea;
    margin-bottom: 1rem;
}
</style>

<!-- Header con gradiente -->
<div class="gradient-header text-white p-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-2"><i class="bi bi-people-fill me-2"></i>Lista de Clientes</h2>
            <p class="mb-0 opacity-75">Gestiona tu base de datos de clientes</p>
        </div>
        <a href="crear.php" class="btn btn-light btn-lg">
            <i class="bi bi-plus-circle me-2"></i>Nuevo Cliente
        </a>
    </div>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert" style="border-radius: 10px; border: none;">
        <?= htmlspecialchars($mensaje) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <h2 class="stats-number"><?= $total_clientes ?></h2>
                <p class="stats-label">Total Clientes</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <h2 class="stats-number"><?= $clientes_activos ?></h2>
                <p class="stats-label">Clientes Activos</p>
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

<!-- Buscador -->
<div class="card search-card mb-4">
    <div class="card-header" style="background: linear-gradient(135deg, #f8f9ff 0%, #e8ecff 100%); border: none;">
        <h5 class="mb-0"><i class="bi bi-search me-2"></i>Inventario de Clientes</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text" style="background: #f8f9ff; border: 1px solid #e0e6ff;">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" class="form-control" name="buscar" 
                           placeholder="Buscar clientes por nombre, teléfono o dirección..." 
                           value="<?= htmlspecialchars($buscar) ?>"
                           style="border: 1px solid #e0e6ff; border-left: none;">
                </div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-action me-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <i class="bi bi-search me-1"></i> Buscar
                </button>
                <?php if (!empty($buscar)): ?>
                    <a href="listar.php" class="btn btn-outline-secondary btn-action">
                        <i class="bi bi-x-circle me-1"></i> Limpiar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card main-table-card">
    <?php if ($total_clientes > 0): ?>
        <div class="card-body p-0">
            <div class="p-3 bg-light">
                <small class="text-muted">
                    Mostrando <?= min($inicio + 1, $total_clientes) ?> - <?= min($inicio + $por_pagina, $total_clientes) ?> 
                    de <?= $total_clientes ?> clientes
                </small>
            </div>
            
            <div class="table-responsive">
                <table class="table modern-table mb-0">
                    <thead class="table-header">
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Teléfono</th>
                            <th>Dirección</th>
                            <th>Fecha</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($cliente = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= $cliente['id'] ?></strong></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2" style="width: 35px; height: 35px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">
                                        <?= strtoupper(substr($cliente['nombre'], 0, 1)) ?>
                                    </div>
                                    <span><?= htmlspecialchars($cliente['nombre']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark"><?= htmlspecialchars($cliente['telefono'] ?? 'No especificado') ?></span>
                            </td>
                            <td><?= htmlspecialchars($cliente['direccion'] ?? 'No especificado') ?></td>
                            <td><?= date('d/m/Y', strtotime($cliente['fecha_registro'])) ?></td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="editar.php?id=<?= $cliente['id'] ?>" 
                                       class="btn btn-sm btn-edit" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="eliminar.php?id=<?= $cliente['id'] ?>" 
                                       class="btn btn-sm btn-delete" title="Eliminar"
                                       onclick="return confirm('¿Estás seguro de eliminar este cliente?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <div class="p-3 bg-light border-top">
                    <nav aria-label="Paginación de clientes">
                        <ul class="pagination justify-content-center mb-0">
                            <!-- Página anterior -->
                            <?php if ($pagina > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?= $pagina - 1 ?><?= !empty($buscar) ? '&buscar=' . urlencode($buscar) : '' ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <!-- Números de página -->
                            <?php 
                            $inicio_pag = max(1, $pagina - 2);
                            $fin_pag = min($total_paginas, $pagina + 2);
                            
                            for ($i = $inicio_pag; $i <= $fin_pag; $i++): ?>
                                <li class="page-item <?= $pagina == $i ? 'active' : '' ?>">
                                    <a class="page-link" href="?pagina=<?= $i ?><?= !empty($buscar) ? '&buscar=' . urlencode($buscar) : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Página siguiente -->
                            <?php if ($pagina < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?= $pagina + 1 ?><?= !empty($buscar) ? '&buscar=' . urlencode($buscar) : '' ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-people"></i>
                </div>
                <h4 class="text-muted">No hay clientes registrados</h4>
                <p class="text-muted mb-4">
                    <?php if (!empty($buscar)): ?>
                        No se encontraron clientes que coincidan con tu búsqueda.
                    <?php else: ?>
                        Comienza agregando tu primer cliente.
                    <?php endif; ?>
                </p>
                <?php if (!empty($buscar)): ?>
                    <a href="listar.php" class="btn btn-outline-primary btn-action">Ver todos los clientes</a>
                <?php else: ?>
                    <a href="crear.php" class="btn btn-action" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <i class="bi bi-plus-circle me-2"></i> Crear primer cliente
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>