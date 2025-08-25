<?php
/**
 * Template para mostrar la vista de detalle de una Orden de Producción.
 * Versión V2 con diseño profesional.
 */

// --- Lógica para el botón "Volver" (Estable) ---
if (!is_user_logged_in()) { auth_redirect(); }
get_header();

$volver_url = home_url('/'); $volver_texto = 'Volver al Inicio';
if (current_user_can('manage_options')) {
    $admin_pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-admin-dashboard.php']);
    if (!empty($admin_pages)) { $volver_url = get_permalink($admin_pages[0]); $volver_texto = 'Volver al Panel'; }
} else {
    $sector_pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-sector-dashboard.php']);
    if (!empty($sector_pages)) { $volver_url = get_permalink($sector_pages[0]); $volver_texto = 'Volver a Mis Tareas'; }
}
?>

<div class="ghd-app-wrapper">
    <?php 
    // Mostramos el sidebar correcto según el rol
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

        <!-- NUEVA CABECERA DE PEDIDO -->
        <header class="ghd-order-header">
            <div class="order-header-info">
                <h1>Pedido #<?php the_title(); ?></h1>
                <span class="ghd-tag <?php echo esc_attr($prioridad_class); ?>">Prioridad <?php echo esc_html($prioridad); ?></span>
            </div>
            <div class="header-actions">
                <button class="ghd-btn ghd-btn-secondary"><i class="fa-solid fa-pen"></i> Editar Pedido</button>
                <button class="ghd-btn ghd-btn-primary"><i class="fa-solid fa-file-invoice"></i> Generar Factura</button>
            </div>
        </header>

        <!-- NUEVA NAVEGACIÓN POR PESTAÑAS -->
        <nav class="ghd-order-nav">
            <a href="#" class="active">Información General</a>
            <a href="#">Línea de Tiempo</a>
            <a href="#">Documentos</a>
        </nav>

        <!-- NUEVA ESTRUCTURA DE DOS COLUMNAS -->
        <div class="ghd-order-details-grid">
            
            <!-- Columna Principal (Izquierda) -->
            <div class="order-details-main">
                <div class="ghd-card">
                    <h3 class="card-section-title">Detalles del Cliente</h3>
                    <p><strong>Nombre:</strong> <?php echo esc_html(get_field('nombre_cliente')); ?></p>
                    <p><strong>Email:</strong> <?php echo esc_html(get_field('cliente_email')); ?></p>
                    <p><strong>Teléfono:</strong> <?php echo esc_html(get_field('cliente_telefono')); ?></p>
                </div>
                 <div class="ghd-card">
                    <h3 class="card-section-title">Detalles del Producto</h3>
                    <?php $imagen_url = get_field('imagen_del_producto'); if ($imagen_url): ?>
                        <img src="<?php echo esc_url($imagen_url); ?>" alt="Producto" style="max-width: 150px; border-radius: 8px; margin-bottom: 1rem;">
                    <?php endif; ?>
                    <p><strong>Producto:</strong> <?php echo esc_html(get_field('nombre_producto')); ?></p>
                    <p><strong>Especificaciones:</strong> <?php echo nl2br(esc_html(get_field('especificaciones_producto'))); ?></p>
                </div>
            </div>

            <!-- Columna Lateral (Derecha) -->
            <div class="order-details-sidebar">
                <div class="ghd-card">
                    <h3 class="card-section-title">Estado Actual</h3>
                    <p><strong>Sector:</strong> <?php echo esc_html(get_field('sector_actual')); ?></p>
                </div>
                 <div class="ghd-card">
                    <h3 class="card-section-title">Control de Estado</h3>
                    <button class="ghd-btn ghd-btn-primary move-to-next-sector-btn" data-order-id="<?php echo get_the_ID(); ?>" data-nonce="<?php echo wp_create_nonce('ghd_move_order_nonce'); ?>">
                        Avanzar a <?php echo esc_html(ghd_get_next_sector(get_field('sector_actual'))); ?>
                    </button>
                    <button class="ghd-btn ghd-btn-tertiary">Reportar Problema</button>
                </div>
                <div class="ghd-card">
                    <h3 class="card-section-title">Acciones Rápidas</h3>
                    <div class="quick-actions">
                        <a href="<?php echo esc_url($volver_url); ?>" class="ghd-btn ghd-btn-secondary">Volver a la Lista</a>
                        <button class="ghd-btn ghd-btn-secondary">Generar Remito</button>
                    </div>
                </div>
            </div>
        </div>

        <?php endwhile; endif; ?>
    </main>
</div>
<?php get_footer(); ?>