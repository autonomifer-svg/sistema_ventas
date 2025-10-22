<?php
// INCLUSIÓN DE ARCHIVOS Y VERIFICACIÓN DE SEGURIDAD
// --------------------------------------------------
require_once(__DIR__ . '/../includes/config.php');
requireAuth();
require_once(__DIR__ . '/../includes/conexion.php');

// --- OBTENCIÓN DE DATOS INICIALES DESDE LA BASE DE DATOS ---

// 1. OBTENER PRODUCTOS DISPONIBLES
// Se seleccionan solo productos que no están suspendidos y tienen stock > 0.
$sql_productos = "SELECT CodigoNum, Codigo, Descripcion, PrecioVenta, `100` as Stock 
                  FROM productos 
                  WHERE Suspendido = 0 AND `100` > 0
                  ORDER BY Descripcion";
$result_productos = $conexion->query($sql_productos);
$productos = [];
if ($result_productos) {
    while ($row = $result_productos->fetch_assoc()) {
        $productos[] = $row;
    }
}

// 2. OBTENER CLIENTES ACTIVOS
$sql_clientes = "SELECT NroCliente, Nombre FROM clientes WHERE Inactivo = 0 ORDER BY Nombre";
$result_clientes = $conexion->query($sql_clientes);
$clientes = [];
if ($result_clientes) {
    while ($row = $result_clientes->fetch_assoc()) {
        $clientes[] = $row;
    }
}

// 3. OBTENER SUCURSALES
$sql_sucursales = "SELECT NroSucursal, Nombre FROM sucursal ORDER BY Nombre";
$result_sucursales = $conexion->query($sql_sucursales);
$sucursales = [];
if ($result_sucursales) {
    while ($row = $result_sucursales->fetch_assoc()) {
        $sucursales[] = $row;
    }
}

// 4. OBTENER FORMAS DE PAGO
$sql_formas_pago = "SELECT IdFormaPago, FormaPago FROM formapago ORDER BY FormaPago";
$result_formas_pago = $conexion->query($sql_formas_pago);
$formas_pago = [];
if ($result_formas_pago) {
    while ($row = $result_formas_pago->fetch_assoc()) {
        $formas_pago[] = $row;
    }
}

// --- INICIALIZACIÓN Y GESTIÓN DE LA SESIÓN DE VENTA ---

// Se utiliza la sesión para mantener el estado de la venta actual (carrito, cliente, etc.).

// Inicializa el carrito si no existe.
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}
// Inicializa el cliente seleccionado si no existe.
if (!isset($_SESSION['cliente_seleccionado'])) {
    $_SESSION['cliente_seleccionado'] = null;
}
// Inicializa la sucursal (usando la primera disponible como default).
if (!isset($_SESSION['sucursal_seleccionada'])) {
    $_SESSION['sucursal_seleccionada'] = !empty($sucursales) ? $sucursales[0]['NroSucursal'] : 1;
}
// Inicializa la forma de pago (usando la primera disponible como default).
if (!isset($_SESSION['forma_pago_seleccionada'])) {
    $_SESSION['forma_pago_seleccionada'] = !empty($formas_pago) ? $formas_pago[0]['IdFormaPago'] : 1;
}

// --- PROCESAMIENTO DE ACCIONES DEL FORMULARIO ---

// 1. SELECCIONAR / CAMBIAR CLIENTE
if (isset($_POST['seleccionar_cliente'])) {
    $_SESSION['cliente_seleccionado'] = intval($_POST['cliente_id']);
}
if (isset($_POST['cambiar_cliente'])) {
    $_SESSION['cliente_seleccionado'] = null;
    $_SESSION['carrito'] = []; // Limpia el carrito al cambiar de cliente.
}

