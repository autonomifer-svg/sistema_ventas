<?php
require_once(__DIR__ . '/../includes/config.php');
requireAuth();

$success = '';
$error = '';
$producto = null;

// Verificar que se proporcione un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('productos/listar.php?error=ID de producto inválido');
}

$id = intval($_GET['id']);

// Obtener datos del producto
try {
    $conexion = conectarDB();
    
    $sql = "SELECT p.*, m.Marca, r.Descripcion as Rubro, sr.Descripcion as SubRubro, tp.TipoProducto
            FROM productos p
            LEFT JOIN marca m ON p.IdMarca = m.IdMarca
            LEFT JOIN rubros r ON p.IdRubro = r.IdRubro
            LEFT JOIN subrubro sr ON p.IdSubRubro = sr.IdSubRubro
            LEFT JOIN tipoproducto tp ON p.IdTipoProducto = tp.IdTipoProducto
            WHERE p.CodigoNum = ?";
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error en la preparación: " . $conexion->error);
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conexion->close();
        redirect('productos/listar.php?error=Producto no encontrado');
    }
    
    $producto = $result->fetch_assoc();
    $stmt->close();
    
    // Obtener datos para los selects
    $sql_rubros = "SELECT IdRubro, Descripcion FROM rubros ORDER BY Descripcion";
    $result_rubros = $conexion->query($sql_rubros);
    
    $sql_subrubros = "SELECT IdSubRubro, Descripcion FROM subrubro ORDER BY Descripcion";
    $result_subrubros = $conexion->query($sql_subrubros);
    
    $sql_marcas = "SELECT IdMarca, Marca FROM marca ORDER BY Marca";
    $result_marcas = $conexion->query($sql_marcas);
    
    $sql_tipos = "SELECT IdTipoProducto, TipoProducto FROM tipoproducto ORDER BY TipoProducto";
    $result_tipos = $conexion->query($sql_tipos);
    
} catch (Exception $e) {
    error_log("Error al obtener producto: " . $e->getMessage());
    redirect('productos/listar.php?error=Error al cargar producto');
}

// Actualizar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descripcion = trim($_POST['nombre'] ?? '');
    $precio_venta = floatval($_POST['precio'] ?? 0);
    $stock = floatval($_POST['stock'] ?? 0);
    $id_rubro = !empty($_POST['id_rubro']) ? intval($_POST['id_rubro']) : null;
    $id_subrubro = !empty($_POST['id_subrubro']) ? intval($_POST['id_subrubro']) : null;
    $id_marca = !empty($_POST['id_marca']) ? intval($_POST['id_marca']) : null;
    $id_tipo_producto = !empty($_POST['id_tipo_producto']) ? intval($_POST['id_tipo_producto']) : 1;

    // Validaciones
    if (empty($descripcion)) {
        $error = "El nombre del producto es obligatorio";
    } elseif ($precio_venta <= 0) {
        $error = "El precio debe ser mayor a 0";
    } elseif ($stock < 0) {
        $error = "El stock no puede ser negativo";
    } else {
        try {
            // Calcular precio costo (70% del precio venta)
            $precio_costo = $precio_venta * 0.7;
            
            $sql = "UPDATE productos SET 
                        Descripcion = ?, 
                        PrecioVenta = ?, 
                        PrecioCosto = ?,
                        `100` = ?,
                        IdRubro = ?,
                        IdSubRubro = ?,
                        IdMarca = ?,
                        IdTipoProducto = ?
                    WHERE CodigoNum = ?";
            $stmt = $conexion->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Error en la preparación: " . $conexion->error);
            }
            
            $stmt->bind_param("sdddiiii", 
                $descripcion, $precio_venta, $precio_costo, $stock,
                $id_rubro, $id_subrubro, $id_marca, $id_tipo_producto, $id
            );
            
            if ($stmt->execute()) {
                $success = "Producto actualizado exitosamente";
                
                // Actualizar datos locales
                $producto['Descripcion'] = $descripcion;
                $producto['PrecioVenta'] = $precio_venta;
                $producto['PrecioCosto'] = $precio_costo;
                $producto['100'] = $stock;
                $producto['IdRubro'] = $id_rubro;
                $producto['IdSubRubro'] = $id_subrubro;
                $producto['IdMarca'] = $id_marca;
                $producto['IdTipoProducto'] = $id_tipo_producto;
                
                error_log("Producto actualizado: CodigoNum=$id, Descripcion='$descripcion'");
            } else {
                throw new Exception("Error al ejecutar: " . $stmt->error);
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $error = "Error al actualizar el producto: " . $e->getMessage();
            error_log("Error en actualizar producto: " . $e->getMessage());
        }
    }
}

include(__DIR__ . '/../includes/header.php');
?>

