<?php

if (!is_user_logged_in()) { auth_redirect(); }
get_header();

// --- 1. LÓGICA DE CONTEXTO Y ACCESO (Estable) ---
$current_user = wp_get_current_user();
$is_admin_viewing = false;
$user_roles = $current_user->roles;
$user_role = !empty($user_roles) ? $user_roles[0] : '';

// Caso 1: Administrador de WordPress visitando un panel de sector
if (current_user_can('manage_options') && isset($_GET['sector'])) {
    $sector_name = sanitize_text_field(urldecode($_GET['sector']));
    $is_admin_viewing = true;
    $clean_sector_name = strtolower(str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $sector_name));
    $campo_estado = 'estado_' . $clean_sector_name;
} 
// Caso 2: Trabajador de producción o administrativo viendo su propio panel
elseif (!current_user_can('manage_options')) {
    $mapa_roles = ghd_get_mapa_roles_a_campos();
    $campo_estado = $mapa_roles[$user_role] ?? '';
    $sector_name = ucfirst(str_replace(['rol_', '_'], ' ', $user_role));
} 
// Caso 3: Admin sin especificar sector (redirigir)
else {
    wp_redirect(home_url('/panel-de-control/'));
    exit;
}

// --- 2. CONSULTA Y KPIs (Estable) ---
$pedidos_args = ['post_type' => 'orden_produccion', 'posts_per_page' => -1, 'meta_query' => [['key' => $campo_estado, 'value' => ['Pendiente', 'En Progreso'], 'compare' => 'IN']], 'orderby'  => ['prioridad_pedido' => 'ASC', 'date' => 'ASC'], 'meta_key' => 'prioridad_pedido' ];
$pedidos_query = new WP_Query($pedidos_args);
$total_pedidos = $pedidos_query->post_count;
$total_prioridad_alta = 0; $total_tiempo_espera = 0; $ahora = current_time('U');
if ($pedidos_query->have_posts()) {
    foreach ($pedidos_query->posts as $pedido) {
        if (get_field('prioridad_pedido', $pedido->ID) === 'Alta') { $total_prioridad_alta++; }
        $total_tiempo_espera += $ahora - get_the_modified_time('U', $pedido->ID);
    }
}
$tiempo_promedio_str = '0.0h';
if ($total_pedidos > 0) {
    $promedio_horas = ($total_tiempo_espera / $total_pedidos) / 3600;
    $tiempo_promedio_str = number_format($promedio_horas, 1) . 'h';
}
$completadas_hoy = 0;
?>

<div class="ghd-app-wrapper">
    <?php 
    if ($is_admin_viewing) { get_template_part('template-parts/sidebar-admin'); } 
    else { get_template_part('template-parts/sidebar-sector'); } 
    ?>

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
            <div class="ghd-kpi-card"><div class="kpi-icon icon-blue"><i class="fa-solid fa-list-check"></i></div><div class="kpi-info"><span class="kpi-value"><?php echo $total_pedidos; ?></span><span class="kpi-label">Activas</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="kpi-info"><span class="kpi-value"><?php echo $total_prioridad_alta; ?></span><span class="kpi-label">Prioridad Alta</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-green"><i class="fa-solid fa-check"></i></div><div class="kpi-info"><span class="kpi-value"><?php echo $completadas_hoy; ?></span><span class="kpi-label">Completadas Hoy</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-yellow"><i class="fa-solid fa-clock"></i></div><div class="kpi-info"><span class="kpi-value"><?php echo esc_html($tiempo_promedio_str); ?></span><span class="kpi-label">Tiempo Promedio</span></div></div>
        </div>
        <div class="ghd-sector-tasks-list">
            <?php if ($pedidos_query->have_posts()) : while ($pedidos_query->have_posts()) : $pedidos_query->the_post();
                
                // --- CORRECCIÓN CLAVE: Lógica condicional para el tipo de tarjeta ---
                // Si el usuario es administrativo, mostramos una tarjeta diferente.
                if ($user_role === 'rol_administrativo') {
                    ?>
                    <div class="ghd-order-card" id="order-<?php echo get_the_ID(); ?>">
                        <div class="order-card-main">
                            <div class="order-card-header">
                                <h3><?php the_title(); ?></h3>
                            </div>
                            <div class="order-card-body">
                                <p><strong>Cliente:</strong> <?php echo esc_html(get_field('nombre_cliente')); ?></p>
                                <p><strong>Producto:</strong> <?php echo esc_html(get_field('nombre_producto')); ?></p>
                            </div>
                        </div>
                        <div class="order-card-actions">
                            <button class="ghd-btn ghd-btn-primary archive-order-btn" data-order-id="<?php echo get_the_ID(); ?>">
                                Archivar Pedido
                            </button>
                            <a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary">Ver Detalles</a>
                        </div>
                    </div>
                    <?php
                } 
                // Si es cualquier otro trabajador de producción, mostramos la tarjeta normal.
                else {
                    get_template_part('template-parts/task-card-v2', null, ['id' => get_the_ID(), 'campo_estado' => $campo_estado]);
                }

            endwhile; else: ?>
                <p class="no-tasks-message">No tienes tareas pendientes.</p>
            <?php endif; wp_reset_postdata(); ?>
        </div>        
    </main>
</div>
<?php get_footer(); ?>