// 2. AGREGAR PRODUCTO AL CARRITO
if (isset($_POST['agregar'])) {
    $producto_id = intval($_POST['producto_id']);
    $cantidad = floatval($_POST['cantidad']);
    
    // Busca el producto en el array de productos ya cargado.
    $producto_seleccionado = null;
    foreach ($productos as $p) {
        if ($p['CodigoNum'] == $producto_id) {
            $producto_seleccionado = $p;
            break;
        }
    }
    
    // Valida que el producto exista y que la cantidad no supere el stock.
    if ($producto_seleccionado && $cantidad > 0 && $cantidad <= $producto_seleccionado['Stock']) {
        $encontrado = false;
        // Si el producto ya está en el carrito, actualiza la cantidad.
        foreach ($_SESSION['carrito'] as &$item) {
            if ($item['id'] == $producto_id) {
                $item['cantidad'] += $cantidad;
                $encontrado = true;
                break;
            }
        }
        // Si no está, lo agrega como un nuevo item.
        if (!$encontrado) {
            $_SESSION['carrito'][] = [
                'id' => $producto_seleccionado['CodigoNum'],
                'nombre' => $producto_seleccionado['Descripcion'],
                'precio' => $producto_seleccionado['PrecioVenta'],
                'cantidad' => $cantidad,
                'stock' => $producto_seleccionado['Stock']
            ];
        }
    } else {
        $error = "Stock insuficiente o producto no válido.";
    }
}

// 3. ELIMINAR PRODUCTO DEL CARRITO
if (isset($_GET['eliminar'])) {
    $index = intval($_GET['eliminar']);
    if (isset($_SESSION['carrito'][$index])) {
        unset($_SESSION['carrito'][$index]);
        $_SESSION['carrito'] = array_values($_SESSION['carrito']); // Re-indexa el array.
    }
    header("Location: nueva.php"); // Redirige para limpiar la URL.
    exit;
}

// 4. ACTUALIZAR CANTIDAD EN EL CARRITO
if (isset($_POST['actualizar_cantidad'])) {
    $index = intval($_POST['item_index']);
    $nueva_cantidad = floatval($_POST['nueva_cantidad']);
    
    if (isset($_SESSION['carrito'][$index]) && $nueva_cantidad > 0 && $nueva_cantidad <= $_SESSION['carrito'][$index]['stock']) {
        $_SESSION['carrito'][$index]['cantidad'] = $nueva_cantidad;
    }
}

// 5. ACTUALIZAR CONFIGURACIÓN DE LA VENTA (SUCURSAL Y FORMA DE PAGO)
if (isset($_POST['actualizar_config'])) {
    $_SESSION['sucursal_seleccionada'] = intval($_POST['sucursal_id']);
    $_SESSION['forma_pago_seleccionada'] = intval($_POST['forma_pago_id']);
}

// 6. FINALIZAR LA VENTA
if (isset($_POST['finalizar_venta'])) {
    // Validar que haya un cliente y productos en el carrito.
    if (empty($_SESSION['carrito']) || !$_SESSION['cliente_seleccionado']) {
        $error = "Debe seleccionar un cliente y agregar productos al carrito";
    } else {
        // --- INICIO DE LA TRANSACCIÓN DE BASE DE DATOS ---
        $conexion->begin_transaction();
        
        try {
            // a. CALCULAR TOTALES DE LA VENTA
            $total_venta = 0;
            foreach ($_SESSION['carrito'] as $item) {
                $total_venta += $item['precio'] * $item['cantidad'];
            }
            $iva_monto = $total_venta / 11;

            // b. INSERTAR EL REGISTRO PRINCIPAL DE LA VENTA EN LA TABLA `salida`
            $sql_max_salida = "SELECT COALESCE(MAX(IdSalida), 0) + 1 as siguiente FROM salida";
            $id_salida = $conexion->query($sql_max_salida)->fetch_assoc()['siguiente'];
            
            $sql_venta = "INSERT INTO salida (IdSalida, NroSucursal, NroCliente, Fecha, Hora, Total, Iva, Estado, IdFormaPago, IdUsuario) 
                          VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, ?, 1, ?, ?)";
            $stmt_venta = $conexion->prepare($sql_venta);
            $stmt_venta->bind_param("iiiddisi", 
                $id_salida, $_SESSION['sucursal_seleccionada'], $_SESSION['cliente_seleccionado'],
                $total_venta, $iva_monto, $_SESSION['forma_pago_seleccionada'], $_SESSION['user_id']
            );
            $stmt_venta->execute();

            // c. INSERTAR CADA PRODUCTO DEL CARRITO EN LA TABLA `detallesalida`
            $sql_detalle = "INSERT INTO detallesalida (IdSalida, CodigoNum, Descripcion, Cantidad, Preciounidad, Totalin) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_detalle = $conexion->prepare($sql_detalle);

            // d. ACTUALIZAR EL STOCK DE CADA PRODUCTO
            $sql_update_stock = "UPDATE productos SET `100` = `100` - ? WHERE CodigoNum = ?";
            $stmt_update = $conexion->prepare($sql_update_stock);

            foreach ($_SESSION['carrito'] as $item) {
                // Insertar detalle
                $subtotal = $item['precio'] * $item['cantidad'];
                $stmt_detalle->bind_param("iisdds", 
                    $id_salida, $item['id'], $item['nombre'], 
                    $item['cantidad'], $item['precio'], $subtotal
                );
                $stmt_detalle->execute();

                // Actualizar stock
                $stmt_update->bind_param("di", $item['cantidad'], $item['id']);
                $stmt_update->execute();
            }
            
            // e. CONFIRMAR LA TRANSACCIÓN
            $conexion->commit();
            
            // f. LIMPIAR LA SESIÓN Y REDIRIGIR
            $_SESSION['carrito'] = [];
            $_SESSION['cliente_seleccionado'] = null;
            $_SESSION['mensaje_exito'] = "Venta registrada exitosamente! ID: $id_salida";
            header("Location: historial.php");
            exit;
            
        } catch (Exception $e) {
            // g. REVERTIR LA TRANSACCIÓN EN CASO DE ERROR
            $conexion->rollback();
            $error = "Error al registrar la venta: " . $e->getMessage();
            error_log("Error en venta: " . $e->getMessage());
        }
    }
}

