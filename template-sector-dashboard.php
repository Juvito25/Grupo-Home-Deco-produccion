<?php
/*
Template Name: Panel de Tareas (Sector) V4 - Multiroles Corregido
*/

// 1. Seguridad Indiscutible: Si el usuario no está logueado, se va a la página de login. Fin del script.
if ( ! is_user_logged_in() ) {
    auth_redirect();
    exit;
}

$current_user = wp_get_current_user();
$user_roles = (array) $current_user->roles;
$requested_sector = isset($_GET['sector']) ? sanitize_text_field($_GET['sector']) : '';

// 2. Determinar TODOS los sectores a los que el usuario tiene acceso
$user_allowed_sectors = [];
$is_leader_user = false; 

foreach ($user_roles as $role) {
    if (strpos($role, 'lider_') === 0) {
        $user_allowed_sectors[] = str_replace('lider_', '', $role);
        $is_leader_user = true; // El usuario tiene al menos un rol de líder
    } elseif (strpos($role, 'operario_') === 0) {
        $user_allowed_sectors[] = str_replace('operario_', '', $role);
    }
}
$user_allowed_sectors = array_unique($user_allowed_sectors); // Sectores únicos permitidos

// El sector de destino por defecto es el primero que tenga en sus roles.
$default_sector = $user_allowed_sectors[0] ?? ''; 

// 3. Lógica de Redirección y Validación
if ( current_user_can('manage_options') ) {
    // Caso A: Administrador
    if (empty($requested_sector) || !array_key_exists($requested_sector, ghd_get_sectores())) {
        // Redirige al panel principal si no hay sector válido.
        wp_redirect(home_url('/panel-de-control/'));
        exit;
    }
    $base_sector_key = $requested_sector;
    $is_leader_for_rendering = true; // El admin siempre puede asignar.

} else {
    // Caso B: Líder u Operario
    
    // Si no tiene roles de producción, lo enviamos a la home (debería ir al panel-de-ventas/fletero si esos templates no redirigieron aquí)
    if (empty($default_sector)) {
        wp_redirect(home_url());
        exit;
    }
    
    // Si no se solicitó un sector, o el sector solicitado NO está en la lista de sectores permitidos,
    // lo forzamos a ir al primer sector en su lista ($default_sector).
    if (empty($requested_sector) || !in_array($requested_sector, $user_allowed_sectors)) {
        wp_redirect(add_query_arg('sector', $default_sector, get_permalink()));
        exit;
    }
    
    // Si llegó aquí, está en la URL correcta de un sector al que tiene acceso.
    $base_sector_key = $requested_sector;
    
    // Si tiene un rol de líder (aunque sea de otro sector), puede ver los selectores.
    // Usamos el rol de líder del usuario, NO si el sector actual es líder.
    $is_leader_for_rendering = $is_leader_user;
}

// 4. Configuración final para la UI
get_header();

$mapa_roles = ghd_get_mapa_roles_a_campos();
$campo_estado = 'estado_' . $base_sector_key;

$sector_display_map = ghd_get_sectores(); 
$sector_name = $sector_display_map[$base_sector_key] ?? ucfirst($base_sector_key);

$operarios_del_sector = [];
if ($is_leader_for_rendering) {
    // La lista de operarios ahora incluye todos los operarios del sector BASE_SECTOR_KEY (no todos los sectores permitidos).
    $operarios_del_sector = get_users([
        'role__in' => ['lider_' . $base_sector_key, 'operario_' . $base_sector_key], 
        'orderby'  => 'display_name',
        'order'    => 'ASC'
    ]);
}

add_filter('body_class', function($classes) {
    $classes[] = 'is-sector-dashboard-panel';
    return $classes;
});

// Los KPIs y la consulta de pedidos inicial
$sector_kpi_data = ghd_calculate_sector_kpis($campo_estado);
$pedidos_query_args = [
    'post_type'      => 'orden_produccion', 
    'posts_per_page' => -1, 
    'meta_query'     => [['key' => $campo_estado, 'value' => ['Pendiente', 'En Progreso'], 'compare' => 'IN']]
];

// Si NO es admin y el sector solicitado NO es un sector que el usuario deba supervisar (es decir, es un operario normal), se aplica el filtro por asignación.
// PERO, si es un líder (is_leader_user es true), puede ver todas las tareas de su sector, aunque esté mirando otro sector.
if (!current_user_can('manage_options') && !$is_leader_user) {
    $asignado_a_field = str_replace('estado_', 'asignado_a_', $campo_estado);
    $pedidos_query_args['meta_query'][] = ['key' => $asignado_a_field, 'value' => $current_user->ID, 'compare' => '='];
}

$pedidos_query = new WP_Query($pedidos_query_args);
?>

