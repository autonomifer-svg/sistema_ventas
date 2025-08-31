<?php
require_once(__DIR__ . '/../includes/config.php');
requireAuth();
require_once(__DIR__ . '/../includes/conexion.php'); // Conexión a DB

// Obtener productos reales de la base de datos
$sql_productos = "SELECT id, nombre, precio, stock FROM productos";
$result_productos = $conexion->query($sql_productos);
$productos = [];
if ($result_productos && $result_productos->num_rows > 0) {
    while ($row = $result_productos->fetch_assoc()) {
        $productos[] = $row;
    }
}

// Obtener clientes reales de la base de datos
$sql_clientes = "SELECT id, nombre FROM clientes";
$result_clientes = $conexion->query($sql_clientes);
$clientes = [];
if ($result_clientes && $result_clientes->num_rows > 0) {
    while ($row = $result_clientes->fetch_assoc()) {
        $clientes[] = $row;
    }
}

// Inicializar carrito y cliente seleccionado
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}
if (!isset($_SESSION['cliente_seleccionado'])) {
    $_SESSION['cliente_seleccionado'] = null;
}

// Procesar selección de cliente
if (isset($_POST['seleccionar_cliente'])) {
    $_SESSION['cliente_seleccionado'] = intval($_POST['cliente_id']);
}

// Procesar cambiar cliente (limpiar carrito)
if (isset($_POST['cambiar_cliente'])) {
    $_SESSION['cliente_seleccionado'] = null;
    $_SESSION['carrito'] = [];
}

// Procesar agregar producto al carrito
if (isset($_POST['agregar'])) {
    $producto_id = intval($_POST['producto_id']);
    $cantidad = intval($_POST['cantidad']);
    
    // Buscar el producto seleccionado
    $producto_seleccionado = null;
    foreach ($productos as $producto) {
        if ($producto['id'] == $producto_id) {
            $producto_seleccionado = $producto;
            break;
        }
    }
    
    if ($producto_seleccionado && $cantidad <= $producto_seleccionado['stock']) {
        // Verificar si el producto ya está en el carrito
        $encontrado = false;
        foreach ($_SESSION['carrito'] as &$item) {
            if ($item['id'] == $producto_id) {
                $nueva_cantidad = $item['cantidad'] + $cantidad;
                if ($nueva_cantidad <= $producto_seleccionado['stock']) {
                    $item['cantidad'] = $nueva_cantidad;
                    $encontrado = true;
                }
                break;
            }
        }
        
        // Si no está en el carrito, agregarlo
        if (!$encontrado) {
            $_SESSION['carrito'][] = [
                'id' => $producto_seleccionado['id'],
                'nombre' => $producto_seleccionado['nombre'],
                'precio' => $producto_seleccionado['precio'],
                'cantidad' => $cantidad,
                'stock' => $producto_seleccionado['stock']
            ];
        }
    } else {
        $error = "Stock insuficiente o producto no válido";
    }
}

// Procesar eliminar producto del carrito
if (isset($_GET['eliminar'])) {
    $index = intval($_GET['eliminar']);
    if (isset($_SESSION['carrito'][$index])) {
        unset($_SESSION['carrito'][$index]);
        $_SESSION['carrito'] = array_values($_SESSION['carrito']);
    }
    header("Location: nueva.php");
    exit;
}

// Procesar actualizar cantidad
if (isset($_POST['actualizar_cantidad'])) {
    $index = intval($_POST['item_index']);
    $nueva_cantidad = intval($_POST['nueva_cantidad']);
    
    if (isset($_SESSION['carrito'][$index]) && $nueva_cantidad > 0 && $nueva_cantidad <= $_SESSION['carrito'][$index]['stock']) {
        $_SESSION['carrito'][$index]['cantidad'] = $nueva_cantidad;
    }
}

