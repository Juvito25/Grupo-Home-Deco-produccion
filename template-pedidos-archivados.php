<?php
/**
 * Template Name: GHD - Pedidos Archivados
 */

// Seguridad: Solo Admin y Control Final pueden ver esta página
if ( ! is_user_logged_in() || ( ! current_user_can('manage_options') && ! current_user_can('control_final_macarena') ) ) {
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
                <h2>Pedidos Archivados</h2>
            </div> 
            <div class="header-actions">
                <button onclick="window.location.reload();" class="ghd-btn ghd-btn-secondary"><i class="fa-solid fa-sync"></i> <span>Refrescar</span></button>
            </div>
        </header>

        <div class="ghd-card ghd-table-wrapper">
            <table class="ghd-table">
               <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Producto</th>
                        <th>Fecha de Archivo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="ghd-archived-orders-table-body">
                    <?php
                    $args_archivados = array(
                        'post_type'      => 'orden_produccion',
                        'posts_per_page' => -1,
                        'meta_query'     => array(
                            array(
                                'key'     => 'estado_pedido',
                                'value'   => 'Completado y Archivado',
                                'compare' => '=',
                            ),
                        ),
                        'orderby' => 'modified', // Ordenar por la fecha de última modificación (cuando se archivó)
                        'order'   => 'DESC',     // Los más recientes primero
                    );
                    $pedidos_archivados_query = new WP_Query($args_archivados);

                    if ($pedidos_archivados_query->have_posts()) :
                        while ($pedidos_archivados_query->have_posts()) : $pedidos_archivados_query->the_post();
                            $order_id = get_the_ID();
                    ?>
                        <tr id="order-row-archived-<?php echo $order_id; ?>">
                            <td><a href="<?php the_permalink(); ?>" style="color: var(--color-rojo); font-weight: 600;"><?php the_title(); ?></a></td>
                            <td><?php echo esc_html(get_field('nombre_cliente', $order_id)); ?></td>
                            <td><?php echo esc_html(get_field('nombre_producto', $order_id)); ?></td>
                            <td><?php echo get_the_modified_date('d/m/Y H:i', $order_id); ?></td>
                            <td>
                                <a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary ghd-btn-small">
                                    <i class="fa-solid fa-eye"></i> Ver Detalles
                                </a>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else: 
                    ?>
                        <tr><td colspan="5" style="text-align:center;">No hay pedidos archivados.</td></tr>
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