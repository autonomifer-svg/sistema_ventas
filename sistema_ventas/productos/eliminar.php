<?php
require_once('../includes/conexion.php');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Eliminar producto
    $sql = "DELETE FROM productos WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: listar.php?success=Producto eliminado");
    } else {
        header("Location: listar.php?error=" . urlencode($stmt->error));
    }
} else {
    header("Location: listar.php");
}
exit;