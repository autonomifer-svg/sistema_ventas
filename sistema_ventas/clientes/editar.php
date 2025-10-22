<?php
// INCLUSIÓN DE ARCHIVOS Y VERIFICACIÓN DE SEGURIDAD
// --------------------------------------------------
require_once(__DIR__ . '/../includes/config.php');
requireAuth();
require_once(__DIR__ . '/../includes/conexion.php');

// OBTENCIÓN Y VALIDACIÓN DEL ID DEL CLIENTE
// -----------------------------------------
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header("Location: listar.php?error=ID de cliente no válido");
    exit;
}

// OBTENCIÓN DE DATOS DEL CLIENTE PARA PRE-RELLENAR EL FORMULARIO
// -------------------------------------------------------------
$sql = "SELECT * FROM clientes WHERE NroCliente = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();

// Si no se encuentra el cliente, redirigir al listado.
if (!$cliente) {
    header("Location: listar.php?error=Cliente no encontrado");
    exit;
}

// OBTENCIÓN DE DATOS PARA LOS MENÚS DESPLEGABLES (SELECTS)
// -------------------------------------------------------
$result_ciudades = $conexion->query("SELECT IdCiudad, Ciudad FROM ciudad ORDER BY Ciudad");
$result_tipos_cliente = $conexion->query("SELECT IdTipoCliente, TipoCliente FROM tipocliente ORDER BY TipoCliente");
$result_categorias = $conexion->query("SELECT IdCategoriaCliente, CategoriaCliente FROM categoriacliente ORDER BY CategoriaCliente");
$result_zonas = $conexion->query("SELECT IdZona, Zona FROM zonas ORDER BY Zona");

// PROCESAMIENTO DE LA ACTUALIZACIÓN (SI SE ENVÍA POR POST)
// -------------------------------------------------------
$errores = [];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. RECOLECCIÓN Y LIMPIEZA DE DATOS DEL FORMULARIO
    $nombre = trim($_POST['nombre'] ?? '');
    $razon_social = trim($_POST['razon_social'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ruc = trim($_POST['ruc'] ?? '');
    $id_ciudad = !empty($_POST['id_ciudad']) ? intval($_POST['id_ciudad']) : null;
    $id_tipo_cliente = !empty($_POST['id_tipo_cliente']) ? intval($_POST['id_tipo_cliente']) : null;
    $id_categoria = !empty($_POST['id_categoria']) ? intval($_POST['id_categoria']) : null;
    $id_zona = !empty($_POST['id_zona']) ? intval($_POST['id_zona']) : null;

    // 2. VALIDACIÓN DE DATOS
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    }

    // 3. ACTUALIZACIÓN EN LA BASE DE DATOS (SI NO HAY ERRORES)
    if (empty($errores)) {
        try {
            // Preparar la consulta de actualización para prevenir inyecciones SQL.
            $sql_update = "UPDATE clientes SET 
                                Nombre = ?, RazonSocial = ?, Telefono = ?, Direccion = ?, Ruc = ?,
                                IdCiudad = ?, IdTipoCliente = ?, IdCategoriaCliente = ?, IdZona = ?
                            WHERE NroCliente = ?";
            
            $stmt_update = $conexion->prepare($sql_update);
            if (!$stmt_update) {
                throw new Exception("Error al preparar la consulta: " . $conexion->error);
            }

            // Vincular los 10 parámetros a la consulta preparada.
            $stmt_update->bind_param("sssssiiiii", 
                $nombre, $razon_social, $telefono, $direccion, $ruc,
                $id_ciudad, $id_tipo_cliente, $id_categoria, $id_zona, $id
            );
            
            // Ejecutar la actualización.
            if ($stmt_update->execute()) {
                $mensaje = "Cliente actualizado exitosamente";
                
                // Actualizar el array local $cliente para que los cambios se reflejen en el formulario inmediatamente.
                $cliente['Nombre'] = $nombre;
                $cliente['RazonSocial'] = $razon_social;
                // ... (y así sucesivamente para los demás campos)

            } else {
                throw new Exception("Error al ejecutar la actualización: " . $stmt_update->error);
            }
        } catch (Exception $e) {
            $errores[] = $e->getMessage();
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

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-pencil-square"></i> Editar Cliente</h2>
            <p class="text-muted mb-0">Modifica la información del cliente</p>
        </div>
        <a href="listar.php" class="btn btn-outline-secondary btn-modern">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card modern-card shadow-sm">
                <div class="card-header gradient-header">
                    <h5 class="mb-0"><i class="bi bi-person-badge"></i> Información del Cliente</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($errores)): ?>
                        <div class="alert alert-danger alert-modern border-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Errores encontrados:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errores as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success alert-modern border-success">
                            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($mensaje) ?>
                        </div>
                    <?php endif; ?>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" required
                               value="<?= htmlspecialchars($cliente['Nombre']) ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Razón Social</label>
                        <input type="text" class="form-control" name="razon_social"
                               value="<?= htmlspecialchars($cliente['RazonSocial'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">RUC</label>
                        <input type="text" class="form-control" name="ruc"
                               value="<?= htmlspecialchars($cliente['Ruc'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" name="telefono" 
                               value="<?= htmlspecialchars($cliente['Telefono'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Dirección</label>
                    <input type="text" class="form-control" name="direccion" 
                           value="<?= htmlspecialchars($cliente['Direccion'] ?? '') ?>">
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Ciudad</label>
                        <select class="form-select" name="id_ciudad">
                            <option value="">Seleccionar ciudad...</option>
                            <?php while ($ciudad = $result_ciudades->fetch_assoc()): ?>
                                <option value="<?= $ciudad['IdCiudad'] ?>"
                                    <?= ($cliente['IdCiudad'] == $ciudad['IdCiudad']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ciudad['Ciudad']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Zona</label>
                        <select class="form-select" name="id_zona">
                            <option value="">Seleccionar zona...</option>
                            <?php while ($zona = $result_zonas->fetch_assoc()): ?>
                                <option value="<?= $zona['IdZona'] ?>"
                                    <?= ($cliente['IdZona'] == $zona['IdZona']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($zona['Zona']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tipo de Cliente</label>
                        <select class="form-select" name="id_tipo_cliente">
                            <option value="">Seleccionar tipo...</option>
                            <?php while ($tipo = $result_tipos_cliente->fetch_assoc()): ?>
                                <option value="<?= $tipo['IdTipoCliente'] ?>"
                                    <?= ($cliente['IdTipoCliente'] == $tipo['IdTipoCliente']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tipo['TipoCliente']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Categoría</label>
                        <select class="form-select" name="id_categoria">
                            <option value="">Seleccionar categoría...</option>
                            <?php while ($categoria = $result_categorias->fetch_assoc()): ?>
                                <option value="<?= $categoria['IdCategoriaCliente'] ?>"
                                    <?= ($cliente['IdCategoriaCliente'] == $categoria['IdCategoriaCliente']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['CategoriaCliente']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="listar.php" class="btn btn-outline-secondary me-md-2">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Información del cliente -->
    <div class="card shadow-sm mt-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Información</h6>
        </div>
        <div class="card-body">
            <p><strong>ID Cliente:</strong> <?= $cliente['NroCliente'] ?></p>
            <p><strong>Fecha de Registro:</strong> <?= isset($cliente['FechaAct']) ? date('d/m/Y H:i', strtotime($cliente['FechaAct'])) : 'No disponible' ?></p>
            <p><strong>Estado:</strong> <?= $cliente['Inactivo'] == 0 ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>' ?></p>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../includes/footer.php'); ?>