<div class="row">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-pencil"></i> Editar Producto</h2>
            <div>
                <a href="<?= $base_url ?>productos/listar.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver a Lista
                </a>
                <a href="<?= $base_url ?>productos/crear.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Nuevo Producto
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Actualizar Información</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" id="editarForm">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Producto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" 
                               value="<?= htmlspecialchars($producto['Descripcion']) ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Precio de Venta ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="precio" 
                                   value="<?= $producto['PrecioVenta'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stock <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" name="stock" 
                                   value="<?= $producto['100'] ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de Producto</label>
                            <select class="form-select" name="id_tipo_producto">
                                <?php while ($tipo = $result_tipos->fetch_assoc()): ?>
                                    <option value="<?= $tipo['IdTipoProducto'] ?>"
                                        <?= ($producto['IdTipoProducto'] == $tipo['IdTipoProducto']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tipo['TipoProducto']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rubro</label>
                            <select class="form-select" name="id_rubro">
                                <option value="">Seleccionar rubro...</option>
                                <?php while ($rubro = $result_rubros->fetch_assoc()): ?>
                                    <option value="<?= $rubro['IdRubro'] ?>"
                                        <?= ($producto['IdRubro'] == $rubro['IdRubro']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($rubro['Descripcion']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sub-Rubro</label>
                            <select class="form-select" name="id_subrubro">
                                <option value="">Seleccionar sub-rubro...</option>
                                <?php while ($subrubro = $result_subrubros->fetch_assoc()): ?>
                                    <option value="<?= $subrubro['IdSubRubro'] ?>"
                                        <?= ($producto['IdSubRubro'] == $subrubro['IdSubRubro']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($subrubro['Descripcion']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Marca</label>
                            <select class="form-select" name="id_marca">
                                <option value="">Seleccionar marca...</option>
                                <?php while ($marca = $result_marcas->fetch_assoc()): ?>
                                    <option value="<?= $marca['IdMarca'] ?>"
                                        <?= ($producto['IdMarca'] == $marca['IdMarca']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($marca['Marca']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?= $base_url ?>productos/listar.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Actualizar Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Información</h6>
            </div>
            <div class="card-body">
                <p><strong>Código Interno:</strong> <?= htmlspecialchars($producto['Codigo']) ?></p>
                <p><strong>Código Numérico:</strong> <?= $producto['CodigoNum'] ?></p>
                <?php if (isset($producto['FechaAct']) && $producto['FechaAct']): ?>
                    <p><strong>Última Actualización:</strong> <?= date('d/m/Y H:i', strtotime($producto['FechaAct'])) ?></p>
                <?php endif; ?>
                
                <hr>
                
                <h6>Estado del Stock:</h6>
                <?php 
                $stock = $producto['100'];
                if ($stock <= 0): ?>
                    <span class="badge bg-danger">Sin Stock</span>
                    <p class="small text-muted mt-1">⚠️ Producto agotado</p>
                <?php elseif ($stock <= 10): ?>
                    <span class="badge bg-warning text-dark">Stock Bajo</span>
                    <p class="small text-muted mt-1">⚠️ Considerar reposición</p>
                <?php else: ?>
                    <span class="badge bg-success">Stock Normal</span>
                    <p class="small text-muted mt-1">✅ Stock suficiente</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bi bi-trash"></i> Zona Peligrosa</h6>
            </div>
            <div class="card-body">
                <p class="small">Si ya no necesitas este producto, puedes suspenderlo o eliminarlo.</p>
                <a href="<?= $base_url ?>productos/eliminar.php?id=<?= $producto['CodigoNum'] ?>" 
                   class="btn btn-outline-danger btn-sm"
                   onclick="return confirm('⚠️ ¿Estás seguro de eliminar este producto?\n\nEsta acción no se puede deshacer.\n\nProducto: <?= htmlspecialchars($producto['Descripcion']) ?>')">
                    <i class="bi bi-trash"></i> Eliminar Producto
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Validación del formulario
document.getElementById('editarForm').addEventListener('submit', function(e) {
    const nombre = document.querySelector('input[name="nombre"]').value.trim();
    const precio = parseFloat(document.querySelector('input[name="precio"]').value);
    const stock = parseFloat(document.querySelector('input[name="stock"]').value);
    
    if (!nombre) {
        alert('El nombre del producto es obligatorio');
        e.preventDefault();
        return;
    }
    
    if (precio <= 0) {
        alert('El precio debe ser mayor a 0');
        e.preventDefault();
        return;
    }
    
    if (stock < 0) {
        alert('El stock no puede ser negativo');
        e.preventDefault();
        return;
    }
});
</script>

<?php 
$conexion->close();
include(__DIR__ . '/../includes/footer.php'); 
?>
