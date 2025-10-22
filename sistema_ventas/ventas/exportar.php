<?php
// INCLUSIÓN DE ARCHIVO DE CONEXIÓN
// ----------------------------------
require_once('../includes/conexion.php');

// --- LÓGICA DE FILTRADO (similar a historial.php) ---

// 1. OBTENER PARÁMETROS DE FILTRO DE LA URL
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;

// 2. CONSTRUIR LA CONSULTA SQL CON FILTROS
// NOTA: La consulta parece ser inconsistente con el resto de la aplicación.
// Utiliza una tabla `ventas` y campos `id`, `cliente_id` que no coinciden con `salida` y `NroCliente` / `IdSalida`.
// Se comentará el código tal como está, pero esto podría ser un error.
$sql = "SELECT s.IdSalida, c.Nombre AS cliente, s.Fecha, s.Total 
        FROM salida s
        INNER JOIN clientes c ON s.NroCliente = c.NroCliente
        WHERE s.Anulado = 0";

$params = [];
$types = '';

if (!empty($fecha_inicio)) {
    $sql .= " AND DATE(s.Fecha) >= ?";
    $params[] = $fecha_inicio;
    $types .= 's';
}
if (!empty($fecha_fin)) {
    $sql .= " AND DATE(s.Fecha) <= ?";
    $params[] = $fecha_fin;
    $types .= 's';
}
if ($cliente_id > 0) {
    $sql .= " AND s.NroCliente = ?";
    $params[] = $cliente_id;
    $types .= 'i';
}

$sql .= " ORDER BY s.Fecha DESC";

// 3. PREPARAR Y EJECUTAR LA CONSULTA
$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();

// --- GENERACIÓN DEL ARCHIVO EXCEL ---

// 1. CONFIGURAR CABECERAS HTTP PARA LA DESCARGA
// Se le indica al navegador que el contenido es un archivo de Excel y que debe mostrar un diálogo de "Guardar como...".
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="historial_ventas_' . date('Ymd_His') . '.xls"');
header('Cache-Control: max-age=0');

// 2. GENERAR LA SALIDA EN FORMATO DE TABLA HTML
// Excel es capaz de interpretar una tabla HTML simple y convertirla en una hoja de cálculo.
echo "<table border='1'>";
echo "<tr>
        <th>ID Venta</th>
        <th>Cliente</th>
        <th>Fecha</th>
        <th>Total</th>
      </tr>";

while ($fila = $resultado->fetch_assoc()) {
    echo "<tr>
            <td>{$fila['IdSalida']}</td>
            <td>" . htmlspecialchars($fila['cliente']) . "</td>
            <td>" . date('d/m/Y H:i', strtotime($fila['Fecha'])) . "</td>
            <td>" . number_format($fila['Total'], 2) . "</td>
          </tr>";
}

echo "</table>";
exit; // Se termina la ejecución del script después de generar el archivo.
?>
