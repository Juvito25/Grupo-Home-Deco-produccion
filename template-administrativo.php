<?php
/**
 * Template Name: GHD - Panel Administrativo
 */

// --- CONTROL DE ACCESO ---
// Solo pueden ver esta página los administradores o los usuarios con el rol 'rol_administrativo'.
if (!is_user_logged_in() || (!current_user_can('manage_options') && !current_user_can('rol_administrativo'))) {
    auth_redirect();
}

get_header(); 
?>

<div class="ghd-app-wrapper">
    
    <?php get_template_part('template-parts/sidebar-admin'); ?>

    <main class="ghd-main-content">
        
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <h2>Pedidos Pendientes de Cierre</h2>
            </div> 
        </header>
        <div class="ghd-kpi-grid">
            <div class="ghd-kpi-card">
                <div class="kpi-icon icon-blue"><i class="fa-solid fa-list-check"></i></div>
                <div class="kpi-info">
                    <span class="kpi-value" id="kpi-activas"><?php echo $total_pedidos; ?></span>
                    <span class="kpi-label">Activas</span>
                </div>
            </div>
            <div class="ghd-kpi-card">
                <div class="kpi-icon icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="kpi-info">
                    <span class="kpi-value" id="kpi-prioridad-alta"><?php echo $total_prioridad_alta; ?></span>
                    <span class="kpi-label">Prioridad Alta</span>
                </div>
            </div>
            <div class="ghd-kpi-card">
                <div class="kpi-icon icon-green"><i class="fa-solid fa-check"></i></div>
                <div class="kpi-info">
                    <span class="kpi-value" id="kpi-completadas-hoy">0</span>
                    <span class="kpi-label">Completadas Hoy</span>
                </div>
            </div>
            <div class="ghd-kpi-card">
                <div class="kpi-icon icon-yellow"><i class="fa-solid fa-clock"></i></div>
                <div class="kpi-info">
                    <span class="kpi-value" id="kpi-tiempo-promedio"><?php echo esc_html($tiempo_promedio_str); ?></span>
                    <span class="kpi-label">Tiempo Promedio</span>
                </div>
            </div>
        </div>
        <div class="ghd-card ghd-table-wrapper">
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
                <tbody>
                    <?php
                    // Buscamos todos los pedidos cuyo estado administrativo sea "Pendiente"
                    $args = array(
                        'post_type'      => 'orden_produccion',
                        'posts_per_page' => -1,
                        'meta_query'     => array(
                            array(
                                'key'     => 'estado_administrativo',
                                'value'   => 'Pendiente',
                                'compare' => '=',
                            ),
                        ),
                    );
                    $pedidos_query = new WP_Query($args);

                    if ($pedidos_query->have_posts()) :
                        while ($pedidos_query->have_posts()) : $pedidos_query->the_post();
                    ?>
                        <tr id="order-row-<?php echo get_the_ID(); ?>">
                            <td><strong><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></strong></td>
                            <td><?php echo esc_html(get_field('nombre_cliente')); ?></td>
                            <td><?php echo esc_html(get_field('nombre_producto')); ?></td>
                            <td><?php echo esc_html(get_field('fecha_pedido')); ?></td>
                            <td>
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