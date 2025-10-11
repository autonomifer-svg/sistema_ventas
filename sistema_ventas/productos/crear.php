<?php
require_once(__DIR__ . '/../includes/config.php');
requireAuth();

$success = '';
$error = '';

// Obtener datos para los selects
$conexion = conectarDB();

$sql_rubros = "SELECT IdRubro, Descripcion FROM rubros ORDER BY Descripcion";
$result_rubros = $conexion->query($sql_rubros);

$sql_subrubros = "SELECT IdSubRubro, Descripcion FROM subrubro ORDER BY Descripcion";
$result_subrubros = $conexion->query($sql_subrubros);

$sql_marcas = "SELECT IdMarca, Marca FROM marca ORDER BY Marca";
$result_marcas = $conexion->query($sql_marcas);

$sql_tipos = "SELECT IdTipoProducto, TipoProducto FROM tipoproducto ORDER BY TipoProducto";
$result_tipos = $conexion->query($sql_tipos);

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
            // Obtener el siguiente CodigoNum
            $sql_max = "SELECT COALESCE(MAX(CodigoNum), 0) + 1 as siguiente FROM productos";
            $result_max = $conexion->query($sql_max);
            $codigo_num = $result_max->fetch_assoc()['siguiente'];
            
            // Generar código automático
            $codigo = "PROD-" . str_pad($codigo_num, 6, '0', STR_PAD_LEFT);
            
            $sql = "INSERT INTO productos (
                        CodigoNum, Codigo, Descripcion, PrecioVenta, PrecioCosto,
                        IdTipoProducto, IdRubro, IdSubRubro, IdMarca,
                        `100`, Suspendido, CalcularStock
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1)";
            
            $stmt = $conexion->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Error en la preparación: " . $conexion->error);
            }
            
            // PrecioCosto = PrecioVenta por defecto (puedes ajustarlo)
            $precio_costo = $precio_venta * 0.7; // Asumiendo 30% de margen
            
            $stmt->bind_param("issddiiid", 
                $codigo_num, $codigo, $descripcion, $precio_venta, $precio_costo,
                $id_tipo_producto, $id_rubro, $id_subrubro, $id_marca, $stock
            );
            
            if ($stmt->execute()) {
                $success = "Producto '$descripcion' creado exitosamente (Código: $codigo)";
                
                // Log de éxito
                error_log("Producto creado: CodigoNum=$codigo_num, Descripcion='$descripcion'");
                
                // Limpiar formulario
                $descripcion = '';
                $precio_venta = $stock = 0;
            } else {
                throw new Exception("Error al ejecutar: " . $stmt->error);
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $error = "Error al crear el producto: " . $e->getMessage();
            error_log("Error en crear producto: " . $e->getMessage());
        }
    }
}

include(__DIR__ . '/../includes/header.php');
?>

<style>
.gradient-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    margin-bottom: 2rem;
}

.form-card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
}

.form-card-header {
    background: linear-gradient(135deg, #f8f9ff 0%, #e8ecff 100%);
    border-bottom: 3px solid #667eea;
    padding: 1.5rem;
}

.modern-input {
    border: 2px solid #e0e6ff;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    transition: all 0.3s ease;
    background-color: #fafbff;
}

.modern-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    background-color: white;
    transform: translateY(-1px);
}

.form-label {
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.btn-primary-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.btn-primary-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.btn-secondary-modern {
    background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    color: #4a5568;
    transition: all 0.3s ease;
}

.btn-secondary-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    color: #2d3748;
}

.tips-card {
    border: none;
    border-radius: 20px;
    background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border-left: 5px solid #667eea;
}

.tips-card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0;
    padding: 1rem 1.5rem;
}

.tip-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: flex-start;
}

.tip-item:last-child {
    border-bottom: none;
}

.tip-icon {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    margin-right: 1rem;
    flex-shrink: 0;
    margin-top: 2px;
}

.alert-modern {
    border: none;
    border-radius: 12px;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
}

.alert-success-modern {
    background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
    border-left: 4px solid #48bb78;
    color: #2f855a;
}

.alert-danger-modern {
    background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
    border-left: 4px solid #f56565;
    color: #c53030;
}

.input-icon {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #a0aec0;
    z-index: 5;
}

