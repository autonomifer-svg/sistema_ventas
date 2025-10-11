<?php
require_once(__DIR__ . '/../includes/config.php');
requireAuth();
require_once(__DIR__ . '/../includes/conexion.php');

// Obtener productos reales de la base de datos (campo `100` es el stock)
$sql_productos = "SELECT CodigoNum, Codigo, Descripcion, PrecioVenta, `100` as Stock 
                  FROM productos 
                  WHERE Suspendido = 0 AND `100` > 0
                  ORDER BY Descripcion";
$result_productos = $conexion->query($sql_productos);
$productos = [];
if ($result_productos && $result_productos->num_rows > 0) {
    while ($row = $result_productos->fetch_assoc()) {
        $productos[] = $row;
    }
}

// Obtener clientes reales
$sql_clientes = "SELECT NroCliente, Nombre FROM clientes WHERE Inactivo = 0 ORDER BY Nombre";
$result_clientes = $conexion->query($sql_clientes);
$clientes = [];
if ($result_clientes && $result_clientes->num_rows > 0) {
    while ($row = $result_clientes->fetch_assoc()) {
        $clientes[] = $row;
    }
}

// Obtener sucursales
$sql_sucursales = "SELECT NroSucursal, Nombre FROM sucursal ORDER BY Nombre";
$result_sucursales = $conexion->query($sql_sucursales);
$sucursales = [];
if ($result_sucursales && $result_sucursales->num_rows > 0) {
    while ($row = $result_sucursales->fetch_assoc()) {
        $sucursales[] = $row;
    }
}

// Obtener formas de pago
$sql_formas_pago = "SELECT IdFormaPago, FormaPago FROM formapago ORDER BY FormaPago";
$result_formas_pago = $conexion->query($sql_formas_pago);
$formas_pago = [];
if ($result_formas_pago && $result_formas_pago->num_rows > 0) {
    while ($row = $result_formas_pago->fetch_assoc()) {
        $formas_pago[] = $row;
    }
}

