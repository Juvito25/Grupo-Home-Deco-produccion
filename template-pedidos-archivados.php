<?php
/**
 * Template Name: GHD - Pedidos Archivados
 * Descripción: Muestra una lista de todos los pedidos que han sido archivados.
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
                <h2>Pedidos Archivados</h2>
            </div> 
            <div class="header-actions">
                <!-- Aquí podrías añadir un botón de exportar la lista de archivados, o filtros -->
                <button id="ghd-refresh-archived-orders" class="ghd-btn ghd-btn-secondary"><i class="fa-solid fa-sync"></i> <span>Refrescar</span></button>
            </div>
        </header>

        <!-- TABLA DE PEDIDOS ARCHIVADOS -->
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
                        'orderby' => 'modified', // Ordenar por la última modificación (fecha de archivo)
                        'order'   => 'DESC',
                    );
                    $pedidos_archivados_query = new WP_Query($args_archivados);

                    if ($pedidos_archivados_query->have_posts()) :
                        while ($pedidos_archivados_query->have_posts()) : $pedidos_archivados_query->the_post();
                            $order_id = get_the_ID();
                            $remito_page_id = get_posts([
                                'post_type'  => 'page',
                                'fields'     => 'ids',
                                'nopaging'   => true,
                                'meta_key'   => '_wp_page_template',
                                'meta_value' => 'template-remito.php'
                            ]);
                            $remito_base_url = !empty($remito_page_id) ? get_permalink($remito_page_id[0]) : home_url();
                            $remito_url = esc_url( add_query_arg( 'order_id', $order_id, $remito_base_url ) );
                    ?>
                        <tr id="order-row-archived-<?php echo $order_id; ?>">
                            <td><a href="<?php the_permalink(); ?>" style="color: var(--color-rojo); font-weight: 600;"><?php the_title(); ?></a></td>
                            <td><?php echo esc_html(get_field('nombre_cliente', $order_id)); ?></td>
                            <td><?php echo esc_html(get_field('nombre_producto', $order_id)); ?></td>
                            <td><?php echo esc_html(get_field('fecha_de_archivo_pedido', $order_id)); ?></td> <!-- Muestra la fecha del nuevo campo ACF -->
                            <td>
                                <a href="<?php echo $remito_url; ?>" target="_blank" class="ghd-btn ghd-btn-secondary ghd-btn-small generate-remito-btn" data-order-id="<?php echo $order_id; ?>">
                                    <i class="fa-solid fa-file-invoice"></i> Remito
                                </a>
                                <a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary ghd-btn-small">
                                    <i class="fa-solid fa-eye"></i> Detalles
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