<div class="ghd-app-wrapper">
    <?php 
    // Pasamos los sectores permitidos al sidebar para que solo muestre las pestañas correctas
    get_template_part('template-parts/sidebar-sector', null, ['allowed_sectors' => $user_allowed_sectors, 'current_sector' => $base_sector_key]); 
    ?>

    <main class="ghd-main-content" data-campo-estado="<?php echo esc_attr($campo_estado); ?>">
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <?php if (current_user_can('manage_options')) :
                    $sectores_page_admin = get_page_by_path('sectores');
                    $back_button_url = $sectores_page_admin ? get_permalink($sectores_page_admin->ID) : home_url('/panel-de-control/');
                ?>
                    <a href="<?php echo esc_url($back_button_url); ?>" class="ghd-btn ghd-btn-secondary ghd-btn-small" style="margin-right: 15px;">
                        <i class="fa-solid fa-arrow-left"></i> Volver a Sectores
                    </a>
                <?php endif; ?>
                <h2>Tareas de <?php echo esc_html($sector_name); ?></h2>
            </div>
            <div class="header-actions">
                <button id="ghd-refresh-tasks" class="ghd-btn ghd-btn-secondary"><i class="fa-solid fa-sync"></i> <span>Refrescar</span></button>
            </div>
        </header>

        <div class="ghd-filters-wrapper" style="margin-bottom: 1.5rem;">
            <div class="filter-group" style="flex-grow: 2;">
                <label for="ghd-search-sector">Buscar Tarea</label>
                <input type="search" id="ghd-search-sector" placeholder="Código, cliente, producto..." style="width: 100%;">
            </div>
            <div class="filter-group" style="flex-grow: 0;">
                <button id="ghd-reset-search-sector" class="ghd-btn ghd-btn-secondary" style="height: 42px; margin-top: auto;"><i class="fa-solid fa-xmark"></i> Limpiar</button>
            </div>
        </div>

        <div class="ghd-kpi-grid">
            <div class="ghd-kpi-card"><div class="kpi-icon icon-blue"><i class="fa-solid fa-list-check"></i></div><div class="kpi-info"><span id="kpi-activas" class="kpi-value"><?php echo esc_html($sector_kpi_data['total_pedidos']); ?></span><span class="kpi-label">Activas</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="kpi-info"><span id="kpi-prioridad-alta" class="kpi-value"><?php echo esc_html($sector_kpi_data['total_prioridad_alta']); ?></span><span class="kpi-label">Prioridad Alta</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-green"><i class="fa-solid fa-check"></i></div><div class="kpi-info"><span id="kpi-completadas-hoy" class="kpi-value"><?php echo esc_html($sector_kpi_data['completadas_hoy']); ?></span><span class="kpi-label">Completadas Hoy</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-yellow"><i class="fa-solid fa-clock"></i></div><div class="kpi-info"><span id="kpi-tiempo-promedio" class="kpi-value"><?php echo esc_html($sector_kpi_data['tiempo_promedio_str']); ?></span><span class="kpi-label">Tiempo Promedio</span></div></div>
        </div>

        <div class="ghd-sector-tasks-list">
            <?php if ($pedidos_query->have_posts()) : while ($pedidos_query->have_posts()) : $pedidos_query->the_post();
                $current_order_id = get_the_ID();
                $asignado_a_field_name = str_replace('estado_', 'asignado_a_', $campo_estado);
                $asignado_a_id = get_field($asignado_a_field_name, $current_order_id);
                $asignado_a_user = $asignado_a_id ? get_userdata($asignado_a_id) : null;
                $prioridad_pedido = get_field('prioridad_pedido', $current_order_id);

                $task_card_args = [
                    'post_id'           => $current_order_id,
                    'titulo'            => get_the_title(),
                    'prioridad_class'   => 'prioridad-' . strtolower($prioridad_pedido ?: 'baja'),
                    'prioridad'         => $prioridad_pedido,
                    'nombre_cliente'    => get_field('nombre_cliente', $current_order_id),
                    'nombre_producto'   => get_field('nombre_producto', $current_order_id),
                    'permalink'         => get_permalink(),
                    'campo_estado'      => $campo_estado,
                    'estado_actual'     => get_field($campo_estado, $current_order_id),
                    'is_leader'         => $is_leader_for_rendering,
                    'operarios_sector'  => $operarios_del_sector,
                    'asignado_a_id'     => $asignado_a_id,
                    'asignado_a_name'   => $asignado_a_user ? $asignado_a_user->display_name : 'Sin asignar',
                    'logged_in_user_id' => $current_user->ID,
                ];

                get_template_part('template-parts/task-card', null, $task_card_args);

            endwhile; else: ?>
                <p class="no-tasks-message">No tienes tareas pendientes.</p>
            <?php endif; wp_reset_postdata(); ?>
        </div>        
    </main>
</div>
<?php get_footer(); ?>