// Inicializar carrito y cliente seleccionado
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}
if (!isset($_SESSION['cliente_seleccionado'])) {
    $_SESSION['cliente_seleccionado'] = null;
}
if (!isset($_SESSION['sucursal_seleccionada'])) {
    $_SESSION['sucursal_seleccionada'] = !empty($sucursales) ? $sucursales[0]['NroSucursal'] : 1;
}
if (!isset($_SESSION['forma_pago_seleccionada'])) {
    $_SESSION['forma_pago_seleccionada'] = !empty($formas_pago) ? $formas_pago[0]['IdFormaPago'] : 1;
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
    $cantidad = floatval($_POST['cantidad']);
    
    // Buscar el producto seleccionado
    $producto_seleccionado = null;
    foreach ($productos as $producto) {
        if ($producto['CodigoNum'] == $producto_id) {
            $producto_seleccionado = $producto;
            break;
        }
    }
    
    if ($producto_seleccionado && $cantidad <= $producto_seleccionado['Stock']) {
        // Verificar si el producto ya está en el carrito
        $encontrado = false;
        foreach ($_SESSION['carrito'] as &$item) {
            if ($item['id'] == $producto_id) {
                $nueva_cantidad = $item['cantidad'] + $cantidad;
                if ($nueva_cantidad <= $producto_seleccionado['Stock']) {
                    $item['cantidad'] = $nueva_cantidad;
                    $encontrado = true;
                }
                break;
            }
        }
        
        // Si no está en el carrito, agregarlo
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
    $nueva_cantidad = floatval($_POST['nueva_cantidad']);
    
    if (isset($_SESSION['carrito'][$index]) && $nueva_cantidad > 0 && $nueva_cantidad <= $_SESSION['carrito'][$index]['stock']) {
        $_SESSION['carrito'][$index]['cantidad'] = $nueva_cantidad;
    }
}

// Procesar actualizar configuración
if (isset($_POST['actualizar_config'])) {
    $_SESSION['sucursal_seleccionada'] = intval($_POST['sucursal_id']);
    $_SESSION['forma_pago_seleccionada'] = intval($_POST['forma_pago_id']);
}

// Procesar finalizar venta
if (isset($_POST['finalizar_venta'])) {
    if (empty($_SESSION['carrito']) || !$_SESSION['cliente_seleccionado']) {
        $error = "Debe seleccionar un cliente y agregar productos al carrito";
    } else {
        // Calcular totales
        $total_exenta = 0;
        $total_iva5 = 0;
        $total_iva10 = 0;
        $total_venta = 0;
        
        foreach ($_SESSION['carrito'] as $item) {
            $subtotal = $item['precio'] * $item['cantidad'];
            // Por defecto, asignar todo a IVA 10% (puedes modificar esto)
            $total_iva10 += $subtotal;
            $total_venta += $subtotal;
        }
        
        // Calcular IVA
        $iva_monto = $total_iva10 / 11; // IVA 10%
        $iva5_monto = $total_iva5 / 21; // IVA 5%
        
        // Insertar venta en la base de datos
        $conexion->begin_transaction();
        
        try {
            // Obtener el siguiente IdSalida
            $sql_max = "SELECT COALESCE(MAX(IdSalida), 0) + 1 as siguiente FROM salida";
            $result_max = $conexion->query($sql_max);
            $id_salida = $result_max->fetch_assoc()['siguiente'];
            
            // Insertar en tabla salida
            $sql_venta = "INSERT INTO salida (
                            IdSalida, NroSucursal, NroCliente, Fecha, FechaPedido, Hora,
                            Total, Exenta, Iva, Iva5, Estado, IdFormaPago, IdUsuario,
                            Anulado, ImportePagado, IdTipoCliente
                          ) VALUES (?, ?, ?, CURDATE(), CURDATE(), CURTIME(), 
                                    ?, ?, ?, ?, 1, ?, ?, 0, ?, 1)";
            
            $stmt_venta = $conexion->prepare($sql_venta);
            $id_usuario = $_SESSION['user_id'];
            $stmt_venta->bind_param("iiiiiiiiii", 
                $id_salida,
                $_SESSION['sucursal_seleccionada'],
                $_SESSION['cliente_seleccionado'],
                $total_venta,
                $total_exenta,
                $iva_monto,
                $iva5_monto,
                $_SESSION['forma_pago_seleccionada'],
                $id_usuario,
                $total_venta
            );
            $stmt_venta->execute();
            
            // Obtener el siguiente IdDetalleSalida
            $sql_max_detalle = "SELECT COALESCE(MAX(IdDetalleSalida), 0) + 1 as siguiente FROM detallesalida";
            $result_max_detalle = $conexion->query($sql_max_detalle);
            $id_detalle_salida = $result_max_detalle->fetch_assoc()['siguiente'];
            
            // Insertar detalles de la venta
            $sql_detalle = "INSERT INTO detallesalida (
                                IdDetalleSalida, IdSalida, CodigoNum, Descripcion,
                                Cantidad, Preciounidad, Preciocosto, Exenta, Ivad10, Ivad5,
                                Totalin, IdTipoImpuesto, Descuento
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 2, 0)";
            
            $stmt_detalle = $conexion->prepare($sql_detalle);
            
            foreach ($_SESSION['carrito'] as $item) {
                $subtotal = $item['precio'] * $item['cantidad'];
                $precio_costo = $item['precio'] * 0.7; // Asumiendo 30% de margen
                
                // Calcular IVA por ítem
                $ivad10 = $subtotal / 11; // IVA incluido en el precio
                
                $stmt_detalle->bind_param("iiisddddddi",
                    $id_detalle_salida,
                    $id_salida,
                    $item['id'],
                    $item['nombre'],
                    $item['cantidad'],
                    $item['precio'],
                    $precio_costo,
                    0, // Exenta
                    $ivad10, // IVA 10%
                    0, // IVA 5%
                    $subtotal
                );
                $stmt_detalle->execute();
                $id_detalle_salida++;
                
                // Actualizar stock del producto
                $sql_update_stock = "UPDATE productos SET `100` = `100` - ? WHERE CodigoNum = ?";
                $stmt_update = $conexion->prepare($sql_update_stock);
                $stmt_update->bind_param("di", $item['cantidad'], $item['id']);
                $stmt_update->execute();
            }
            
            // Confirmar transacción
            $conexion->commit();
            
            // Limpiar carrito y cliente seleccionado
            $_SESSION['carrito'] = [];
            $_SESSION['cliente_seleccionado'] = null;
            $_SESSION['mensaje_exito'] = "Venta registrada exitosamente! ID: $id_salida";
            header("Location: historial.php");
            exit;
            
        } catch (Exception $e) {
            $conexion->rollback();
            $error = "Error al registrar la venta: " . $e->getMessage();
            error_log("Error en venta: " . $e->getMessage());
        }
    }
}

