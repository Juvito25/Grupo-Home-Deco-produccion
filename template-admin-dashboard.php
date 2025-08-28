<?php /* Template Name: GHD - Panel de Administrador (V3 Estable) */
if (!is_user_logged_in() || !current_user_can('manage_options')) { auth_redirect(); }
get_header(); ?>
<div class="ghd-app-wrapper">
    <?php get_template_part('template-parts/sidebar-admin'); ?>
    <main class="ghd-main-content">
        <header class="ghd-main-header"><h2>Pedidos Pendientes de Asignación</h2></header>
        <div class="ghd-card">
            <table class="ghd-table">
                <thead><tr><th>Código</th><th>Cliente</th><th>Producto</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php $query = new WP_Query(['post_type' => 'orden_produccion', 'posts_per_page' => -1, 'meta_query' => [['key' => 'estado_pedido', 'value' => 'Pendiente de Asignación']]]);
                if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post(); ?>
                    <tr id="order-row-<?php echo get_the_ID(); ?>">
                        <td><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></td>
                        <td><?php echo esc_html(get_field('nombre_cliente')); ?></td>
                        <td><?php echo esc_html(get_field('nombre_producto')); ?></td>
                        <td><button class="ghd-btn ghd-btn-primary start-production-btn" data-order-id="<?php echo get_the_ID(); ?>">Iniciar Producción</button></td>
                    </tr>
                <?php endwhile; else: echo '<tr><td colspan="4">No hay pedidos pendientes.</td></tr>'; endif; wp_reset_postdata(); ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<?php get_footer(); ?>