<?php
/**
 * Template Name: GHD - Panel de Administrador
 * Versión V2 - Definitiva y Funcional
 */

if (!is_user_logged_in() || !current_user_can('manage_options')) {
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
                <h2>Pedidos Pendientes de Asignación</h2>
            </div> 
            <div class="header-actions">
                <button class="ghd-btn ghd-btn-secondary"><i class="fa-solid fa-download"></i> <span>Exportar</span></button>
                <button class="ghd-btn ghd-btn-primary"><i class="fa-solid fa-plus"></i> <span>Nuevo Pedido</span></button>
            </div>
        </header>

        <!-- TABLA DE PEDIDOS -->
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
                    // CONSULTA CORREGIDA: Busca solo los pedidos pendientes de asignación
                    $args = array(
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
                    $pedidos_query = new WP_Query($args);

                    if ($pedidos_query->have_posts()) :
                        while ($pedidos_query->have_posts()) : $pedidos_query->the_post();
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
    </main>
</div>
<?php get_footer(); ?>