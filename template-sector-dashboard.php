<?php
/*
Template Name: Panel de Tareas (Sector) V2
*/

if (!is_user_logged_in()) { auth_redirect(); }

$current_user = wp_get_current_user();
$user_roles = (array) $current_user->roles;

$allowed_leader_sectors = [];
foreach ($user_roles as $role) {
    if (strpos($role, 'lider_') !== false) {
        $allowed_leader_sectors[] = str_replace('lider_', '', remove_accents($role));
    }
}

$requested_sector_raw = isset($_GET['sector']) ? sanitize_text_field($_GET['sector']) : '';
$requested_sector = strtolower(remove_accents($requested_sector_raw));

$base_sector_key = '';
$is_leader_for_rendering = false; // Flag para el renderizado del selector de asignación

// 1. Prioridad: Administrador viendo un sector específico
if (current_user_can('manage_options') && !empty($requested_sector) && array_key_exists($requested_sector, ghd_get_sectores())) {
    $base_sector_key = $requested_sector;
    $is_leader_for_rendering = true; // El Admin actúa como líder para la UI
}
// 2. Si es un líder real del sector solicitado
elseif (!empty($requested_sector) && in_array($requested_sector, $allowed_leader_sectors)) {
    $base_sector_key = $requested_sector;
    $is_leader_for_rendering = true; // Es líder del sector
}
// 3. Si no se solicita un sector, pero es líder de alguno, redirigir al primero
elseif (!empty($allowed_leader_sectors)) {
    wp_redirect( add_query_arg('sector', $allowed_leader_sectors[0], get_permalink() ) );
    exit;
}
// 4. Fallback si no es Admin y no es líder de ningún sector
elseif (!current_user_can('manage_options') && empty($allowed_leader_sectors)) {
    wp_redirect( home_url('/panel-de-control/') ); // Redirigir si no tiene permisos para ningún panel
    exit;
}

// 5. Último fallback si $base_sector_key sigue vacío (ej. sector inválido solicitado)
if (empty($base_sector_key)) {
    wp_redirect( home_url('/panel-de-control/') );
    exit;
}

get_header();

// --- ¡CRÍTICO! DETERMINACIÓN PRECISA DEL $campo_estado Y $is_leader FINAL ---
$mapa_roles = ghd_get_mapa_roles_a_campos();
$campo_estado = ''; // Se inicializa y se rellena con el campo ACF correcto.

// Si el usuario actual tiene el rol de líder del $base_sector_key, o es Admin viendo ese sector
if ($is_leader_for_rendering) {
    // Si es un Admin, o un líder real, queremos el campo de estado para el ROL DE LÍDER de ese sector.
    if (isset($mapa_roles['lider_' . $base_sector_key])) {
        $campo_estado = $mapa_roles['lider_' . $base_sector_key];
    }
} 
// Si NO es líder de renderizado (ej. un operario que tiene su propio panel)
elseif (isset($mapa_roles['operario_' . $base_sector_key])) {
    $campo_estado = $mapa_roles['operario_' . $base_sector_key];
} 
// Casos especiales para Control Final (Macarena)
elseif ($base_sector_key === 'control_final') {
    $campo_estado = 'estado_administrativo';
}

// Fallback si por alguna razón no se encontró un campo de estado específico
if (empty($campo_estado)) {
    // Esto no debería ocurrir si el mapeo de roles y los slugs son correctos.
    error_log("GHD Error: campo_estado no determinado para base_sector_key: {$base_sector_key} y roles del usuario.");
    $campo_estado = 'estado_' . $base_sector_key; // Último intento de inferir (pero indica un problema)
}

$is_leader = $is_leader_for_rendering; // La variable $is_leader para task-card.php ahora es $is_leader_for_rendering

$sector_display_map = ghd_get_sectores(); 
$sector_name = $sector_display_map[$base_sector_key] ?? ucfirst($base_sector_key);

$operarios_del_sector = [];
if ($is_leader && !empty($base_sector_key)) {
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

if (!current_user_can('ghd_view_all_sector_tasks') && !$is_leader) {
    $asignado_a_field = str_replace('estado_', 'asignado_a_', $campo_estado);
    $pedidos_query_args['meta_query'][] = ['key' => $asignado_a_field, 'value' => $current_user->ID, 'compare' => '='];
}

$pedidos_query = new WP_Query($pedidos_query_args);

?>

<div class="ghd-app-wrapper">
    <?php get_template_part('template-parts/sidebar-sector'); ?>

    <main class="ghd-main-content" data-campo-estado="<?php echo esc_attr($campo_estado); ?>">
                <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <?php 
                // --- NUEVO: El botón "Volver" solo se muestra para Administradores ---
                if (current_user_can('manage_options')) {
                    // Obtener la URL de la página que lista TODOS los sectores para el Administrador
                    // Reemplaza 'tu-slug-de-pagina-sectores' con el SLUG REAL de tu página con template-sectores.php
                    $sectores_page_admin = get_page_by_path('tu-slug-de-pagina-sectores'); 
                    $back_button_url = $sectores_page_admin ? get_permalink($sectores_page_admin->ID) : home_url('/panel-de-control/sectores'); // Fallback URL
                    $back_button_text = 'Volver a Sectores';
                ?>
                    <a href="<?php echo esc_url($back_button_url); ?>" class="ghd-btn ghd-btn-secondary ghd-btn-small" style="margin-right: 15px;">
                        <i class="fa-solid fa-arrow-left"></i> <?php echo esc_html($back_button_text); ?>
                    </a>
                <?php 
                }
                // --- FIN NUEVO: Bloque condicional para el botón "Volver" ---
                ?>
                <h2>Tareas de <?php echo esc_html($sector_name); ?></h2>
            </div>
            <div class="header-actions">
                <button id="ghd-refresh-tasks" class="ghd-btn ghd-btn-secondary"><i class="fa-solid fa-sync"></i> <span>Refrescar</span></button>
            </div>
        </header>

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
                    'is_leader'         => $is_leader,
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