<?php
/**
 * Template para Vista de Detalle (V3 Estable y Completo)
 */

if (!is_user_logged_in()) { auth_redirect(); }
get_header(); 
?>

<div class="ghd-app-wrapper">
    <?php 
    if (current_user_can('manage_options')) {
        get_template_part('template-parts/sidebar-admin');
    } else {
        get_template_part('template-parts/sidebar-sector');
    }
    ?>

    <main class="ghd-main-content">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

        <header class="ghd-main-header">
            <h2>Detalles del Pedido: <?php the_title(); ?></h2>
        </header>

        <div class="ghd-details-grid">
            <!-- Columna Principal -->
            <div class="details-main">
                <div class="ghd-card">
                    <h3 class="card-section-title">Información del Cliente</h3>
                    <p><strong>Nombre:</strong> <?php echo esc_html(get_field('nombre_cliente')); ?></p>
                    <p><strong>Email:</strong> <?php echo esc_html(get_field('cliente_email')); ?></p>
                    <p><strong>Teléfono:</strong> <?php echo esc_html(get_field('cliente_telefono')); ?></p>
                </div>
                <div class="ghd-card">
                    <h3 class="card-section-title">Información del Producto</h3>
                    <?php $img_url = get_field('imagen_del_producto'); if($img_url): ?>
                        <img src="<?php echo esc_url($img_url); ?>" style="max-width:150px; border-radius:8px; margin-top:1rem;">
                    <?php endif; ?>
                    <p><strong>Producto:</strong> <?php echo esc_html(get_field('nombre_producto')); ?></p>
                    <p><strong>Especificaciones:</strong> <?php echo nl2br(esc_html(get_field('especificaciones_producto'))); ?></p>
                </div>
            </div>

            <!-- Columna Lateral -->
            <div class="details-sidebar">
                <div class="ghd-card">
                    <h3 class="card-section-title">Estado General</h3>
                    <p><strong>Estado:</strong> <?php echo esc_html(get_field('estado_pedido')); ?></p>
                    <p><strong>Prioridad:</strong> <?php echo esc_html(get_field('prioridad_pedido')); ?></p>
                </div>
                <div class="ghd-card">
                    <h3 class="card-section-title">Sub-estados de Producción</h3>
                    <ul class="sub-status-list">
                        <li><strong>Carpintería:</strong> <?php echo esc_html(get_field('estado_carpinteria')); ?></li>
                        <li><strong>Corte:</strong> <?php echo esc_html(get_field('estado_corte')); ?></li>
                        <li><strong>Costura:</strong> <?php echo esc_html(get_field('estado_costura')); ?></li>
                        <li><strong>Tapicería:</strong> <?php echo esc_html(get_field('estado_tapiceria')); ?></li>
                        <li><strong>Embalaje:</strong> <?php echo esc_html(get_field('estado_embalaje')); ?></li>
                        <li><strong>Logística:</strong> <?php echo esc_html(get_field('estado_logistica')); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php endwhile; endif; ?>
    </main>
</div>
<?php get_footer(); ?>