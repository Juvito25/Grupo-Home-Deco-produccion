<?php
/*
Template Name: Panel de Tareas (Sector) V2
*/

if (!is_user_logged_in()) { auth_redirect(); }

get_header();

$current_user = wp_get_current_user();
$user_roles = (array) $current_user->roles;

// --- LÓGICA PARA DETERMINAR QUÉ SECTOR MOSTRAR (SIN REDIRECCIONES) ---

$allowed_leader_sectors = [];
foreach ($user_roles as $role) {
    if (strpos($role, 'lider_') !== false) {
        $allowed_leader_sectors[] = str_replace('lider_', '', $role);
    }
}

// $requested_sector = isset($_GET['sector']) ? sanitize_text_field($_GET['sector']) : '';
$requested_sector = isset($_GET['sector']) ? strtolower(sanitize_text_field($_GET['sector'])) : '';
$base_sector_key = '';

// Si se solicita un sector y el usuario tiene permiso, lo usamos.
if (!empty($requested_sector) && in_array($requested_sector, $allowed_leader_sectors)) {
    $base_sector_key = $requested_sector;
} 
// Si no se solicita, pero es líder, usamos el primero de su lista.
elseif (!empty($allowed_leader_sectors)) {
    $base_sector_key = $allowed_leader_sectors[0];
} 
// Si no es un líder de sector, no debería estar aquí.
elseif (!current_user_can('manage_options')) {
    // Si no es admin y no tiene roles de líder, lo mandamos al panel principal.
    // Usamos JS para la redirección para evitar errores de header, aunque en este punto ya no deberían ocurrir.
    echo '<script>window.location.href = "' . esc_url(home_url('/panel-de-control/')) . '";</script>';
    exit;
}

// Si $base_sector_key sigue vacío, es un caso de error.
if (empty($base_sector_key)) {
    get_footer();
    return; // Detenemos la ejecución para evitar errores.
}

// A partir del $base_sector_key, definimos el resto de variables
$mapa_roles = ghd_get_mapa_roles_a_campos();
$campo_estado = 'estado_' . $base_sector_key;
$is_leader = true;

$sector_display_map = [ 
    'carpinteria' => 'Carpintería', 'corte' => 'Corte', 'costura' => 'Costura', 
    'tapiceria' => 'Tapicería', 'embalaje' => 'Embalaje', 'logistica' => 'Logística',
];
$sector_name = $sector_display_map[$base_sector_key] ?? ucfirst($base_sector_key);

$operarios_del_sector = get_users([
    'role__in' => ['lider_' . $base_sector_key, 'operario_' . $base_sector_key], 
    'orderby'  => 'display_name',
    'order'    => 'ASC'
]);

// --- FIN DE LA LÓGICA ---

add_filter('body_class', function($classes) {
    $classes[] = 'is-sector-dashboard-panel';
    return $classes;
});

$sector_kpi_data = ghd_calculate_sector_kpis($campo_estado);
$pedidos_query = new WP_Query([
    'post_type'      => 'orden_produccion', 
    'posts_per_page' => -1, 
    'meta_query'     => [['key' => $campo_estado, 'value' => ['Pendiente', 'En Progreso'], 'compare' => 'IN']]
]);
?>

<div class="ghd-app-wrapper">
    <?php get_template_part('template-parts/sidebar-sector'); ?>

    <main class="ghd-main-content" data-campo-estado="<?php echo esc_attr($campo_estado); ?>">
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
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