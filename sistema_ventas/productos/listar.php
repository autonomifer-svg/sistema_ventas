<?php
require_once(__DIR__ . '/../includes/config.php');
requireAuth();

include(__DIR__ . '/../includes/header.php');

// Mensajes de éxito/error
if (isset($_GET['success'])) {
    echo "<div class='alert alert-success alert-dismissible fade show modern-alert'>
            <i class='bi bi-check-circle-fill'></i> " . htmlspecialchars($_GET['success']) . "
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
}
if (isset($_GET['error'])) {
    echo "<div class='alert alert-danger alert-dismissible fade show modern-alert'>
            <i class='bi bi-exclamation-triangle-fill'></i> " . htmlspecialchars($_GET['error']) . "
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
}
?>

<style>
.products-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 8px 30px rgba(102, 126, 234, 0.3);
}

.modern-card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    overflow: hidden;
}

.modern-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}

.card-header-modern {
    background: linear-gradient(135deg, #f8faff 0%, #ffffff 100%);
    border-bottom: 2px solid #e2e8f0;
    padding: 25px;
    border-radius: 20px 20px 0 0 !important;
}

.stats-container {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.stat-box {
    background: white;
    padding: 20px;
    border-radius: 15px;
    border-left: 4px solid #667eea;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    flex: 1;
    min-width: 200px;
}

.stat-number {
    font-size: 2rem;
    font-weight: 800;
    color: #667eea;
    margin-bottom: 5px;
}

.stat-label {
    color: #718096;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.modern-table {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.modern-table thead th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    padding: 20px 15px;
    border: none;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
}

.modern-table tbody td {
    padding: 20px 15px;
    border: none;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.modern-table tbody tr {
    transition: all 0.3s ease;
}

.modern-table tbody tr:hover {
    background: linear-gradient(135deg, #f8faff 0%, #ffffff 100%);
    transform: scale(1.01);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
}

.product-id {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 8px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
}

.product-name {
    font-weight: 700;
    color: #1a202c;
    font-size: 1.1rem;
}

.price-tag {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
    padding: 8px 15px;
    border-radius: 25px;
    font-weight: 700;
    display: inline-block;
    font-size: 1rem;
}

.stock-badge-low {
    background: linear-gradient(135deg, #fc8181 0%, #f56565 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
    animation: pulse 2s infinite;
}

.stock-badge-medium {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
}

.stock-badge-high {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
}

.status-available {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
    padding: 6px 15px;
    border-radius: 20px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.status-unavailable {
    background: linear-gradient(135deg, #fc8181 0%, #f56565 100%);
    color: white;
    padding: 6px 15px;
    border-radius: 20px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.btn-edit {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 10px;
    transition: all 0.3s ease;
    font-weight: 600;
}

.btn-edit:hover {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(251, 191, 36, 0.4);
    color: white;
}

.btn-delete {
    background: linear-gradient(135deg, #fc8181 0%, #f56565 100%);
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 10px;
    transition: all 0.3s ease;
    font-weight: 600;
}

.btn-delete:hover {
    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(252, 129, 129, 0.4);
    color: white;
}

.btn-primary-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary-modern:hover {
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    color: white;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: linear-gradient(135deg, #f8faff 0%, #ffffff 100%);
    border-radius: 20px;
    border: 2px dashed #cbd5e0;
}

.empty-icon {
    font-size: 4rem;
    color: #cbd5e0;
    margin-bottom: 20px;
}

.modern-alert {
    border: none;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.search-container {
    background: white;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.search-input {
    border-radius: 25px;
    border: 2px solid #e2e8f0;
    padding: 12px 20px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

@media (max-width: 768px) {
    .products-header {
        padding: 20px;
        text-align: center;
    }
    
    .stats-container {
        flex-direction: column;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .modern-table {
        font-size: 0.9rem;
    }
    
    .modern-table thead th,
    .modern-table tbody td {
        padding: 15px 10px;
    }
}
</style>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="products-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2 class="mb-2"><i class="bi bi-box-seam"></i> Lista de Productos</h2>
                <p class="mb-0 opacity-90">Gestiona tu inventario de productos de limpieza</p>
            </div>
            <a href="<?= $base_url ?>productos/crear.php" class="btn btn-light btn-lg">
                <i class="bi bi-plus-circle"></i> Nuevo Producto
            </a>
        </div>
    </div>

    <div class="modern-card">
        <div class="card-header-modern">
            <h5 class="mb-3"><i class="bi bi-clipboard-data"></i> Inventario de Productos</h5>
            
            <!-- Search Bar -->
            <div class="search-container">
                <div class="position-relative">
                    <input type="text" id="searchProducts" class="form-control search-input" 
                           placeholder="🔍 Buscar productos por nombre, descripción o ID...">
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <?php
            try {
                // Conectar a la base de datos REAL
                $conexion = conectarDB();
                
                // Consulta REAL a la base de datos
                $sql = "SELECT id, nombre, descripcion, precio, stock, fecha_creacion 
                        FROM productos 
                        ORDER BY nombre ASC";
                
                $resultado = $conexion->query($sql);
                
                if (!$resultado) {
                    throw new Exception("Error en la consulta: " . $conexion->error);
                }
                
                // Stats
                $total_productos = $resultado->num_rows;
                $productos_bajo_stock = 0;
                $valor_inventario = 0;
                
                // Calcular estadísticas
                $productos_array = [];
                while ($row = $resultado->fetch_assoc()) {
                    $productos_array[] = $row;
                    if ($row['stock'] <= 10) $productos_bajo_stock++;
                    $valor_inventario += $row['precio'] * $row['stock'];
                }
            ?>
            
            <!-- Stats Section -->
            <div class="stats-container">
                <div class="stat-box">
                    <div class="stat-number"><?= $total_productos ?></div>
                    <div class="stat-label">Total Productos</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $productos_bajo_stock ?></div>
                    <div class="stat-label">Bajo Stock</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">$<?= number_format($valor_inventario, 0) ?></div>
                    <div class="stat-label">Valor Inventario</div>
                </div>
            </div>
            
            <?php if ($total_productos > 0): ?>
                <div class="table-responsive">
                    <table class="table modern-table" id="productsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>Descripción</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos_array as $producto): ?>
                                <tr class="product-row" data-search="<?= strtolower(htmlspecialchars($producto['nombre'] . ' ' . $producto['descripcion'] . ' ' . $producto['id'])) ?>">
                                    <td>
                                        <span class="product-id"><?= $producto['id'] ?></span>
                                    </td>
                                    <td>
                                        <div class="product-name"><?= htmlspecialchars($producto['nombre']) ?></div>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <?php 
                                            $desc = htmlspecialchars($producto['descripcion'] ?? '');
                                            echo strlen($desc) > 40 ? substr($desc, 0, 40) . '...' : ($desc ?: 'Sin descripción');
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="price-tag">
                                            $<?= number_format($producto['precio'], 2) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $stock = $producto['stock'];
                                        if ($stock <= 10) {
                                            echo "<span class='stock-badge-low'><i class='bi bi-exclamation-triangle'></i> $stock</span>";
                                        } elseif ($stock <= 30) {
                                            echo "<span class='stock-badge-medium'><i class='bi bi-exclamation-circle'></i> $stock</span>";
                                        } else {
                                            echo "<span class='stock-badge-high'><i class='bi bi-check-circle'></i> $stock</span>";
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($stock > 0): ?>
                                            <span class="status-available">
                                                <i class="bi bi-check-circle-fill"></i> Disponible
                                            </span>
                                        <?php else: ?>
                                            <span class="status-unavailable">
                                                <i class="bi bi-x-circle-fill"></i> Sin Stock
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php 
                                            if ($producto['fecha_creacion']) {
                                                echo date('d/m/Y', strtotime($producto['fecha_creacion']));
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?= $base_url ?>productos/editar.php?id=<?= $producto['id'] ?>" 
                                               class="btn btn-edit" title="Editar">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <a href="<?= $base_url ?>productos/eliminar.php?id=<?= $producto['id'] ?>" 
                                               class="btn btn-delete" title="Eliminar"
                                               onclick="return confirm('¿Está seguro de eliminar el producto: <?= htmlspecialchars($producto['nombre']) ?>?')">
                                                <i class="bi bi-trash3"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- No results message -->
                <div id="noResults" class="empty-state" style="display: none;">
                    <div class="empty-icon">
                        <i class="bi bi-search"></i>
                    </div>
                    <h4>No se encontraron productos</h4>
                    <p class="text-muted">Intenta con otro término de búsqueda</p>
                </div>
                
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h4>No hay productos registrados</h4>
                    <p class="text-muted mb-4">Comienza creando tu primer producto para mostrar el inventario.</p>
                    <a href="<?= $base_url ?>productos/crear.php" class="btn btn-primary-modern">
                        <i class="bi bi-plus-circle"></i> Crear Primer Producto
                    </a>
                </div>
            <?php endif; ?>
            
            <?php
                $conexion->close();
                
            } catch (Exception $e) {
                // Error en la consulta
                echo "<div class='alert alert-danger modern-alert'>
                        <h5><i class='bi bi-exclamation-triangle-fill'></i> Error al cargar productos</h5>
                        <p>No se pudieron cargar los productos desde la base de datos.</p>
                        <details>
                            <summary>Detalles técnicos</summary>
                            <code>" . htmlspecialchars($e->getMessage()) . "</code>
                        </details>
                      </div>";
                
                // Log del error
                error_log("Error en productos/listar.php: " . $e->getMessage());
            }
            ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchProducts');
    const productRows = document.querySelectorAll('.product-row');
    const noResults = document.getElementById('noResults');
    const productsTable = document.getElementById('productsTable');

    // Función de búsqueda
    function searchProducts() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        productRows.forEach(row => {
            const searchData = row.dataset.search;
            
            if (searchData.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
                
                // Highlight search terms
                if (searchTerm) {
                    highlightSearchTerm(row, searchTerm);
                } else {
                    removeHighlight(row);
                }
            } else {
                row.style.display = 'none';
            }
        });

        // Toggle no results message
        if (visibleCount === 0 && searchTerm) {
            noResults.style.display = 'block';
            productsTable.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            productsTable.style.display = 'table';
        }
    }

    function highlightSearchTerm(row, term) {
        const nameCell = row.querySelector('.product-name');
        const originalText = nameCell.textContent;
        const regex = new RegExp(`(${term})`, 'gi');
        nameCell.innerHTML = originalText.replace(regex, '<mark style="background: #fef3cd; border-radius: 3px; padding: 1px 3px;">$1</mark>');
    }

    function removeHighlight(row) {
        const nameCell = row.querySelector('.product-name');
        nameCell.innerHTML = nameCell.textContent;
    }

    // Event listener for search
    searchInput.addEventListener('input', searchProducts);

    // Animation on page load
    setTimeout(() => {
        productRows.forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(20px)';
            row.style.transition = 'all 0.3s ease';
            
            setTimeout(() => {
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, index * 50);
        });
    }, 100);
});
</script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>