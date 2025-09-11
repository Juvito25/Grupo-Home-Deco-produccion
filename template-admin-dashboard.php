<?php
/**
 * Template Name: GHD - Panel de Administrador
 * Versión V6 - Pedidos en Producción (con Material/Color/Observaciones) y Vendedoras
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
                <button id="ghd-export-assignation-orders" class="ghd-btn ghd-btn-secondary" data-export-type="assignation">
                    <i class="fa-solid fa-download"></i> <span>Exportar</span>
                </button>
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
                        <th>Vendedora</th> <!-- NUEVA COLUMNA: Vendedora para asignación inicial -->
                        <th>Prioridad</th>
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
                                <?php 
                                // Obtener USUARIOS Vendedoras
                                $vendedoras_users_objs = get_users([
                                    'role__in' => ['vendedora', 'gerente_ventas'],
                                    'orderby'  => 'display_name',
                                    'order'    => 'ASC'
                                ]);
                                $current_vendedora_id = get_field('vendedora_asignada', get_the_ID());
                                $is_vendedora_set = !empty($current_vendedora_id) && $current_vendedora_id !== '0'; // '0' es nuestro valor de "no asignado"
                                ?>
                                <select class="ghd-vendedora-selector" data-order-id="<?php echo get_the_ID(); ?>">
                                    <option value="0" <?php selected($current_vendedora_id, '0'); ?>>Asignar Vendedora</option>
                                    <?php 
                                    if (!empty($vendedoras_users_objs)) { 
                                        foreach ($vendedoras_users_objs as $vendedora_obj) : 
                                            // Asegurarse de que sea un usuario real, no un rol o un objeto vacío
                                            if (isset($vendedora_obj->ID) && !empty($vendedora_obj->display_name)) :
                                            ?>
                                            <option value="<?php echo esc_attr($vendedora_obj->ID); ?>" <?php selected($current_vendedora_id, $vendedora_obj->ID); ?>>
                                                <?php echo esc_html($vendedora_obj->display_name); ?>
                                            </option>
                                            <?php 
                                            endif;
                                        endforeach; 
                                    }
                                    ?>
                                </select>
                            </td>
                            <td>
                                <?php 
                                $current_priority = get_field('prioridad_pedido', get_the_ID());
                                // Lógica MEJORADA: Seleccionar "Seleccionar Prioridad" si el campo está vacío o no es una prioridad válida
                                // Además, si el campo ACF "prioridad_pedido" está vacío, setearlo a "Seleccionar Prioridad" para que el `selected()` funcione.
                                $display_priority = (empty($current_priority) || !in_array($current_priority, ['Alta', 'Media', 'Baja'])) ? 'Seleccionar Prioridad' : $current_priority; 
                                $is_priority_set = ($display_priority !== 'Seleccionar Prioridad');
                                
                                // El botón se habilita si la prioridad Y la vendedora están asignadas
                                $can_initiate_production = $is_priority_set && $is_vendedora_set;
                                ?>
                                <select class="ghd-priority-selector" data-order-id="<?php echo get_the_ID(); ?>">
                                    <option value="Seleccionar Prioridad" <?php selected($display_priority, 'Seleccionar Prioridad'); ?>>Seleccionar Prioridad</option>
                                    <option value="Alta" <?php selected($display_priority, 'Alta'); ?>>Alta</option>
                                    <option value="Media" <?php selected($display_priority, 'Media'); ?>>Media</option>
                                    <option value="Baja" <?php selected($display_priority, 'Baja'); ?>>Baja</option>
                                </select>
                            </td>
                            <td>
                                <button class="ghd-btn ghd-btn-primary start-production-btn" data-order-id="<?php echo get_the_ID(); ?>" <?php if(!$can_initiate_production) echo 'disabled'; ?>>
                                    Iniciar Producción
                                </button>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else: 
                    ?>
                        <tr><td colspan="6" style="text-align:center;">No hay pedidos pendientes de asignación.</td></tr>
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
                        <th>Vendedora</th> <!-- NUEVA COLUMNA: Vendedora -->
                        <th>Producto</th>
                        <th>Material</th>
                        <th>Color</th>
                        <th>Observaciones</th>
                        <th>Estado General</th>
                        <th>Asignación/Completado por</th> <!-- CAMBIADO: Nombre de la columna -->
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