// Procesar finalizar venta
if (isset($_POST['finalizar_venta'])) {
    if (empty($_SESSION['carrito']) || !$_SESSION['cliente_seleccionado']) {
        $error = "Debe seleccionar un cliente y agregar productos al carrito";
    } else {
        // Calcular total de la venta
        $total_venta = 0;
        foreach ($_SESSION['carrito'] as $item) {
            $subtotal = $item['precio'] * $item['cantidad'];
            $total_venta += $subtotal;
        }
        
        // Insertar venta en la base de datos
        $conexion->begin_transaction();
        
        try {
            // Insertar en tabla ventas
            $sql_venta = "INSERT INTO ventas (cliente_id, total, fecha) VALUES (?, ?, NOW())";
            $stmt_venta = $conexion->prepare($sql_venta);
            $stmt_venta->bind_param("id", $_SESSION['cliente_seleccionado'], $total_venta);
            $stmt_venta->execute();
            $venta_id = $conexion->insert_id;
            
            // Insertar detalles de la venta
            $sql_detalle = "INSERT INTO ventas_detalle (venta_id, producto_id, cantidad, precio_unitario) 
                            VALUES (?, ?, ?, ?)";
            $stmt_detalle = $conexion->prepare($sql_detalle);
            
            foreach ($_SESSION['carrito'] as $item) {
                $stmt_detalle->bind_param("iiid", $venta_id, $item['id'], $item['cantidad'], $item['precio']);
                $stmt_detalle->execute();
            }
            
            // Confirmar transacción
            $conexion->commit();
            
            // Limpiar carrito y cliente seleccionado
            $_SESSION['carrito'] = [];
            $_SESSION['cliente_seleccionado'] = null;
            $_SESSION['mensaje_exito'] = "Venta registrada exitosamente! ID: $venta_id";
            header("Location: historial.php");
            exit;
            
        } catch (Exception $e) {
            $conexion->rollback();
            $error = "Error al registrar la venta: " . $e->getMessage();
        }
    }
}

// Obtener información del cliente seleccionado
$cliente_info = null;
if ($_SESSION['cliente_seleccionado']) {
    foreach ($clientes as $cliente) {
        if ($cliente['id'] == $_SESSION['cliente_seleccionado']) {
            $cliente_info = $cliente;
            break;
        }
    }
}

include(__DIR__ . '/../includes/header.php');
?>

<style>
.modern-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.modern-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.gradient-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0 !important;
}

.client-info {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
}

.product-card {
    border: 2px solid transparent;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.product-card:hover {
    border-color: #667eea;
    background-color: #f8f9ff;
}

.btn-modern {
    border-radius: 25px;
    padding: 10px 25px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
}

.btn-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-gradient:hover {
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
    transform: translateY(-1px);
    color: white;
}

.btn-success-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-success-gradient:hover {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
}

.quantity-input {
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    transition: border-color 0.3s ease;
}

.quantity-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.cart-item {
    background: white;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border-left: 4px solid #667eea;
}

.total-section {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    border-radius: 15px;
    padding: 20px;
    margin-top: 20px;
}

.step-indicator {
    display: flex;
    justify-content: center;
    margin-bottom: 30px;
}

.step {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 10px;
    color: white;
    font-weight: bold;
}

.step.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.step.inactive {
    background: #e2e8f0;
    color: #718096;
}

.step-line {
    width: 50px;
    height: 2px;
    background: #e2e8f0;
    align-self: center;
}

.step-line.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.search-container {
    position: relative;
}

.search-input {
    border-radius: 25px;
    padding: 15px 50px 15px 20px;
    border: 2px solid #e2e8f0;
    transition: all 0.3s ease;
    font-size: 1.1rem;
}

.search-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

.search-icon {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 1.2rem;
}

.cliente-item {
    background: white;
    border: 2px solid #f1f5f9 !important;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.cliente-item:hover {
    border-color: #667eea !important;
    background: #f8faff;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.15);
}

.cliente-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
}

.cliente-nombre {
    color: #1e293b;
    font-weight: 600;
}

.clientes-container {
    border-radius: 10px;
}

.clientes-container::-webkit-scrollbar {
    width: 6px;
}

.clientes-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.clientes-container::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
}

.highlight {
    background-color: #fef3cd;
    font-weight: bold;
    border-radius: 3px;
    padding: 1px 3px;
}
</style>

