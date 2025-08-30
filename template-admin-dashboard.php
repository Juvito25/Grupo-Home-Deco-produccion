<?php
/**
 * Template Name: GHD - Panel de Administrador
 * Versión V5 - Pedidos en Producción (con Material/Color/Observaciones)
 */

if (!is_user_logged_in() || !current_user_can('manage_options')) {
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
        
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <h2>Pedidos Pendientes de Asignación</h2>
            </div> 
            <div class="header-actions">
                <button class="ghd-btn ghd-btn-secondary"><i class="fa-solid fa-download"></i> <span>Exportar</span></button>
                <button class="ghd-btn ghd-btn-primary"><i class="fa-solid fa-plus"></i> <span>Nuevo Pedido</span></button>
            </div>
        </header>

        <!-- TABLA DE PEDIDOS PENDIENTES DE ASIGNACIÓN -->
        <div class="ghd-card ghd-table-wrapper">
            <table class="ghd-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Producto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="ghd-orders-table-body">
                    <?php
                    $args_asignacion = array(
                        'post_type'      => 'orden_produccion',
                        'posts_per_page' => -1,
                        'meta_query'     => array(
                            array(
                                'key'     => 'estado_pedido',
                                'value'   => 'Pendiente de Asignación',
                                'compare' => '=',
                            ),
                        ),
                    );
                    $pedidos_asignacion_query = new WP_Query($args_asignacion);

                    if ($pedidos_asignacion_query->have_posts()) :
                        while ($pedidos_asignacion_query->have_posts()) : $pedidos_asignacion_query->the_post();
                    ?>
                        <tr id="order-row-<?php echo get_the_ID(); ?>">
                            <td><a href="<?php the_permalink(); ?>" style="color: var(--color-rojo); font-weight: 600;"><?php the_title(); ?></a></td>
                            <td><?php echo esc_html(get_field('nombre_cliente')); ?></td>
                            <td><?php echo esc_html(get_field('nombre_producto')); ?></td>
                            <td>
                                <button class="ghd-btn ghd-btn-primary start-production-btn" data-order-id="<?php echo get_the_ID(); ?>">
                                    Iniciar Producción
                                </button>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else: 
                    ?>
                        <tr><td colspan="4" style="text-align:center;">No hay pedidos pendientes de asignación.</td></tr>
                    <?php
                    endif;
                    wp_reset_postdata(); 
                    ?>
                </tbody>
            </table>
        </div>

        <!-- NUEVA SECCIÓN: PEDIDOS EN PRODUCCIÓN -->
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
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Producto</th>
                        <th>Material</th> <!-- NUEVA COLUMNA -->
                        <th>Color</th>   <!-- NUEVA COLUMNA -->
                        <th>Observaciones</th> <!-- NUEVA COLUMNA -->
                        <th>Estado General</th>
                        <th>Sub-estados de Producción</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="ghd-production-table-body">
                    <?php echo $production_data['tasks_html']; ?>
                </tbody>
            </table>
        </div>


        <!-- SECCIÓN EXISTENTE: PEDIDOS PENDIENTES DE CIERRE -->
        <div class="header-title-wrapper" style="margin-top: 40px;">
            <h2>Pedidos Pendientes de Cierre</h2>
            <button id="ghd-refresh-closure-tasks" class="ghd-btn ghd-btn-secondary"><i class="fa-solid fa-sync"></i> <span>Refrescar</span></button>
        </div>

        <?php 
        $admin_closure_kpis = ghd_calculate_admin_closure_kpis();
        ?>

        <div class="ghd-kpi-grid" style="margin-bottom: 30px;">
            <div class="ghd-kpi-card"><div class="kpi-icon icon-blue"><i class="fa-solid fa-list-check"></i></div><div class="kpi-info"><span id="kpi-cierre-activas" class="kpi-value"><?php echo $admin_closure_kpis['total_pedidos_cierre']; ?></span><span class="kpi-label">Activas</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="kpi-info"><span id="kpi-cierre-prioridad-alta" class="kpi-value"><?php echo $admin_closure_kpis['total_prioridad_alta_cierre']; ?></span><span class="kpi-label">Prioridad Alta</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-green"><i class="fa-solid fa-check"></i></div><div class="kpi-info"><span id="kpi-cierre-completadas-hoy" class="kpi-value"><?php echo $admin_closure_kpis['completadas_hoy_cierre']; ?></span><span class="kpi-label">Completadas Hoy</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-yellow"><i class="fa-solid fa-clock"></i></div><div class="kpi-info"><span id="kpi-cierre-tiempo-promedio" class="kpi-value"><?php echo esc_html($admin_closure_kpis['tiempo_promedio_str_cierre']); ?></span><span class="kpi-label">Tiempo Promedio</span></div></div>
        </div>

        <!-- TABLA DE PEDIDOS PENDIENTES DE CIERRE (ADMINISTRATIVO) -->
        <div class="ghd-card ghd-table-wrapper" id="admin-closure-tasks-container">
            <table class="ghd-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Producto</th>
                        <th>Fecha de Pedido</th>
                        <th>Acciones</th>
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

                    if ($pedidos_cierre_query->have_posts()) :
                        while ($pedidos_cierre_query->have_posts()) : $pedidos_cierre_query->the_post();
                    ?>
                        <tr id="order-row-closure-<?php echo get_the_ID(); ?>">
                            <td><a href="<?php the_permalink(); ?>" style="color: var(--color-rojo); font-weight: 600;"><?php the_title(); ?></a></td>
                            <td><?php echo esc_html(get_field('nombre_cliente')); ?></td>
                            <td><?php echo esc_html(get_field('nombre_producto')); ?></td>
                            <td><?php echo get_the_date(); ?></td>
                            <td>
                                <a href="<?php 
                                    $remito_page_id = get_posts([
                                        'post_type'  => 'page',
                                        'fields'     => 'ids',
                                        'nopaging'   => true,
                                        'meta_key'   => '_wp_page_template',
                                        'meta_value' => 'template-remito.php'
                                    ]);
                                    $remito_base_url = !empty($remito_page_id) ? get_permalink($remito_page_id[0]) : home_url();
                                    echo esc_url( add_query_arg( 'order_id', get_the_ID(), $remito_base_url ) );
                                ?>" target="_blank" class="ghd-btn ghd-btn-secondary ghd-btn-small generate-remito-btn" data-order-id="<?php echo get_the_ID(); ?>">
                                    <i class="fa-solid fa-file-invoice"></i> Generar Remito
                                </a>
                                <button class="ghd-btn ghd-btn-primary archive-order-btn" data-order-id="<?php echo get_the_ID(); ?>">
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