// OBTENER INFORMACIÓN DEL CLIENTE SELECCIONADO PARA MOSTRAR
$cliente_info = null;
if ($_SESSION['cliente_seleccionado']) {
    foreach ($clientes as $cliente) {
        if ($cliente['NroCliente'] == $_SESSION['cliente_seleccionado']) {
            $cliente_info = $cliente;
            break;
        }
    }
}

include(__DIR__ . '/../includes/header.php');
?>

<style>
.modern-card { border: none; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); transition: all 0.3s ease; }
.modern-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
.gradient-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px 15px 0 0 !important; }
.client-info { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; }
.btn-modern { border-radius: 25px; padding: 10px 25px; font-weight: 600; transition: all 0.3s ease; border: none; }
.btn-gradient { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-gradient:hover { background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%); transform: translateY(-1px); color: white; }
.btn-success-gradient { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; }
.btn-success-gradient:hover { background: linear-gradient(135deg, #38a169 0%, #2f855a 100%); color: white; }
.quantity-input { border-radius: 10px; border: 2px solid #e2e8f0; transition: border-color 0.3s ease; }
.quantity-input:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
.cart-item { background: white; border-radius: 10px; padding: 15px; margin-bottom: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #667eea; }
.total-section { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border-radius: 15px; padding: 20px; margin-top: 20px; }
.step-indicator { display: flex; justify-content: center; margin-bottom: 30px; }
.step { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 10px; color: white; font-weight: bold; }
.step.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.step.inactive { background: #e2e8f0; color: #718096; }
.step-line { width: 50px; height: 2px; background: #e2e8f0; align-self: center; }
.step-line.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.search-input { border-radius: 25px; padding: 15px 50px 15px 20px; border: 2px solid #e2e8f0; transition: all 0.3s ease; font-size: 1.1rem; }
.search-input:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); outline: none; }
.search-icon { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 1.2rem; }
.cliente-item { background: white; border: 2px solid #f1f5f9 !important; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.05); cursor: pointer; }
.cliente-item:hover { border-color: #667eea !important; background: #f8faff; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(102, 126, 234, 0.15); }
.highlight { background-color: #fef3cd; font-weight: bold; border-radius: 3px; padding: 1px 3px; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Nueva Venta</h2>
        <div>
            <a href="historial.php" class="btn btn-info btn-modern me-2"><i class="bi bi-clock-history"></i> Ver Historial</a>
            <a href="nueva.php" class="btn btn-outline-secondary btn-modern"><i class="bi bi-arrow-clockwise"></i> Reiniciar</a>
        </div>
    </div>

    <div class="step-indicator">
        <div class="step <?= !$_SESSION['cliente_seleccionado'] ? 'active' : 'inactive' ?>">1</div>
        <div class="step-line <?= $_SESSION['cliente_seleccionado'] ? 'active' : '' ?>"></div>
        <div class="step <?= $_SESSION['cliente_seleccionado'] ? 'active' : 'inactive' ?>">2</div>
        <div class="step-line <?= !empty($_SESSION['carrito']) ? 'active' : '' ?>"></div>
        <div class="step <?= !empty($_SESSION['carrito']) ? 'active' : 'inactive' ?>">3</div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill"></i> <?= $_SESSION['mensaje_exito'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['mensaje_exito']); ?>
    <?php endif; ?>

    <?php if (!$_SESSION['cliente_seleccionado']): ?>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card modern-card">
                    <div class="card-header gradient-header text-center">
                        <h4><i class="bi bi-person-plus"></i> Paso 1: Seleccionar Cliente</h4>
                        <p class="mb-0">Busca y selecciona el cliente para esta venta</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="position-relative mb-4">
                            <input type="text" id="searchCliente" class="form-control search-input" placeholder="Buscar cliente por nombre..." autocomplete="off">
                            <i class="bi bi-search search-icon"></i>
                        </div>
                        <div style="max-height: 500px; overflow-y: auto;">
                            <div id="clientesList" class="row g-3">
                                <?php foreach ($clientes as $cliente): ?>
                                    <div class="col-md-6">
                                        <div class="card cliente-item h-100" data-nombre="<?= strtolower($cliente['Nombre']) ?>">
                                            <div class="card-body d-flex align-items-center">
                                                <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.3rem; margin-right: 1rem;">
                                                    <?= strtoupper(substr($cliente['Nombre'], 0, 1)) ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="cliente-nombre mb-1"><?= htmlspecialchars($cliente['Nombre']) ?></h6>
                                                    <small class="text-muted">ID: <?= $cliente['NroCliente'] ?></small>
                                                </div>
                                                <form method="POST">
                                                    <input type="hidden" name="cliente_id" value="<?= $cliente['NroCliente'] ?>">
                                                    <button type="submit" name="seleccionar_cliente" class="btn btn-gradient btn-modern"><i class="bi bi-check-circle"></i> Seleccionar</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div id="noResults" style="display: none;" class="text-center py-5">
                                <i class="bi bi-search" style="font-size: 3rem; color: #cbd5e0;"></i>
                                <p class="text-muted mt-3">No se encontraron clientes</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-7">
                <div class="client-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5><i class="bi bi-person-check-fill"></i> Cliente Seleccionado</h5>
                            <h4 class="mb-0"><?= htmlspecialchars($cliente_info['Nombre']) ?></h4>
                            <small>ID: <?= $cliente_info['NroCliente'] ?></small>
                        </div>
                        <form method="POST">
                            <button type="submit" name="cambiar_cliente" class="btn btn-light btn-modern"><i class="bi bi-arrow-left-circle"></i> Cambiar</button>
                        </form>
                    </div>
                </div>

                <div class="card modern-card mb-4">
                    <div class="card-header gradient-header"><h5 class="mb-0"><i class="bi bi-gear"></i> Configuración</h5></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Sucursal</label>
                                    <select name="sucursal_id" class="form-select" required>
                                        <?php foreach ($sucursales as $sucursal): ?>
                                            <option value="<?= $sucursal['NroSucursal'] ?>" <?= $_SESSION['sucursal_seleccionada'] == $sucursal['NroSucursal'] ? 'selected' : '' ?>><?= htmlspecialchars($sucursal['Nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Forma de Pago</label>
                                    <select name="forma_pago_id" class="form-select" required>
                                        <?php foreach ($formas_pago as $forma): ?>
                                            <option value="<?= $forma['IdFormaPago'] ?>" <?= $_SESSION['forma_pago_seleccionada'] == $forma['IdFormaPago'] ? 'selected' : '' ?>><?= htmlspecialchars($forma['FormaPago']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="actualizar_config" class="btn btn-gradient btn-modern w-100"><i class="bi bi-check-circle"></i> Actualizar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card modern-card">
                    <div class="card-header gradient-header"><h5 class="mb-0"><i class="bi bi-plus-circle"></i> Agregar Productos</h5></div>
                    <div class="card-body">
                        <?php if (empty($productos)): ?>
                            <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> No hay productos con stock</div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label>Producto</label>
                                        <select name="producto_id" class="form-select" required>
                                            <option value="">Seleccione</option>
                                            <?php foreach ($productos as $producto): ?>
                                                <option value="<?= $producto['CodigoNum'] ?>" data-stock="<?= $producto['Stock'] ?>"><?= htmlspecialchars($producto['Descripcion']) ?> - $<?= number_format($producto['PrecioVenta'], 0) ?> (Stock: <?= $producto['Stock'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Cantidad</label>
                                        <input type="number" name="cantidad" class="form-control quantity-input" min="1" step="0.01" value="1" required>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="submit" name="agregar" class="btn btn-gradient btn-modern w-100"><i class="bi bi-cart-plus"></i></button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card modern-card sticky-top" style="top: 20px;">
                    <div class="card-header gradient-header"><h5 class="mb-0"><i class="bi bi-cart3"></i> Carrito</h5></div>
                    <div class="card-body">
                        <?php if (empty($_SESSION['carrito'])): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-cart-x" style="font-size: 4rem; color: #cbd5e0;"></i>
                                <p class="text-muted mt-3">Carrito vacío</p>
                            </div>
                        <?php else: ?>
                            <div style="max-height: 400px; overflow-y: auto;">
                                <?php 
                                $total = 0;
                                foreach ($_SESSION['carrito'] as $index => $item): 
                                    $subtotal = $item['precio'] * $item['cantidad'];
                                    $total += $subtotal;
                                ?>
                                    <div class="cart-item">
                                        <div class="d-flex justify-content-between mb-2">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?= htmlspecialchars($item['nombre']) ?></h6>
                                                <small class="text-muted">$<?= number_format($item['precio'], 0) ?></small>
                                            </div>
                                            <a href="?eliminar=<?= $index ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')"><i class="bi bi-trash"></i></a>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <form method="POST" class="d-flex align-items-center">
                                                <input type="hidden" name="item_index" value="<?= $index ?>">
                                                <label class="me-2 mb-0">Cant:</label>
                                                <input type="number" name="nueva_cantidad" class="form-control form-control-sm quantity-input" style="width: 70px;" min="1" max="<?= $item['stock'] ?>" step="0.01" value="<?= $item['cantidad'] ?>">
                                                <button type="submit" name="actualizar_cantidad" class="btn btn-sm btn-outline-primary ms-2"><i class="bi bi-check"></i></button>
                                            </form>
                                            <strong>$<?= number_format($subtotal, 0) ?></strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="total-section text-center mt-3">
                                <h3><i class="bi bi-calculator"></i> Total: $<?= number_format($total, 0) ?></h3>
                            </div>
                            <form method="POST" class="mt-3">
                                <button type="submit" name="finalizar_venta" class="btn btn-success-gradient btn-modern w-100"><i class="bi bi-check-circle"></i> Finalizar Venta</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const search = document.getElementById('searchCliente');
    const items = document.querySelectorAll('.cliente-item');
    const noResults = document.getElementById('noResults');
    const list = document.getElementById('clientesList');

    if (search) {
        search.addEventListener('input', function() {
            const term = this.value.toLowerCase().trim();
            let visible = 0;

            items.forEach(item => {
                const nombre = item.dataset.nombre;
                const nameEl = item.querySelector('.cliente-nombre');
                
                if (nombre.includes(term)) {
                    item.style.display = 'block';
                    visible++;
                    
                    if (term) {
                        const regex = new RegExp(`(${term})`, 'gi');
                        nameEl.innerHTML = nameEl.textContent.replace(regex, '<span class="highlight">$1</span>');
                    } else {
                        nameEl.innerHTML = nameEl.textContent;
                    }
                } else {
                    item.style.display = 'none';
                }
            });

            if (visible === 0 && term) {
                noResults.style.display = 'block';
                list.style.display = 'none';
            } else {
                noResults.style.display = 'none';
                list.style.display = 'block';
            }
        });
    }

    const prodSelect = document.querySelector('select[name="producto_id"]');
    if (prodSelect) {
        prodSelect.addEventListener('change', function() {
            const cantInput = document.querySelector('input[name="cantidad"]');
            const option = this.options[this.selectedIndex];
            
            if (option.dataset.stock) {
                cantInput.max = option.dataset.stock;
                cantInput.value = Math.min(cantInput.value, option.dataset.stock);
            }
        });
    }
});
</script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>

