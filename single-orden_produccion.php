<?php
/**
 * Template para mostrar la vista de detalle de una Orden de Producción.
 * Versión V2 con diseño profesional.
 */

if (!is_user_logged_in()) { auth_redirect(); }
get_header();

// --- Lógica para el botón "Volver" ---
$volver_url = home_url('/'); $volver_texto = 'Volver';
if (current_user_can('manage_options')) {
    $pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-admin-dashboard.php']);
    if (!empty($pages)) { $volver_url = get_permalink($pages[0]); $volver_texto = 'Volver al Panel'; }
} else {
    $pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-sector-dashboard.php']);
    if (!empty($pages)) { $volver_url = get_permalink($pages[0]); $volver_texto = 'Volver a Mis Tareas'; }
}
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
        <?php if (have_posts()) : while (have_posts()) : the_post(); 
            $prioridad = get_field('prioridad_pedido');
            $prioridad_class = ($prioridad == 'Alta') ? 'tag-red' : (($prioridad == 'Media') ? 'tag-yellow' : 'tag-green');
        ?>

        <header class="ghd-order-header">
            <div class="order-header-info">
                <h1>Pedido #<?php the_title(); ?></h1>
                <span class="order-meta-item"><i class="fa-solid fa-calendar"></i> Creado: <?php echo get_the_date('d/m/Y'); ?></span>
                <span class="order-meta-item"><i class="fa-solid fa-user"></i> Cliente: <?php echo esc_html(get_field('nombre_cliente')); ?></span>
                <span class="ghd-tag <?php echo esc_attr($prioridad_class); ?>">Prioridad <?php echo esc_html($prioridad); ?></span>
            </div>
            <div class="header-actions">
                <button class="ghd-btn ghd-btn-secondary"><i class="fa-solid fa-pen"></i> Editar Pedido</button>
                
                <?php
                // --- LÓGICA CONDICIONAL PARA MOSTRAR EL BOTÓN DE REMITO ---
                $sector_actual = get_field('sector_actual', get_the_ID());
                $sectores_permitidos = array('Tapicería', 'Logística');

                // Mostramos el botón si el usuario es admin O si el pedido está en un sector permitido.
                if (current_user_can('manage_options') || in_array($sector_actual, $sectores_permitidos)) :
                ?>
                    <a href="<?php echo get_stylesheet_directory_uri(); ?>/generar-remito.php?pedido_id=<?php echo get_the_ID(); ?>" 
                    class="ghd-btn ghd-btn-primary" 
                    target="_blank">
                    <i class="fa-solid fa-file-pdf"></i> Generar Remito
                    </a>
                <?php endif; ?>
            </div>
        </header>

        <nav class="ghd-order-nav">
            <a href="#" class="active"><i class="fa-solid fa-user"></i> Customer Information</a>
            <a href="#"><i class="fa-solid fa-box"></i> Product Specifications</a>
            <a href="#"><i class="fa-solid fa-timeline"></i> Production Timeline</a>
        </nav>

        <div class="ghd-order-details-grid">
            <!-- Columna Principal (Izquierda) -->
            <div class="order-details-main">
                <div class="ghd-card">
                    <div class="card-split-content">
                        <div class="customer-details">
                            <h3 class="card-section-title">Detalles del Cliente</h3>
                            <?php echo get_avatar(get_field('cliente_email'), 64); ?>
                            <p class="customer-name"><?php echo esc_html(get_field('nombre_cliente')); ?></p>
                            <p><strong>Email:</strong> <?php echo esc_html(get_field('cliente_email')); ?></p>
                            <p><strong>Teléfono:</strong> <?php echo esc_html(get_field('cliente_telefono')); ?></p>
                            <p><strong>ID Cliente:</strong> <?php echo esc_html(get_field('id_de_cliente')); ?></p>
                        </div>
                        <div class="delivery-address">
                            <h3 class="card-section-title">Dirección de Entrega</h3>
                            <address><?php echo nl2br(esc_html(get_field('direccion_de_entrega'))); ?></address>
                            <p><strong>Instrucciones:</strong> <?php echo esc_html(get_field('instrucciones_de_entrega')); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Lateral (Derecha) -->
            <div class="order-details-sidebar">
                <div class="ghd-card">
                    <h3 class="card-section-title">Resumen del Pedido</h3>
                    <?php 
                        $total = get_field('valor_total_del_pedido') ?: 0;
                        $pagado = get_field('sena_pagada') ?: 0;
                        $saldo = $total - $pagado;
                    ?>
                    <p class="summary-item"><span>Total:</span> <strong>$<?php echo number_format($total, 2); ?></strong></p>
                    <p class="summary-item"><span>Pagado:</span> <span>$<?php echo number_format($pagado, 2); ?></span></p>
                    <p class="summary-item"><span>Saldo:</span> <strong>$<?php echo number_format($saldo, 2); ?></strong></p>
                </div>
                <div class="ghd-card">
                    <h3 class="card-section-title">Control de Estado</h3>
                    <?php 
                    $next_sector = ghd_get_next_sector(get_field('sector_actual'));
                    if ($next_sector && $next_sector !== 'Completado') : ?>
                        <button 
                            class="ghd-btn ghd-btn-primary move-to-next-sector-btn" 
                            data-order-id="<?php echo get_the_ID(); ?>"
                            data-nonce="<?php echo wp_create_nonce('ghd_move_order_nonce'); ?>">
                            Avanzar a <?php echo esc_html($next_sector); ?>
                        </button>
                    <?php else: ?>
                        <p>Este pedido ha completado el flujo de producción.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php endwhile; endif; ?>
    </main>
</div>
<?php get_footer(); ?>