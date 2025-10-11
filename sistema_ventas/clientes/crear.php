<?php
// Mover el procesamiento al principio del archivo
require_once(__DIR__ . '/../includes/config.php');
require_once(__DIR__ . '/../includes/conexion.php');
requireAuth();

$errores = [];
$mensaje = '';

// Obtener datos necesarios para los selects
$sql_ciudades = "SELECT IdCiudad, Ciudad FROM ciudad ORDER BY Ciudad";
$result_ciudades = $conexion->query($sql_ciudades);

$sql_tipos_cliente = "SELECT IdTipoCliente, TipoCliente FROM tipocliente ORDER BY TipoCliente";
$result_tipos_cliente = $conexion->query($sql_tipos_cliente);

$sql_categorias = "SELECT IdCategoriaCliente, CategoriaCliente FROM categoriacliente ORDER BY CategoriaCliente";
$result_categorias = $conexion->query($sql_categorias);

$sql_zonas = "SELECT IdZona, Zona FROM zonas ORDER BY Zona";
$result_zonas = $conexion->query($sql_zonas);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $razon_social = trim($_POST['razon_social'] ?? $nombre); // Si no se proporciona, usar el nombre
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ruc = trim($_POST['ruc'] ?? '');
    $id_ciudad = !empty($_POST['id_ciudad']) ? intval($_POST['id_ciudad']) : null;
    $id_tipo_cliente = !empty($_POST['id_tipo_cliente']) ? intval($_POST['id_tipo_cliente']) : 1; // Default
    $id_categoria = !empty($_POST['id_categoria']) ? intval($_POST['id_categoria']) : 1; // Default
    $id_zona = !empty($_POST['id_zona']) ? intval($_POST['id_zona']) : null;

    // Validación básica
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    }
    
    if (strlen($nombre) > 100) {
        $errores[] = "El nombre no puede exceder 100 caracteres";
    }
    
    if (!empty($telefono) && strlen($telefono) > 70) {
        $errores[] = "El teléfono no puede exceder 70 caracteres";
    }
    
    if (!empty($direccion) && strlen($direccion) > 150) {
        $errores[] = "La dirección no puede exceder 150 caracteres";
    }
    
    // Si no hay errores, insertar en la base de datos
    if (empty($errores)) {
        // Obtener el siguiente NroCliente
        $sql_max = "SELECT COALESCE(MAX(NroCliente), 0) + 1 as siguiente FROM clientes";
        $result_max = $conexion->query($sql_max);
        $nro_cliente = $result_max->fetch_assoc()['siguiente'];
        
        // Obtener IdUsuario de la sesión (usar 1 como default si no existe)
        $id_usuario = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
        
        // Convertir NULL a valores válidos para evitar errores
        if ($id_ciudad === null) $id_ciudad = 0;
        if ($id_zona === null) $id_zona = 0;
        
        $sql = "INSERT INTO clientes (
                    NroCliente, Nombre, RazonSocial, Ruc, Direccion, Telefono, 
                    IdCiudad, IdTipoCliente, IdCategoriaCliente, IdZona, IdUsuario, 
                    Plazo, LimiteCredito, Inactivo
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 0)";
        
        $stmt = $conexion->prepare($sql);
        
        if (!$stmt) {
            $errores[] = "Error al preparar consulta: " . $conexion->error;
        } else {
            $stmt->bind_param(
                "isssssiiiii", 
                $nro_cliente, $nombre, $razon_social, $ruc, $direccion, $telefono,
                $id_ciudad, $id_tipo_cliente, $id_categoria, $id_zona, $id_usuario
            );
        }
        
        if ($stmt->execute()) {
            $_SESSION['mensaje_exito'] = "Cliente registrado exitosamente (ID: $nro_cliente)";
            
            // Redirigir ANTES de enviar cualquier salida
            header("Location: listar.php");
            exit;
        } else {
            $errores[] = "Error al registrar el cliente: " . $stmt->error;
        }
    }
}

