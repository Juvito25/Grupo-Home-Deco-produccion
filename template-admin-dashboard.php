<?php
/**
 * Template Name: GHD - Panel de Administrador
 * V7 - Vista condicional para Admin y Control Final (Macarena)
 */

// Redirección de seguridad
if ( ! is_user_logged_in() || ( ! current_user_can('manage_options') && ! current_user_can('control_final_macarena') ) ) {
    auth_redirect();
}

add_filter('body_class', function($classes) {
    $classes[] = 'is-admin-dashboard-panel';
    return $classes;
});

get_header(); 
?>

<div class="ghd-app-wrapper">
    
    <?php get_template_part('template-parts/sidebar-admin'); ?>

    <main class="ghd-main-content">
        
        <?php // --- SECCIONES SOLO PARA ADMINISTRADOR --- ?>
        <?php if (current_user_can('manage_options')) : ?>
            
            <header class="ghd-main-header">
                <div class="header-title-wrapper">
                    <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                    <h2>Pedidos Pendientes de Asignación</h2>
                </div> 
                <div class="header-actions">
                    <button class="ghd-btn ghd-btn-primary"><i class="fa-solid fa-plus"></i> <span>Nuevo Pedido</span></button>
                </div>
            </header>

            <div class="ghd-card ghd-table-wrapper">
                <table class="ghd-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Producto</th>
                            <th>Vendedora</th>
                            <th>Prioridad</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="ghd-orders-table-body">
                        <?php
                        // Carga inicial de pedidos pendientes de asignación
                        $pedidos_asignacion_query = new WP_Query(['post_type' => 'orden_produccion', 'posts_per_page' => -1, 'meta_query' => [['key' => 'estado_pedido', 'value' => 'Pendiente de Asignación']]]);
                        if ($pedidos_asignacion_query->have_posts()) :
                            while ($pedidos_asignacion_query->have_posts()) : $pedidos_asignacion_query->the_post();
                                // Lógica para mostrar cada fila...
                            endwhile;
                        else:
                            echo '<tr><td colspan="6" style="text-align:center;">No hay pedidos pendientes de asignación.</td></tr>';
                        endif;
                        wp_reset_postdata();
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="header-title-wrapper" style="margin-top: 40px;">
                <h2>Pedidos en Producción</h2>
                <button id="ghd-refresh-production-tasks" class="ghd-btn ghd-btn-secondary"><i class="fa-solid fa-sync"></i> <span>Refrescar</span></button>
            </div>

            <?php 
            $production_data = ghd_get_pedidos_en_produccion_data();
            $production_kpis = $production_data['kpi_data'];
            ?>
            <div class="ghd-kpi-grid" style="margin-bottom: 30px;">
                <div class="ghd-kpi-card"><div class="kpi-icon icon-blue"><i class="fa-solid fa-list-check"></i></div><div class="kpi-info"><span id="kpi-produccion-activas" class="kpi-value"><?php echo $production_kpis['total_pedidos_produccion']; ?></span><span class="kpi-label">Activas</span></div></div>
                <div class="ghd-kpi-card"><div class="kpi-icon icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="kpi-info"><span id="kpi-produccion-prioridad-alta" class="kpi-value"><?php echo $production_kpis['total_prioridad_alta_produccion']; ?></span><span class="kpi-label">Prioridad Alta</span></div></div>
                <div class="ghd-kpi-card"><div class="kpi-icon icon-green"><i class="fa-solid fa-check"></i></div><div class="kpi-info"><span id="kpi-produccion-completadas-hoy" class="kpi-value"><?php echo $production_kpis['completadas_hoy_produccion']; ?></span><span class="kpi-label">Completadas Hoy</span></div></div>
                <div class="ghd-kpi-card"><div class="kpi-icon icon-yellow"><i class="fa-solid fa-clock"></i></div><div class="kpi-info"><span id="kpi-produccion-tiempo-promedio" class="kpi-value"><?php echo esc_html($production_kpis['tiempo_promedio_str_produccion']); ?></span><span class="kpi-label">Tiempo Promedio</span></div></div>
            </div>

            <div class="ghd-card ghd-table-wrapper" id="admin-production-tasks-container">
                <table class="ghd-table">
                    <thead>
                        <tr>
                            <th>Código</th> <th>Cliente</th> <th>Vendedora</th> <th>Producto</th> <th>Material</th> <th>Color</th> <th>Observaciones</th> <th>Estado General</th> <th>Asignación/Completado</th> <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="ghd-production-table-body">
                        <?php echo $production_data['tasks_html']; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; // --- FIN DE SECCIONES SOLO PARA ADMINISTRADOR --- ?>


        <?php // --- SECCIÓN PARA ADMIN Y MACARENA --- ?>
        <div class="header-title-wrapper" style="margin-top: 40px;">
             <?php if (!current_user_can('manage_options')) : // Título específico para Macarena ?>
                <h2>Panel de Control Final</h2>
             <?php else: ?>
                <h2>Pedidos Pendientes de Cierre</h2>
             <?php endif; ?>
            <button id="ghd-refresh-closure-tasks" class="ghd-btn ghd-btn-secondary"><i class="fa-solid fa-sync"></i> <span>Refrescar</span></button>
        </div>

        <?php $admin_closure_kpis = ghd_calculate_admin_closure_kpis(); ?>
        <div class="ghd-kpi-grid" style="margin-bottom: 30px;">
            <div class="ghd-kpi-card"><div class="kpi-icon icon-blue"><i class="fa-solid fa-list-check"></i></div><div class="kpi-info"><span id="kpi-cierre-activas" class="kpi-value"><?php echo $admin_closure_kpis['total_pedidos_cierre']; ?></span><span class="kpi-label">Activas</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="kpi-info"><span id="kpi-cierre-prioridad-alta" class="kpi-value"><?php echo $admin_closure_kpis['total_prioridad_alta_cierre']; ?></span><span class="kpi-label">Prioridad Alta</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-green"><i class="fa-solid fa-check"></i></div><div class="kpi-info"><span id="kpi-cierre-completadas-hoy" class="kpi-value"><?php echo $admin_closure_kpis['completadas_hoy_cierre']; ?></span><span class="kpi-label">Completadas Hoy</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-yellow"><i class="fa-solid fa-clock"></i></div><div class="kpi-info"><span id="kpi-cierre-tiempo-promedio" class="kpi-value"><?php echo esc_html($admin_closure_kpis['tiempo_promedio_str_cierre']); ?></span><span class="kpi-label">Tiempo Promedio</span></div></div>
        </div>

        <div class="ghd-card ghd-table-wrapper" id="admin-closure-tasks-container">
            <table class="ghd-table">
                <thead>
                    <tr>
                        <th>Código</th> <th>Cliente</th> <th>Producto</th> <th>Fecha de Pedido</th> <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="ghd-closure-table-body">
                    <?php
                    $args_cierre = array(
                        'post_type'      => 'orden_produccion',
                        'posts_per_page' => -1,
                        'meta_query'     => array(
                            array(
                                'key'     => 'estado_pedido',
                                'value'   => 'Pendiente de Cierre Admin',
                                'compare' => '=',
                            ),
                        ),
                        'orderby' => 'date',
                        'order'   => 'ASC',
                    );
                    $pedidos_cierre_query = new WP_Query($args_cierre);

                    // Obtener la URL base para el remito una sola vez
                    $remito_page = get_page_by_path('generador-de-remitos'); // Asegúrate de que el slug de tu página de remito sea 'remito'
                    $remito_base_url = $remito_page ? get_permalink($remito_page->ID) : home_url();

                    if ($pedidos_cierre_query->have_posts()) :
                        while ($pedidos_cierre_query->have_posts()) : $pedidos_cierre_query->the_post();
                            $order_id = get_the_ID();
                            $remito_url = esc_url(add_query_arg('order_id', $order_id, $remito_base_url));
                    ?>
                        <tr id="order-row-closure-<?php echo $order_id; ?>">
                            <td><a href="<?php the_permalink(); ?>" style="color: var(--color-rojo); font-weight: 600;"><?php the_title(); ?></a></td>
                            <td><?php echo esc_html(get_field('nombre_cliente', $order_id)); ?></td>
                            <td><?php echo esc_html(get_field('nombre_producto', $order_id)); ?></td>
                            <td><?php echo get_the_date('d/m/Y', $order_id); ?></td>
                            <td>
                                <a href="<?php echo $remito_url; ?>" target="_blank" class="ghd-btn ghd-btn-secondary ghd-btn-small">
                                    <i class="fa-solid fa-file-invoice"></i> Generar Remito
                                </a>
                                <button class="ghd-btn ghd-btn-success archive-order-btn" data-order-id="<?php echo $order_id; ?>">
                                    Archivar Pedido
                                </button>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else: 
                    ?>
                        <tr><td colspan="5" style="text-align:center;">No hay pedidos pendientes de cierre.</td></tr>
                    <?php
                    endif;
                    wp_reset_postdata(); 
                    ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<?php get_footer(); ?>