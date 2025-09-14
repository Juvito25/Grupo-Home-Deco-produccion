<?php
/**
 * Template Part para la barra lateral del panel de sector.
 * V3 - Menú ordenado según el flujo de producción.
 */

$current_user = wp_get_current_user();
$user_roles = (array) $current_user->roles;

$dashboard_page = get_page_by_path('mis-tareas');
$dashboard_url = $dashboard_page ? get_permalink($dashboard_page->ID) : home_url('/');

$active_sector = isset($_GET['sector']) ? strtolower(sanitize_text_field($_GET['sector'])) : '';

// 1. Definir el orden correcto y los nombres de los sectores
$production_flow_order = [
    'carpinteria' => 'Carpintería',
    'corte'       => 'Corte',
    'costura'     => 'Costura',
    'tapiceria'   => 'Tapicería',
    'embalaje'    => 'Embalaje',
    'logistica'   => 'Logística',
];

// 2. Filtrar y ordenar los roles del usuario según el flujo de producción
$user_leader_sectors = [];
foreach ($production_flow_order as $sector_key => $sector_name) {
    if (in_array('lider_' . $sector_key, $user_roles)) {
        $user_leader_sectors[$sector_key] = $sector_name;
    }
}

// Si el sector activo no está definido, el primero de la lista ordenada será el activo.
if (empty($active_sector) && !empty($user_leader_sectors)) {
    $active_sector = key($user_leader_sectors);
}
?>
<aside class="ghd-sidebar">
    <div class="sidebar-header">
        <h3 class="logo">Mi Puesto</h3>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <?php if (!empty($user_leader_sectors)) : ?>
                <!-- 3. Construir el menú usando la lista ordenada -->
                <?php foreach ($user_leader_sectors as $sector_key => $sector_name) : ?>
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
            <?php endif; ?>

            <li style="margin-top: auto; padding-top: 1rem; border-top: 1px solid #555;">
                <a href="<?php echo wp_logout_url(home_url()); ?>">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>