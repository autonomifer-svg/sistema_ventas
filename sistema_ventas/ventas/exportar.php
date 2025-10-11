<?php
require_once('../includes/conexion.php');

// Obtener parámetros de filtro (igual que en historial.php)
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;

// Construir consulta con filtros
$sql = "SELECT v.id, c.nombre AS cliente, v.fecha, v.total 
        FROM ventas v
        INNER JOIN clientes c ON v.cliente_id = c.id
        WHERE 1=1";

$params = [];
$types = '';

if (!empty($fecha_inicio)) {
    $sql .= " AND DATE(v.fecha) >= ?";
    $params[] = $fecha_inicio;
    $types .= 's';
}

if (!empty($fecha_fin)) {
    $sql .= " AND DATE(v.fecha) <= ?";
    $params[] = $fecha_fin;
    $types .= 's';
}

if ($cliente_id > 0) {
    $sql .= " AND v.cliente_id = ?";
    $params[] = $cliente_id;
    $types .= 'i';
}

$sql .= " ORDER BY v.fecha DESC";

// Preparar y ejecutar consulta
$stmt = $conexion->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$resultado = $stmt->get_result();

// Cabeceras para descarga de Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="historial_ventas_' . date('Ymd_His') . '.xls"');
header('Cache-Control: max-age=0');

// Salida para Excel
echo "<table border='1'>";
echo "<tr>
        <th>ID Venta</th>
        <th>Cliente</th>
        <th>Fecha</th>
        <th>Total</th>
      </tr>";

while ($fila = $resultado->fetch_assoc()) {
    echo "<tr>
            <td>{$fila['id']}</td>
            <td>{$fila['cliente']}</td>
            <td>" . date('d/m/Y H:i', strtotime($fila['fecha'])) . "</td>
            <td>$" . number_format($fila['total'], 2) . "</td>
          </tr>";
}

echo "</table>";
exit;
?>