.form-section {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
</style>

<!-- Header con gradiente -->
<div class="gradient-header text-white p-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-2"><i class="bi bi-plus-circle me-2"></i>Nuevo Producto</h2>
            <p class="mb-0 opacity-75">Agrega un nuevo producto a tu inventario</p>
        </div>
        <a href="<?= $base_url ?>productos/listar.php" class="btn btn-light btn-lg">
            <i class="bi bi-arrow-left me-2"></i>Volver a Lista
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card form-card">
            <div class="form-card-header">
                <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Información del Producto</h5>
            </div>
            <div class="card-body form-section">
                <?php if ($success): ?>
                    <div class="alert alert-success-modern alert-dismissible fade show">
                        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger-modern alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" id="productoForm">
                    <div class="mb-4">
                        <label class="form-label">Nombre del Producto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control modern-input" name="nombre" 
                               value="<?= htmlspecialchars($descripcion ?? '') ?>" 
                               placeholder="Ej: Detergente Líquido Premium" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Precio de Venta ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control modern-input" name="precio" 
                                   value="<?= $precio_venta > 0 ? $precio_venta : '' ?>" 
                                   placeholder="0.00" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Stock Inicial <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control modern-input" name="stock" 
                                   value="<?= $stock > 0 ? $stock : '' ?>" 
                                   placeholder="0" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Tipo de Producto</label>
                            <select class="form-select modern-input" name="id_tipo_producto">
                                <?php while ($tipo = $result_tipos->fetch_assoc()): ?>
                                    <option value="<?= $tipo['IdTipoProducto'] ?>">
                                        <?= htmlspecialchars($tipo['TipoProducto']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Marca</label>
                            <select class="form-select modern-input" name="id_marca">
                                <option value="">Seleccionar marca...</option>
                                <?php while ($marca = $result_marcas->fetch_assoc()): ?>
                                    <option value="<?= $marca['IdMarca'] ?>">
                                        <?= htmlspecialchars($marca['Marca']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-3 justify-content-end">
                        <button type="reset" class="btn btn-secondary-modern">
                            <i class="bi bi-arrow-clockwise me-2"></i>Limpiar
                        </button>
                        <button type="submit" class="btn btn-primary-modern">
                            <i class="bi bi-check-lg me-2"></i>Guardar Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card tips-card">
            <div class="tips-card-header">
                <h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Consejos Útiles</h6>
            </div>
            <div class="card-body">
                <div class="tip-item">
                    <div class="tip-icon">
                        <i class="bi bi-check"></i>
                    </div>
                    <div>
                        <small><strong>Código automático:</strong> El sistema generará un código único para el producto</small>
                    </div>
                </div>
                
                <div class="tip-item">
                    <div class="tip-icon">
                        <i class="bi bi-check"></i>
                    </div>
                    <div>
                        <small><strong>Precio de costo:</strong> Se calculará automáticamente con un margen del 30%</small>
                    </div>
                </div>
                
                <div class="tip-item">
                    <div class="tip-icon">
                        <i class="bi bi-check"></i>
                    </div>
                    <div>
                        <small><strong>Stock inicial:</strong> Puedes empezar con 0 si aún no tienes inventario</small>
                    </div>
                </div>
                
                <div class="tip-item">
                    <div class="tip-icon">
                        <i class="bi bi-check"></i>
                    </div>
                    <div>
                        <small><strong>Campos opcionales:</strong> Rubro, sub-rubro y marca pueden dejarse en blanco</small>
                    </div>
                </div>
                
                <div class="tip-item">
                    <div class="tip-icon">
                        <i class="bi bi-check"></i>
                    </div>
                    <div>
                        <small><strong>Información completa:</strong> Todos los campos marcados con * son obligatorios</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-4" style="border: none; border-radius: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="card-body text-center">
                <i class="bi bi-graph-up" style="font-size: 2.5rem; margin-bottom: 1rem;"></i>
                <h5>¡Producto Nuevo!</h5>
                <p class="mb-0 opacity-75">Una vez guardado, podrás gestionarlo desde el inventario</p>
            </div>
        </div>
    </div>
</div>

<script>
// Validación del formulario en el cliente
document.getElementById('productoForm').addEventListener('submit', function(e) {
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

// Efectos visuales mejorados
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    const inputs = document.querySelectorAll('.modern-input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });
});
</script>

<?php 
$conexion->close();
include(__DIR__ . '/../includes/footer.php'); 
?></select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Rubro</label>
                            <select class="form-select modern-input" name="id_rubro">
                                <option value="">Seleccionar rubro...</option>
                                <?php while ($rubro = $result_rubros->fetch_assoc()): ?>
                                    <option value="<?= $rubro['IdRubro'] ?>">
                                        <?= htmlspecialchars($rubro['Descripcion']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Sub-Rubro</label>
                            <select class="form-select modern-input" name="id_subrubro">
                                <option value="">Seleccionar sub-rubro...</option>
                                <?php while ($subrubro = $result_subrubros->fetch_assoc()): ?>
                                    <option value="<?= $subrubro['IdSubRubro'] ?>">
                                        <?= htmlspecialchars($subrubro['Descripcion']) ?>
                                    </option>
                                <?php endwhile; ?>
