<?php
// INCLUSIÓN DE ARCHIVOS Y VERIFICACIÓN DE SEGURIDAD
// --------------------------------------------------
require_once(__DIR__ . '/../includes/config.php');
requireAuth();

// INICIALIZACIÓN DE VARIABLES
// ---------------------------
$success = '';
$error = '';
$producto = null; // Almacenará los datos del producto a editar.

// VALIDACIÓN DEL ID DEL PRODUCTO
// ------------------------------
// Es crucial verificar que se haya pasado un ID válido por la URL.
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Si no hay un ID o no es numérico, redirige a la lista con un mensaje de error.
    redirect('productos/listar.php?error=ID de producto inválido');
}

$id = intval($_GET['id']); // Convierte el ID a un entero por seguridad.

// OBTENCIÓN DE DATOS DEL PRODUCTO Y PARA LOS SELECTS
// --------------------------------------------------
try {
    $conexion = conectarDB();
    
    // Prepara la consulta para obtener los datos del producto específico.
    // Se usan LEFT JOINs para obtener también los nombres de marca, rubro, etc.
    $sql = "SELECT p.*, m.Marca, r.Descripcion as Rubro, sr.Descripcion as SubRubro, tp.TipoProducto
            FROM productos p
            LEFT JOIN marca m ON p.IdMarca = m.IdMarca
            LEFT JOIN rubros r ON p.IdRubro = r.IdRubro
            LEFT JOIN subrubro sr ON p.IdSubRubro = sr.IdSubRubro
            LEFT JOIN tipoproducto tp ON p.IdTipoProducto = tp.IdTipoProducto
            WHERE p.CodigoNum = ?";
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta de producto: " . $conexion->error);
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Si no se encuentra ningún producto con ese ID, redirige.
    if ($result->num_rows === 0) {
        $stmt->close();
        $conexion->close();
        redirect('productos/listar.php?error=Producto no encontrado');
    }
    
    // Guarda los datos del producto en el array $producto.
    $producto = $result->fetch_assoc();
    $stmt->close();
    
    // Obtiene los datos para rellenar los menús desplegables (selects) del formulario.
    $result_rubros = $conexion->query("SELECT IdRubro, Descripcion FROM rubros ORDER BY Descripcion");
    $result_subrubros = $conexion->query("SELECT IdSubRubro, Descripcion FROM subrubro ORDER BY Descripcion");
    $result_marcas = $conexion->query("SELECT IdMarca, Marca FROM marca ORDER BY Marca");
    $result_tipos = $conexion->query("SELECT IdTipoProducto, TipoProducto FROM tipoproducto ORDER BY TipoProducto");
    
} catch (Exception $e) {
    error_log("Error al obtener producto para editar: " . $e->getMessage());
    redirect('productos/listar.php?error=Error fatal al cargar el producto');
}

// PROCESAMIENTO DE LA ACTUALIZACIÓN (MÉTODO POST)
// ------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recolección y saneamiento de los datos del formulario.
    $descripcion = trim($_POST['nombre'] ?? '');
    $precio_venta = floatval($_POST['precio'] ?? 0);
    $precio_costo = floatval($_POST['precio_costo'] ?? 0);
    $stock = floatval($_POST['stock'] ?? 0);
    $id_rubro = !empty($_POST['id_rubro']) ? intval($_POST['id_rubro']) : null;
    $id_subrubro = !empty($_POST['id_subrubro']) ? intval($_POST['id_subrubro']) : null;
    $id_marca = !empty($_POST['id_marca']) ? intval($_POST['id_marca']) : null;
    $id_tipo_producto = !empty($_POST['id_tipo_producto']) ? intval($_POST['id_tipo_producto']) : 1;

    // Validaciones del lado del servidor.
    if (empty($descripcion)) {
        $error = "El nombre del producto es obligatorio";
    } elseif ($precio_venta <= 0) {
        $error = "El precio debe ser mayor a 0";
    } elseif ($stock < 0) {
        $error = "El stock no puede ser negativo";
    } else {
        try {
            
            // Prepara la consulta de actualización (UPDATE).
            $sql_update = "UPDATE productos SET 
                            Descripcion = ?, PrecioVenta = ?, PrecioCosto = ?, `100` = ?,
                            IdRubro = ?, IdSubRubro = ?, IdMarca = ?, IdTipoProducto = ?
                        WHERE CodigoNum = ?";
            $stmt_update = $conexion->prepare($sql_update);
            
            if (!$stmt_update) {
                throw new Exception("Error en la preparación de la actualización: " . $conexion->error);
            }
            
            // Vincula los 9 parámetros a la consulta preparada.
            $stmt_update->bind_param("sdddiiiii", 
                $descripcion, $precio_venta, $precio_costo, $stock,
                $id_rubro, $id_subrubro, $id_marca, $id_tipo_producto, $id
            );
            
            // Ejecuta la actualización.
            if ($stmt_update->execute()) {
                $success = "Producto actualizado exitosamente";
                
                // Actualiza el array $producto con los nuevos datos para que se reflejen en el formulario sin recargar la página.
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
                throw new Exception("Error al ejecutar la actualización: " . $stmt_update->error);
            }
            
            $stmt_update->close();
            
        } catch (Exception $e) {
            $error = "Error al actualizar el producto: " . $e->getMessage();
            error_log("Error en actualizar producto: " . $e->getMessage());
        }
    }
}

// INCLUSIÓN DEL ENCABEZADO HTML
// -----------------------------
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

.btn-modern {
    border-radius: 25px;
    padding: 10px 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.form-control, .form-select {
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.info-card {
    background: linear-gradient(135deg, #f8f9ff 0%, #e8ecff 100%);
    border: none;
    border-radius: 15px;
}

.danger-zone {
    background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);
    border: 2px solid #ff6b6b;
    border-radius: 15px;
}

.badge-custom {
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 600;
}

.alert-modern {
    border: none;
    border-radius: 12px;
    border-left: 4px solid;
}
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-pencil-square"></i> Editar Producto</h2>
            <p class="text-muted mb-0">Actualiza la información del producto</p>
        </div>
        <div>
            <a href="<?= $base_url ?>productos/listar.php" class="btn btn-outline-secondary btn-modern me-2">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            <a href="<?= $base_url ?>productos/crear.php" class="btn btn-success btn-modern">
                <i class="bi bi-plus-circle"></i> Nuevo
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card modern-card shadow">
                <div class="card-header gradient-header">
                    <h5 class="mb-0"><i class="bi bi-box-seam"></i> Información del Producto</h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-modern border-success">
                            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-modern border-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                <form method="POST" id="editarForm">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Producto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" 
                               value="<?= htmlspecialchars($producto['Descripcion']) ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Precio de Venta ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="precio" 
                                   value="<?= $producto['PrecioVenta'] ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Precio Costo ($)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="precio_costo" 
                                   value="<?= $producto['PrecioCosto'] ?>">
                        </div>
                        <div class="col-md-4 mb-3">
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