// Ahora incluir el header después del procesamiento
include(__DIR__ . '/../includes/header.php');
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Nuevo Cliente</h2>
        <a href="listar.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        <div class="alert alert-success"><?= $_SESSION['mensaje_exito'] ?></div>
        <?php unset($_SESSION['mensaje_exito']); ?>
    <?php endif; ?>
    
    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
            <strong>Por favor, corrige los siguientes errores:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-person-plus"></i> Registrar Cliente</h5>
        </div>
        <div class="card-body">
            <form method="POST" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= in_array('El nombre es obligatorio', $errores) ? 'is-invalid' : '' ?>" 
                               name="nombre" required maxlength="100"
                               placeholder="Ej: Juan Pérez García" 
                               value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>">
                        <div class="form-text">Campo obligatorio. Máximo 100 caracteres.</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Razón Social</label>
                        <input type="text" class="form-control" name="razon_social" maxlength="100"
                               placeholder="Ej: Empresa S.A." 
                               value="<?= isset($_POST['razon_social']) ? htmlspecialchars($_POST['razon_social']) : '' ?>">
                        <div class="form-text">Si no se especifica, se usará el nombre.</div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">RUC</label>
                        <input type="text" class="form-control" name="ruc" maxlength="30"
                               placeholder="Ej: 80012345-6" 
                               value="<?= isset($_POST['ruc']) ? htmlspecialchars($_POST['ruc']) : '' ?>">
                        <div class="form-text">Opcional.</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" name="telefono" maxlength="70"
                               placeholder="Ej: +595 21 123456" 
                               value="<?= isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : '' ?>">
                        <div class="form-text">Opcional. Máximo 70 caracteres.</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Dirección</label>
                    <input type="text" class="form-control" name="direccion" maxlength="150"
                           placeholder="Ej: Calle Principal #123" 
                           value="<?= isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : '' ?>">
                    <div class="form-text">Opcional. Máximo 150 caracteres.</div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Ciudad</label>
                        <select class="form-select" name="id_ciudad">
                            <option value="">Seleccionar ciudad...</option>
                            <?php while ($ciudad = $result_ciudades->fetch_assoc()): ?>
                                <option value="<?= $ciudad['IdCiudad'] ?>"
                                    <?= (isset($_POST['id_ciudad']) && $_POST['id_ciudad'] == $ciudad['IdCiudad']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ciudad['Ciudad']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="form-text">Opcional.</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Zona</label>
                        <select class="form-select" name="id_zona">
                            <option value="">Seleccionar zona...</option>
                            <?php while ($zona = $result_zonas->fetch_assoc()): ?>
                                <option value="<?= $zona['IdZona'] ?>"
                                    <?= (isset($_POST['id_zona']) && $_POST['id_zona'] == $zona['IdZona']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($zona['Zona']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="form-text">Opcional.</div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tipo de Cliente</label>
                        <select class="form-select" name="id_tipo_cliente">
                            <?php 
                            $result_tipos_cliente->data_seek(0); // Reset pointer
                            while ($tipo = $result_tipos_cliente->fetch_assoc()): 
                            ?>
                                <option value="<?= $tipo['IdTipoCliente'] ?>"
                                    <?= (isset($_POST['id_tipo_cliente']) && $_POST['id_tipo_cliente'] == $tipo['IdTipoCliente']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tipo['TipoCliente']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="form-text">Se usará el primer tipo si no se especifica.</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Categoría</label>
                        <select class="form-select" name="id_categoria">
                            <?php 
                            $result_categorias->data_seek(0); // Reset pointer
                            while ($categoria = $result_categorias->fetch_assoc()): 
                            ?>
                                <option value="<?= $categoria['IdCategoriaCliente'] ?>"
                                    <?= (isset($_POST['id_categoria']) && $_POST['id_categoria'] == $categoria['IdCategoriaCliente']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['CategoriaCliente']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="form-text">Se usará la primera categoría si no se especifica.</div>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="reset" class="btn btn-outline-secondary" onclick="return confirm('¿Estás seguro de limpiar el formulario?')">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Registrar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Información adicional -->
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Información</h6>
        </div>
        <div class="card-body">
            <small class="text-muted">
                <strong>Campos requeridos:</strong> Solo el nombre es obligatorio.<br>
                <strong>Valores por defecto:</strong> Si no se especifica razón social, se usará el nombre del cliente.<br>
                <strong>Fecha de registro:</strong> Se asignará automáticamente la fecha y hora actual.
            </small>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../includes/footer.php'); ?>
