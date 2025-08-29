<?php
/**
 * Template Name: GHD - Info de Cliente
 */

// --- CONTROL DE ACCESO ---
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    auth_redirect();
}

// 1. Verificamos que se haya pasado un nombre de cliente en la URL.
if (!isset($_GET['cliente'])) {
    wp_die('No se ha especificado un cliente.');
}

$cliente_nombre = sanitize_text_field(urldecode($_GET['cliente']));

// 2. Hacemos la consulta para encontrar todos los pedidos de este cliente.
$args = array(
    'post_type'      => 'orden_produccion',
    'posts_per_page' => -1,
    'meta_query'     => array(
        array(
            'key'     => 'nombre_cliente',
            'value'   => $cliente_nombre,
            'compare' => '=',
        ),
    ),
);
$pedidos_query = new WP_Query($args);

get_header(); 
?>

<div class="ghd-app-wrapper">
    <?php get_template_part('template-parts/sidebar-admin'); ?>
    <main class="ghd-main-content">
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <h2>Info del Cliente: <?php echo esc_html($cliente_nombre); ?></h2>
            </div>
        </header>

        <div class="ghd-card">
            <h3 class="card-section-title">Historial de Pedidos</h3>
            <div class="ghd-table-wrapper">
                <table class="ghd-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pedidos_query->have_posts()) : while ($pedidos_query->have_posts()) : $pedidos_query->the_post(); ?>
                            <tr>
                                <td><strong><?php the_title(); ?></strong></td>
                                <td><?php echo esc_html(get_field('nombre_producto')); ?></td>
                                <td><?php echo esc_html(get_field('estado_pedido')); ?></td>
                                <td><?php echo esc_html(get_field('fecha_del_pedido')); ?></td>
                                <td>
                                    <a href="<?php the_permalink(); ?>" class="ghd-btn-icon" title="Ver Detalles">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="5" style="text-align:center;">Este cliente no tiene pedidos.</td></tr>
                        <?php endif; wp_reset_postdata(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php get_footer(); ?>