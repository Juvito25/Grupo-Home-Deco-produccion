<?php
/* 
 * Template Name: GHD - Panel de Sector
 * Versión Final y Corregida
 */

// --- CONTROL DE ACCESO (SE EJECUTA ANTES DE CUALQUIER HTML) ---
if (!is_user_logged_in()) {
    auth_redirect();
}
if (current_user_can('manage_options')) {
    $admin_dashboard_url = home_url('/');
    $admin_pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-admin-dashboard.php']);
    if (!empty($admin_pages)) { $admin_dashboard_url = get_permalink($admin_pages[0]); }
    wp_redirect($admin_dashboard_url);
    exit;
}

// --- COMIENZO DE LA VISTA ---
get_header();
// --- OBTENCIÓN DE DATOS PARA LA VISTA (CON CÁLCULO DE TIEMPO PROMEDIO) ---
$current_user = wp_get_current_user();
$user_roles = $current_user->roles;
$user_role = !empty($user_roles) ? $user_roles[0] : '';
$role_to_sector_map = array('rol_carpinteria' => 'Carpintería', 'rol_costura' => 'Costura', 'rol_tapiceria' => 'Tapicería', 'rol_logistica' => 'Logística');
$sector_name = isset($role_to_sector_map[$user_role]) ? $role_to_sector_map[$user_role] : '';

// 1. Hacemos la consulta principal
$pedidos_args = array(
    'post_type' => 'orden_produccion', 'posts_per_page' => -1,
    'meta_query' => array(array('key' => 'sector_actual', 'value' => $sector_name)),
    'orderby'  => array('prioridad_pedido' => 'ASC', 'date' => 'ASC'),
);
$pedidos_query = new WP_Query($pedidos_args);

// 2. Calculamos los KPIs, incluyendo el tiempo promedio
$total_pedidos = $pedidos_query->post_count;
$total_prioridad_alta = 0;
$total_tiempo_espera_segundos = 0;
$ahora_timestamp = current_time('U'); // Hora actual en segundos (formato Unix)

if ($pedidos_query->have_posts()) {
    foreach ($pedidos_query->posts as $pedido) {
        // Contamos las prioridades altas
        if (get_field('prioridad_pedido', $pedido->ID) === 'Alta') {
            $total_prioridad_alta++;
        }
        // Sumamos el tiempo de espera de cada pedido
        $fecha_modificacion_timestamp = get_the_modified_time('U', $pedido->ID);
        $total_tiempo_espera_segundos += $ahora_timestamp - $fecha_modificacion_timestamp;
    }
}

// 3. Formateamos el resultado final
$tiempo_promedio_str = 'N/A';
if ($total_pedidos > 0) {
    $promedio_segundos = $total_tiempo_espera_segundos / $total_pedidos;
    $promedio_horas = $promedio_segundos / 3600; // Convertimos segundos a horas
    $tiempo_promedio_str = number_format($promedio_horas, 1) . 'h'; // Formateamos a 1 decimal (ej: "7.1h")
}

$completadas_hoy = 0; // Mantenemos este como estático por ahora

?>

<div class="ghd-app-wrapper">
    <?php get_template_part('template-parts/sidebar-sector'); ?>
    <main class="ghd-main-content">
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
            <div class="ghd-kpi-card"><div class="kpi-icon icon-blue"><i class="fa-solid fa-list-check"></i></div><div class="kpi-info"><span class="kpi-value"><?php echo $total_pedidos; ?></span><span class="kpi-label">Órdenes Activas</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="kpi-info"><span class="kpi-value"><?php echo $total_prioridad_alta; ?></span><span class="kpi-label">Prioridad Alta</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-green"><i class="fa-solid fa-check"></i></div><div class="kpi-info"><span class="kpi-value"><?php echo $completadas_hoy; ?></span><span class="kpi-label">Completadas Hoy</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-yellow"><i class="fa-solid fa-clock"></i></div><div class="kpi-info"><span class="kpi-value"><?php echo $tiempo_promedio_str; ?></span><span class="kpi-label">Tiempo Promedio</span></div></div>
        </div>
        
        <div class="ghd-sector-tasks-grid">
            <?php if ($pedidos_query->have_posts()) : while ($pedidos_query->have_posts()) : $pedidos_query->the_post();
                $p = get_field('prioridad_pedido'); $pc = ($p=='Alta')?'tag-red':(($p=='Media')?'tag-yellow':'tag-green');
            ?>
            <div class="ghd-task-card" id="order-<?php echo get_the_ID(); ?>">
                <div class="card-header"><h3><?php the_title(); ?></h3><span class="ghd-tag <?php echo $pc; ?>"><?php echo esc_html($p); ?></span></div>
                <div class="card-body">
                    <p><strong>Cliente:</strong> <?php echo esc_html(get_field('nombre_cliente')); ?></p>
                    <p><strong>Producto:</strong> <?php echo esc_html(get_field('nombre_producto')); ?></p>
                </div>
                <div class="card-footer">
                    <a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary">Detalles</a>
                    
                    <?php
                    // --- LÓGICA CONDICIONAL PARA MOSTRAR EL BOTÓN DE REMITO ---
                    $sector_actual_tarjeta = get_field('sector_actual', get_the_ID());
                    $sectores_permitidos_tarjeta = array('Tapicería', 'Logística');

                    // Mostramos el botón de Remito si el pedido está en un sector permitido.
                    if (in_array($sector_actual_tarjeta, $sectores_permitidos_tarjeta)) :
                    ?>
                        <a href="<?php echo get_stylesheet_directory_uri(); ?>/generar-remito.php?pedido_id=<?php echo get_the_ID(); ?>" 
                        class="ghd-btn ghd-btn-secondary" 
                        target="_blank">
                        Generar Remito
                        </a>
                    <?php endif; ?>

                    <button class="ghd-btn ghd-btn-primary move-to-next-sector-btn" data-order-id="<?php echo get_the_ID(); ?>" data-nonce="<?php echo wp_create_nonce('ghd_move_order_nonce'); ?>">
                        Mover
                    </button>
                </div>
            </div>
            <?php endwhile; else: echo '<p>No tienes tareas asignadas.</p>'; endif; wp_reset_postdata(); ?>
        </div>
    </main>
</div>
<?php get_footer(); ?>