// Obtener información del cliente seleccionado
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
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
}

.btn-success-gradient:hover {
    background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
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

.config-section {
    background: linear-gradient(135deg, #f8f9ff 0%, #e8ecff 100%);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
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
                        <!-- Buscador -->
                        <div class="search-container mb-4">
                            <input type="text" 
                                   id="searchCliente" 
                                   class="form-control search-input" 
                                   placeholder="Buscar cliente por nombre..."
                                   autocomplete="off">
                            <i class="bi bi-search search-icon"></i>
                        </div>

                        <!-- Lista de clientes -->
                        <div class="clientes-container" style="max-height: 500px; overflow-y: auto;">
                            <div id="clientesList" class="row g-3">
                                <?php foreach ($clientes as $cliente): ?>
                                    <div class="col-md-6 cliente-item-wrapper">
                                        <div class="card cliente-item h-100" 
                                             data-nombre="<?= strtolower($cliente['Nombre']) ?>">
                                            <div class="card-body d-flex align-items-center">
                                                <div class="cliente-avatar me-3">
                                                    <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.3rem;">
                                                        <?= strtoupper(substr($cliente['Nombre'], 0, 1)) ?>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="cliente-nombre mb-1"><?= htmlspecialchars($cliente['Nombre']) ?></h6>
                                                    <small class="text-muted">ID: <?= $cliente['NroCliente'] ?></small>
                                                </div>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="cliente_id" value="<?= $cliente['NroCliente'] ?>">
                                                    <button type="submit" 
                                                            name="seleccionar_cliente" 
                                                            class="btn btn-gradient btn-modern select-client-btn"
                                                            data-client-id="<?= $cliente['NroCliente'] ?>"
                                                            data-client-name="<?= htmlspecialchars($cliente['Nombre']) ?>">
                                                        <i class="bi bi-check-circle"></i> Seleccionar
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Mensaje sin resultados -->
                            <div id="noResults" style="display: none;" class="text-center py-5">
                                <i class="bi bi-search" style="font-size: 3rem; color: #cbd5e0;"></i>
                                <p class="text-muted mt-3">No se encontraron clientes con ese nombre</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($_SESSION['cliente_seleccionado']): ?>
        <!-- PASO 2 y 3: Agregar Productos y Revisar Carrito -->
        <div class="row">
            <!-- Columna Izquierda: Agregar Productos -->
            <div class="col-lg-7">
                <!-- Info del Cliente Seleccionado -->
                <div class="client-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5><i class="bi bi-person-check-fill"></i> Cliente Seleccionado</h5>
                            <h4 class="mb-0"><?= htmlspecialchars($cliente_info['Nombre']) ?></h4>
                            <small>ID: <?= $cliente_info['NroCliente'] ?></small>
                        </div>
                        <form method="POST">
                            <button type="submit" name="cambiar_cliente" class="btn btn-light btn-modern">
                                <i class="bi bi-arrow-left-circle"></i> Cambiar
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Configuración de Venta -->
                <div class="card modern-card mb-4">
                    <div class="card-header gradient-header">
                        <h5 class="mb-0"><i class="bi bi-gear"></i> Configuración de Venta</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Sucursal</label>
                                    <select name="sucursal_id" class="form-select" required>
                                        <?php foreach ($sucursales as $sucursal): ?>
                                            <option value="<?= $sucursal['NroSucursal'] ?>" 
                                                    <?= $_SESSION['sucursal_seleccionada'] == $sucursal['NroSucursal'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($sucursal['Nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Forma de Pago</label>
                                    <select name="forma_pago_id" class="form-select" required>
                                        <?php foreach ($formas_pago as $forma): ?>
                                            <option value="<?= $forma['IdFormaPago'] ?>" 
                                                    <?= $_SESSION['forma_pago_seleccionada'] == $forma['IdFormaPago'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($forma['FormaPago']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="actualizar_config" class="btn btn-gradient btn-modern w-100">
                                        <i class="bi bi-check-circle"></i> Actualizar Configuración
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Agregar Productos -->
                <div class="card modern-card">
                    <div class="card-header gradient-header">
                        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Paso 2: Agregar Productos</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($productos)): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> No hay productos disponibles con stock.
                            </div>
                        <?php else: ?>
                            <form method="POST" class="product-form">
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label class="form-label">Producto</label>
                                        <select name="producto_id" class="form-select" required>
                                            <option value="">Seleccione un producto</option>
                                            <?php foreach ($productos as $producto): ?>
                                                <option value="<?= $producto['CodigoNum'] ?>" 
                                                        data-precio="<?= $producto['PrecioVenta'] ?>"
                                                        data-stock="<?= $producto['Stock'] ?>">
                                                    <?= htmlspecialchars($producto['Descripcion']) ?> 
                                                    - $<?= number_format($producto['PrecioVenta'], 0) ?> 
                                                    (Stock: <?= $producto['Stock'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Cantidad</label>
                                        <input type="number" 
                                               name="cantidad" 
                                               class="form-control quantity-input" 
                                               min="1" 
                                               step="0.01"
                                               value="1" 
                                               required>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="submit" 
                                                name="agregar" 
                                                class="btn btn-gradient btn-modern w-100">
                                            <i class="bi bi-cart-plus"></i> Agregar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Carrito -->
            <div class="col-lg-5">
                <div class="card modern-card sticky-top" style="top: 20px;">
                    <div class="card-header gradient-header">
                        <h5 class="mb-0"><i class="bi bi-cart3"></i> Paso 3: Carrito de Compras</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($_SESSION['carrito'])): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-cart-x" style="font-size: 4rem; color: #cbd5e0;"></i>
                                <p class="text-muted mt-3">El carrito está vacío</p>
                                <small class="text-muted">Agrega productos desde el formulario de la izquierda</small>
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
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?= htmlspecialchars($item['nombre']) ?></h6>
                                                <small class="text-muted">Precio unitario: $<?= number_format($item['precio'], 0) ?></small>
                                            </div>
                                            <a href="?eliminar=<?= $index ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('¿Eliminar este producto?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <form method="POST" class="d-flex align-items-center">
                                                <input type="hidden" name="item_index" value="<?= $index ?>">
                                                <label class="me-2 mb-0">Cantidad:</label>
                                                <input type="number" 
                                                       name="nueva_cantidad" 
                                                       class="form-control form-control-sm quantity-input" 
                                                       style="width: 80px;"
                                                       min="1" 
                                                       max="<?= $item['stock'] ?>"
                                                       step="0.01"
                                                       value="<?= $item['cantidad'] ?>">
                                                <button type="submit" 
                                                        name="actualizar_cantidad" 
                                                        class="btn btn-sm btn-outline-primary ms-2">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                            </form>
                                            <div class="text-end">
                                                <strong>$<?= number_format($subtotal, 0) ?></strong>
                                            </div>
                                        </div>
                                        <small class="text-muted">Stock disponible: <?= $item['stock'] ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="total-section text-center mt-3">
                                <h3><i class="bi bi-calculator"></i> Total: $<?= number_format($total, 0) ?></h3>
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

    if (searchInput) {
        function searchClients() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            clienteItems.forEach(item => {
                const clientName = item.dataset.nombre;
                const nameElement = item.querySelector('.cliente-nombre');
                
                if (clientName.includes(searchTerm)) {
                    item.style.display = 'block';
                    visibleCount++;
                    
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

            if (visibleCount === 0 && searchTerm) {
                noResults.style.display = 'block';
                clientesList.style.display = 'none';
            } else {
                noResults.style.display = 'none';
                clientesList.style.display = 'block';
            }
        }

        searchInput.addEventListener('input', searchClients);

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
    }

    document.querySelectorAll('.select-client-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const clientId = this.dataset.clientId;
            const clientName = this.dataset.clientName;
            
            if (confirm(`¿Confirmas que quieres seleccionar a "${clientName}"?`)) {
                document.getElementById('clienteIdInput').value = clientId;
                document.getElementById('clienteForm').submit();
            }
        });
    });

    clienteItems.forEach(item => {
        item.addEventListener('click', function(e) {
            if (!e.target.closest('.select-client-btn')) {
                const btn = this.querySelector('.select-client-btn');
                btn.click();
            }
        });
    });
});

const productoSelect = document.querySelector('select[name="producto_id"]');
if (productoSelect) {
    productoSelect.addEventListener('change', function() {
        const cantidadInput = document.querySelector('input[name="cantidad"]');
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.dataset.stock) {
            cantidadInput.max = selectedOption.dataset.stock;
            cantidadInput.value = Math.min(cantidadInput.value, selectedOption.dataset.stock);
        }
    });
}

document.querySelectorAll('.quantity-input[name="nueva_cantidad"]').forEach(input => {
    input.addEventListener('change', function() {
        this.closest('form').submit();
    });
});
</script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>
