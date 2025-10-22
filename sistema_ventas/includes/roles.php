<?php
// roles.php - Definición centralizada de roles y permisos.

// ESTRUCTURA DE ROLES Y PERMISOS
// --------------------------------
// Este array asociativo define qué acciones puede realizar cada rol en el sistema.
// Es una forma flexible de gestionar el control de acceso. Si en el futuro se necesita un nuevo rol
// o un nuevo permiso, solo es necesario modificar este array.
$ROLES_PERMISOS = [
    'admin' => [
        'gestion_usuarios' => true,   // Puede crear, editar y eliminar usuarios.
        'ver_todas_ventas' => true,   // Puede ver las ventas de todos los usuarios.
        'editar_productos' => true,   // Puede editar productos.
        'eliminar_registros' => true    // Permiso general para eliminar registros (ej. clientes, productos).
    ],
    'vendedor' => [
        'gestion_usuarios' => false,
        'ver_todas_ventas' => false, // Solo puede ver sus propias ventas (lógica a implementar).
        'editar_productos' => false,
        'eliminar_registros' => false
    ],
    'inventario' => [
        'gestion_usuarios' => false,
        'ver_todas_ventas' => false,
        'editar_productos' => true,   // Puede gestionar el inventario.
        'eliminar_registros' => false
    ]
];

/**
 * Verifica si el rol del usuario actual tiene un permiso específico.
 * 
 * Esta función permite comprobar de una manera limpia y centralizada si se debe permitir una acción.
 * NOTA: Aunque está definida, esta función no parece ser utilizada en el resto del código, 
 * donde las comprobaciones se hacen directamente con `esAdministrador()`. Integrar esta función
 * haría el sistema de permisos mucho más robusto y fácil de mantener.
 * 
 * @param string $permiso El nombre del permiso que se quiere verificar (ej. 'gestion_usuarios').
 * @return bool Devuelve true si el usuario tiene el permiso, de lo contrario false.
 */
function tienePermiso($permiso) {
    global $ROLES_PERMISOS;
    // Obtiene el rol del usuario de la sesión. Si no existe, se le asigna un rol de 'invitado'.
    $rol = $_SESSION['user_role'] ?? 'invitado';
    
    // Devuelve el valor del permiso para el rol del usuario. Si el rol o el permiso no existen, devuelve false.
    return $ROLES_PERMISOS[$rol][$permiso] ?? false;
}
?>