<div class="container-fluid">
    <!-- Header con indicador de pasos -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-gradient">Nueva Venta</h2>
        <div>
            <a href="<?= $base_url ?>ventas/historial.php" class="btn btn-info btn-modern me-2">
                <i class="bi bi-clock-history"></i> Ver Historial
            </a>
            <a href="<?= $base_url ?>ventas/nueva.php" class="btn btn-outline-secondary btn-modern">
                <i class="bi bi-arrow-clockwise"></i> Reiniciar
            </a>
        </div>
    </div>

    <!-- Indicador de pasos -->
    <div class="step-indicator">
        <div class="step <?= !$_SESSION['cliente_seleccionado'] ? 'active' : 'inactive' ?>">1</div>
        <div class="step-line <?= $_SESSION['cliente_seleccionado'] ? 'active' : '' ?>"></div>
        <div class="step <?= $_SESSION['cliente_seleccionado'] ? 'active' : 'inactive' ?>">2</div>
        <div class="step-line <?= !empty($_SESSION['carrito']) ? 'active' : '' ?>"></div>
        <div class="step <?= !empty($_SESSION['carrito']) ? 'active' : 'inactive' ?>">3</div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?= $_SESSION['mensaje_exito'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje_exito']); ?>
    <?php endif; ?>

    <?php if (!$_SESSION['cliente_seleccionado']): ?>
        <!-- PASO 1: Selección de Cliente -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card modern-card">
                    <div class="card-header gradient-header text-center">
                        <h4><i class="bi bi-person-plus"></i> Paso 1: Seleccionar Cliente</h4>
                        <p class="mb-0">Busca y selecciona el cliente para esta venta</p>
                    </div>
                    <div class="card-body p-4">
                        <!-- Buscador de clientes -->
                        <div class="mb-4">
                            <div class="search-container position-relative">
                                <input type="text" id="searchCliente" class="form-control form-control-lg search-input" 
                                       placeholder="🔍 Buscar cliente por nombre..." autocomplete="off">
                                <div class="search-icon">
                                    <i class="bi bi-search"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Lista de clientes -->
                        <div class="clientes-container" style="max-height: 400px; overflow-y: auto;">
                            <form method="POST" id="clienteForm">
                                <div class="list-group" id="clientesList">
                                    <?php foreach ($clientes as $cliente): ?>
                                        <div class="list-group-item cliente-item border-0 rounded mb-2" 
                                             data-nombre="<?= strtolower(htmlspecialchars($cliente['nombre'])) ?>"
                                             style="cursor: pointer; transition: all 0.3s ease;">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center">
                                                    <div class="cliente-avatar me-3">
                                                        <i class="bi bi-person-circle text-primary" style="font-size: 2.5rem;"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1 cliente-nombre"><?= htmlspecialchars($cliente['nombre']) ?></h6>
                                                        <small class="text-muted">ID: <?= $cliente['id'] ?></small>
                                                    </div>
                                                </div>
                                                <button type="button" 
                                                        class="btn btn-gradient btn-modern select-client-btn"
                                                        data-client-id="<?= $cliente['id'] ?>"
                                                        data-client-name="<?= htmlspecialchars($cliente['nombre']) ?>">
                                                    <i class="bi bi-arrow-right"></i> Seleccionar
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="cliente_id" id="clienteIdInput" value="">
                                <input type="hidden" name="seleccionar_cliente" value="1">
                            </form>
                        </div>

                        <!-- Mensaje cuando no hay resultados -->
                        <div id="noResults" class="text-center py-4" style="display: none;">
                            <i class="bi bi-search" style="font-size: 3rem; color: #cbd5e0;"></i>
                            <h5 class="text-muted mt-3">No se encontraron clientes</h5>
                            <p class="text-muted">Intenta con otro término de búsqueda</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Información del cliente seleccionado -->
        <div class="client-info text-center">
            <h4><i class="bi bi-person-check-fill"></i> Cliente: <?= htmlspecialchars($cliente_info['nombre']) ?></h4>
            <form method="POST" class="d-inline">
                <button type="submit" name="cambiar_cliente" class="btn btn-light btn-modern">
                    <i class="bi bi-arrow-left"></i> Cambiar Cliente
                </button>
            </form>
        </div>

        <div class="row">
            <!-- PASO 2: Agregar Productos -->
            <div class="col-lg-5 mb-4">
                <div class="card modern-card">
                    <div class="card-header gradient-header">
                        <h5><i class="bi bi-bag-plus"></i> Paso 2: Agregar Productos</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Producto</label>
                                <select name="producto_id" class="form-select form-select-lg" required>
                                    <option value="">Seleccionar producto</option>
                                    <?php foreach ($productos as $producto): ?>
                                    <option value="<?= $producto['id'] ?>" 
                                            data-precio="<?= $producto['precio'] ?>"
                                            data-stock="<?= $producto['stock'] ?>">
                                        <?= htmlspecialchars($producto['nombre']) ?> 
                                        - $<?= number_format($producto['precio'], 2) ?> 
                                        (Stock: <?= $producto['stock'] ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Cantidad</label>
                                <input type="number" name="cantidad" class="form-control form-control-lg quantity-input" 
                                       min="1" value="1" required>
                            </div>
                            <button type="submit" name="agregar" class="btn btn-gradient btn-modern w-100">
                                <i class="bi bi-cart-plus"></i> Agregar al Carrito
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- PASO 3: Carrito y Finalizar -->
            <div class="col-lg-7">
                <div class="card modern-card">
                    <div class="card-header gradient-header">
                        <h5><i class="bi bi-cart3"></i> Paso 3: Carrito de Venta</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($_SESSION['carrito'])): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-cart-x" style="font-size: 4rem; color: #cbd5e0;"></i>
                                <h5 class="text-muted mt-3">El carrito está vacío</h5>
                                <p class="text-muted">Agrega productos para continuar</p>
                            </div>
                        <?php else: ?>
                            <?php
                            $total = 0;
                            foreach ($_SESSION['carrito'] as $index => $item):
                                $subtotal = $item['precio'] * $item['cantidad'];
                                $total += $subtotal;
                            ?>
                            <div class="cart-item">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <h6 class="mb-1"><?= htmlspecialchars($item['nombre']) ?></h6>
                                        <small class="text-muted">$<?= number_format($item['precio'], 2) ?> c/u</small>
                                    </div>
                                    <div class="col-md-3">
                                        <form method="POST" class="d-flex align-items-center">
                                            <input type="hidden" name="item_index" value="<?= $index ?>">
                                            <input type="number" name="nueva_cantidad" value="<?= $item['cantidad'] ?>" 
                                                   min="1" max="<?= $item['stock'] ?>" 
                                                   class="form-control form-control-sm quantity-input me-2" style="width: 70px;">
                                            <button type="submit" name="actualizar_cantidad" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-check"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>$<?= number_format($subtotal, 2) ?></strong>
                                    </div>
                                    <div class="col-md-2">
                                        <a href="?eliminar=<?= $index ?>" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="total-section text-center">
                                <h3><i class="bi bi-calculator"></i> Total: $<?= number_format($total, 2) ?></h3>
                            </div>
                            
                            <form method="POST" class="mt-3">
                                <button type="submit" name="finalizar_venta" class="btn btn-success-gradient btn-modern w-100">
                                    <i class="bi bi-check-circle"></i> Finalizar Venta
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Funcionalidad de búsqueda de clientes
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchCliente');
    const clientesList = document.getElementById('clientesList');
    const noResults = document.getElementById('noResults');
    const clienteItems = document.querySelectorAll('.cliente-item');

    // Función de búsqueda
    function searchClients() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        clienteItems.forEach(item => {
            const clientName = item.dataset.nombre;
            const nameElement = item.querySelector('.cliente-nombre');
            
            if (clientName.includes(searchTerm)) {
                item.style.display = 'block';
                visibleCount++;
                
                // Resaltar término de búsqueda
                if (searchTerm) {
                    const originalText = nameElement.textContent;
                    const regex = new RegExp(`(${searchTerm})`, 'gi');
                    const highlightedText = originalText.replace(regex, '<span class="highlight">$1</span>');
                    nameElement.innerHTML = highlightedText;
                } else {
                    nameElement.innerHTML = nameElement.textContent;
                }
            } else {
                item.style.display = 'none';
            }
        });

        // Mostrar/ocultar mensaje de "no resultados"
        if (visibleCount === 0 && searchTerm) {
            noResults.style.display = 'block';
            clientesList.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            clientesList.style.display = 'block';
        }
    }

    // Evento de búsqueda en tiempo real
    searchInput.addEventListener('input', searchClients);

    // Manejar selección de cliente
    document.querySelectorAll('.select-client-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const clientId = this.dataset.clientId;
            const clientName = this.dataset.clientName;
            
            // Confirmar selección
            if (confirm(`¿Confirmas que quieres seleccionar a "${clientName}"?`)) {
                document.getElementById('clienteIdInput').value = clientId;
                document.getElementById('clienteForm').submit();
            }
        });
    });

    // Permitir selección con Enter
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const visibleItems = Array.from(clienteItems).filter(item => 
                item.style.display !== 'none'
            );
            
            if (visibleItems.length === 1) {
                const btn = visibleItems[0].querySelector('.select-client-btn');
                btn.click();
            }
        }
    });

    // Hacer clickeable toda la fila del cliente
    clienteItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Solo si no se hizo clic en el botón
            if (!e.target.closest('.select-client-btn')) {
                const btn = this.querySelector('.select-client-btn');
                btn.click();
            }
        });
    });
});

// Actualizar stock disponible dinámicamente
document.querySelector('select[name="producto_id"]')?.addEventListener('change', function() {
    const cantidadInput = document.querySelector('input[name="cantidad"]');
    const selectedOption = this.options[this.selectedIndex];
    
    if (selectedOption.dataset.stock) {
        cantidadInput.max = selectedOption.dataset.stock;
        cantidadInput.value = Math.min(cantidadInput.value, selectedOption.dataset.stock);
    }
});

// Auto-submit para actualización de cantidad
document.querySelectorAll('.quantity-input[name="nueva_cantidad"]').forEach(input => {
    input.addEventListener('change', function() {
        this.closest('form').submit();
    });
});
</script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>