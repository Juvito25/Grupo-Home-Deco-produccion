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
                // --- LÓGICA CONDICIONAL CORREGIDA ---
                // Mostramos el botón de Remito SIEMPRE si el usuario es un administrador.
                if (current_user_can('manage_options')) :
                ?>
                    <a href="<?php echo get_stylesheet_directory_uri(); ?>/generar-remito.php?pedido_id=<?php echo get_the_ID(); ?>" 
                    class="ghd-btn ghd-btn-primary" 
                    target="_blank">
                    <i class="fa-solid fa-file-pdf"></i> Generar Remito
                    </a>
                <?php 
                // Si no es admin, aplicamos la lógica de sectores.
                else:
                    $sector_actual = get_field('sector_actual', get_the_ID());
                    $sectores_permitidos = array('Tapicería', 'Logística');
                    if (in_array($sector_actual, $sectores_permitidos)) :
                ?>
                    <a href="<?php echo get_stylesheet_directory_uri(); ?>/generar-remito.php?pedido_id=<?php echo get_the_ID(); ?>" 
                    class="ghd-btn ghd-btn-primary" 
                    target="_blank">
                    <i class="fa-solid fa-file-pdf"></i> Generar Remito
                    </a>
                <?php 
                    endif;
                endif; 
                ?>
            </div>
            <a href="<?php echo esc_url($volver_url); ?>" class="ghd-btn ghd-btn-tertiary"><i class="fa-solid fa-arrow-left"></i> <?php echo esc_html($volver_texto); ?></a>
        </header>

        <nav class="ghd-order-nav">
            <a href="#" class="active" data-tab="info-general"><i class="fa-solid fa-user"></i> Información General</a>
            <a href="#" data-tab="linea-tiempo"><i class="fa-solid fa-timeline"></i> Línea de Tiempo</a>
            <a href="#" data-tab="documentos"><i class="fa-solid fa-file-lines"></i> Documentos</a>
        </nav>

        <div class="ghd-order-details-grid">
            <!-- Columna Principal (Izquierda) -->
            <!-- INICIO DEL BLOQUE COMPLETO Y FINAL PARA LA COLUMNA PRINCIPAL -->
            <div class="order-details-main">

                <!-- Panel 1: Información General (Visible por defecto) -->
                <div id="tab-content-info-general" class="tab-content is-active">
                    <div class="ghd-card">
                        <div class="card-split-content">
                            <div class="customer-details">
                                <h3 class="card-section-title">Detalles del Cliente</h3>
                                <?php 
                                $cliente_email = get_field('cliente_email');
                                if ($cliente_email) {
                                    echo get_avatar($cliente_email, 64); 
                                }
                                ?>
                                <p class="customer-name"><?php echo esc_html(get_field('nombre_cliente')); ?></p>
                                <p><strong>Email:</strong> <?php echo esc_html($cliente_email); ?></p>
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

                <!-- Panel 2: Línea de Tiempo (Oculto por defecto) -->
                <div id="tab-content-linea-tiempo" class="tab-content">
                    <div class="ghd-card">
                        <h3 class="card-section-title">Historial de Producción</h3>
                        
                        <?php
                        // Hacemos una consulta para buscar todos los "posts" de historial
                        // que estén vinculados a esta orden de producción.
                        $historial_query = new WP_Query(array(
                            'post_type' => 'ghd_historial',
                            'posts_per_page' => -1,
                            'orderby' => 'date',
                            'order' => 'ASC', // Del más antiguo al más nuevo
                            'meta_query' => array(
                                array(
                                    'key' => '_orden_produccion_id',
                                    'value' => get_the_ID(),
                                )
                            )
                        ));

                        if ($historial_query->have_posts()) : ?>
                            <ul class="production-timeline">
                                <?php while ($historial_query->have_posts()) : $historial_query->the_post(); ?>
                                    <li>
                                        <div class="timeline-dot"></div>
                                        <div class="timeline-content">
                                            <span class="timeline-title"><?php the_title(); ?></span>
                                            <span class="timeline-date"><?php echo get_the_date('d/m/Y H:i'); ?></span>
                                        </div>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else : ?>
                            <p>Aún no hay eventos en el historial de este pedido.</p>
                        <?php endif; wp_reset_postdata(); ?>

                    </div>
                </div>
                <!-- Panel 3: Documentos (Oculto por defecto) -->
                <div id="tab-content-documentos" class="tab-content">
                    <div class="ghd-card">
                        <h3 class="card-section-title">Documentos Adjuntos</h3>
                        <p><em>(Funcionalidad futura: Aquí se podrán ver los remitos generados y otros archivos.)</em></p>
                    </div>
                </div>

            </div>
            <!-- FIN DEL BLOQUE COMPLETO -->

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