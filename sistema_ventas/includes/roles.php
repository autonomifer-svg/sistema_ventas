<?php
// roles.php - Definición de roles y permisos

$ROLES_PERMISOS = [
    'admin' => [
        'gestion_usuarios' => true,
        'ver_todas_ventas' => true,
        'editar_productos' => true,
        'eliminar_registros' => true
    ],
    'vendedor' => [
        'gestion_usuarios' => false,
        'ver_todas_ventas' => false,
        'editar_productos' => false,
        'eliminar_registros' => false
    ],
    'inventario' => [
        'gestion_usuarios' => false,
        'ver_todas_ventas' => false,
        'editar_productos' => true,
        'eliminar_registros' => false
    ]
];

function tienePermiso($permiso) {
    global $ROLES_PERMISOS;
    $rol = $_SESSION['user_role'] ?? 'invitado';
    
    return $ROLES_PERMISOS[$rol][$permiso] ?? false;
}
?>