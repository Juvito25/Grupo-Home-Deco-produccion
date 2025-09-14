<?php
/**
 * Template Part para la barra lateral del panel de sector.
 * V2 - Ahora es dinámico y muestra enlaces para cada rol de líder del usuario.
 */

$current_user = wp_get_current_user();
$user_roles = (array) $current_user->roles;

// Obtener la URL base de la página del panel de tareas
// Es importante que el slug de la página sea 'panel-de-tareas' o el que corresponda.
// $dashboard_page = get_page_by_path('panel-de-tareas');
$dashboard_page = get_page_by_path('mis-tareas');
$dashboard_url = $dashboard_page ? get_permalink($dashboard_page->ID) : home_url('/');

// Identificar el sector activo desde la URL
$active_sector = isset($_GET['sector']) ? sanitize_text_field($_GET['sector']) : '';

// Mapeo de claves de sector a nombres legibles
$sector_display_map = [ 
    'carpinteria' => 'Carpintería', 
    'corte' => 'Corte', 
    'costura' => 'Costura', 
    'tapiceria' => 'Tapicería', 
    'embalaje' => 'Embalaje', 
    'logistica' => 'Logística',
];

// Identificar todos los roles de líder del usuario para construir el menú
$leader_roles = [];
$first_leader_key = '';
foreach ($user_roles as $role) {
    if (strpos($role, 'lider_') !== false) {
        $sector_key = str_replace('lider_', '', $role);
        $leader_roles[$sector_key] = $sector_display_map[$sector_key] ?? ucfirst($sector_key);
        if (empty($first_leader_key)) {
            $first_leader_key = $sector_key;
        }
    }
}

// Si el sector activo no está definido en la URL, el primero de la lista será el activo.
if (empty($active_sector)) {
    $active_sector = $first_leader_key;
}
?>
<aside class="ghd-sidebar">
    <div class="sidebar-header">
        <h3 class="logo">Mi Puesto</h3>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <?php if (!empty($leader_roles)) : ?>
                <!-- Bucle para mostrar un enlace por cada rol de líder -->
                <?php foreach ($leader_roles as $sector_key => $sector_name) : ?>
                    <?php
                    $link_url = add_query_arg('sector', $sector_key, $dashboard_url);
                    $li_class = ($active_sector === $sector_key) ? 'active' : '';
                    ?>
                    <li class="<?php echo esc_attr($li_class); ?>">
                        <a href="<?php echo esc_url($link_url); ?>">
                            <i class="fa-solid fa-list-check"></i>
                            <span>Tareas de <?php echo esc_html($sector_name); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback si el usuario no tiene roles de líder (aunque no debería llegar aquí) -->
                <li class="active">
                    <a href="<?php echo esc_url($dashboard_url); ?>">
                        <i class="fa-solid fa-list-check"></i>
                        <span>Mis Tareas</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Enlace para Cerrar Sesión -->
            <li style="margin-top: auto; padding-top: 1rem; border-top: 1px solid #555;">
                <a href="<?php echo wp_logout_url(home_url()); ?>">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>