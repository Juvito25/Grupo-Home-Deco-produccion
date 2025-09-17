<?php
/**
 * Template Name: Panel de Tareas (Sector) V2
 */

// --- 1. Lógica de Redirección y Determinación de Sector (TODO ANTES DE get_header()) ---

// 1.1 Redirección de seguridad: Asegurar que el usuario esté logueado
if (!is_user_logged_in()) {
    auth_redirect(); // Esto envía cabeceras y sale, por lo que debe ser lo primero.
}

$current_user = wp_get_current_user();
$user_roles = (array) $current_user->roles;

// 1.2 Determinar qué sectores son permitidos para el usuario actual (roles de líder)
$allowed_leader_sectors = [];
foreach ($user_roles as $role) {
    if (strpos($role, 'lider_') !== false) {
        $allowed_leader_sectors[] = str_replace('lider_', '', remove_accents($role));
    }
}

// 1.3 Limpiar y normalizar el sector solicitado desde la URL
$requested_sector_raw = isset($_GET['sector']) ? sanitize_text_field($_GET['sector']) : '';
$requested_sector = strtolower(remove_accents($requested_sector_raw));

$base_sector_key = '';
$is_leader = false; // Se inicializa aquí, se ajustará más adelante si es necesario para la vista

// 1.4 Lógica para determinar el $base_sector_key y si es un líder para propósitos de la vista
// A) Si el usuario es administrador, puede ver cualquier sector válido solicitado
if (current_user_can('manage_options') && !empty($requested_sector) && array_key_exists($requested_sector, ghd_get_sectores())) {
    $base_sector_key = $requested_sector;
    $is_leader = true; // El admin actúa como líder de ese sector para la vista
}
// B) Si se solicita un sector y el usuario tiene permiso (es líder de ese sector)
elseif (!empty($requested_sector) && in_array($requested_sector, $allowed_leader_sectors)) {
    $base_sector_key = $requested_sector;
    $is_leader = true;
}
// C) Si no se solicita un sector específico, pero el usuario es líder de al menos un sector, redirigimos al primero
elseif (!empty($allowed_leader_sectors)) {
    wp_redirect( add_query_arg('sector', $allowed_leader_sectors[0], get_permalink() ) );
    exit;
}
// D) Si no es admin y no tiene roles de líder, lo mandamos al panel principal.
elseif (!current_user_can('manage_options')) {
    wp_redirect( home_url('/panel-de-control/') );
    exit;
}
// E) Si $base_sector_key sigue vacío después de todas las comprobaciones, es un caso de error o acceso no autorizado.
//    (Esto debería capturar casos donde un sector no válido es solicitado o no hay permisos)
if (empty($base_sector_key)) {
    wp_redirect( home_url('/panel-de-control/') ); // Fallback si nada de lo anterior se cumple
    exit;
}

// --- 2. Ahora que hemos asegurado que todas las redirecciones posibles han ocurrido,
//         y $base_sector_key está definido, podemos incluir el header y el resto del HTML. ---

get_header(); // A PARTIR DE AQUÍ PUEDE HABER OUTPUT

// A partir del $base_sector_key, definimos el resto de variables que usará el template
$mapa_roles = ghd_get_mapa_roles_a_campos();
$campo_estado = 'estado_' . $base_sector_key;

$sector_display_map = ghd_get_sectores(); // Usamos la función ghd_get_sectores para obtener el nombre legible
$sector_name = $sector_display_map[$base_sector_key] ?? ucfirst($base_sector_key);

// Operarios del sector, esta lógica se mantiene similar a como la teníamos
$operarios_del_sector = [];
// Si $is_leader es true (sea por rol directo o por ser admin con permiso de ver todo)
if ($is_leader && !empty($base_sector_key)) {
    $operarios_del_sector = get_users([
        'role__in' => ['lider_' . $base_sector_key, 'operario_' . $base_sector_key], 
        'orderby'  => 'display_name',
        'order'    => 'ASC'
    ]);
}

// Ajuste para el contexto de rendering, si se pasa a task-card.php
// (Esta variable $is_leader ya la ajustamos en ghd_refresh_sector_tasks_callback)

add_filter('body_class', function($classes) {
    $classes[] = 'is-sector-dashboard-panel';
    return $classes;
});

// Los KPIs y la consulta de pedidos inicial se realizan aquí, después de que todo lo anterior esté listo
$sector_kpi_data = ghd_calculate_sector_kpis($campo_estado);
$pedidos_query_args = [
    'post_type'      => 'orden_produccion', 
    'posts_per_page' => -1, 
    'meta_query'     => [['key' => $campo_estado, 'value' => ['Pendiente', 'En Progreso'], 'compare' => 'IN']]
];

// Lógica para filtrar por asignación si el usuario NO es admin y NO es líder para la vista
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