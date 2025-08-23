<?php
/**
 * Template para mostrar la vista de detalle de una Orden de Producción.
 * Versión corregida y final.
 */

// --- LÓGICA MEJORADA PARA EL BOTÓN "VOLVER" ---
// Determinamos a qué panel debe volver el usuario basándonos en su rol.
$volver_url = home_url('/'); // URL por defecto si todo falla.
$volver_texto = 'Volver al Inicio';

if (current_user_can('manage_options')) {
    // Si es admin, buscamos la URL de su panel.
    $admin_pages = get_posts(array(
        'post_type'  => 'page', 'fields' => 'ids', 'nopaging' => true,
        'meta_key' => '_wp_page_template', 'meta_value' => 'template-admin-dashboard.php'
    ));
    if (!empty($admin_pages)) {
        $volver_url = get_permalink($admin_pages[0]);
        $volver_texto = 'Volver al Panel de Admin';
    }
} else {
    // Si es otro rol (trabajador), buscamos la URL del panel de sector.
    $sector_pages = get_posts(array(
        'post_type'  => 'page', 'fields' => 'ids', 'nopaging' => true,
        'meta_key' => '_wp_page_template', 'meta_value' => 'template-sector-dashboard.php'
    ));
    if (!empty($sector_pages)) {
        $volver_url = get_permalink($sector_pages[0]);
        $volver_texto = 'Volver a Mis Tareas';
    }
}

get_header();
?>

<div class="ghd-app-wrapper">
    <!-- El sidebar se mantiene para consistencia -->
    <aside class="ghd-sidebar">
        <?php 
        // Mostramos el sidebar correspondiente al rol del usuario
        if (current_user_can('manage_options')) {
            get_template_part('template-parts/sidebar-admin');
        } else {
            get_template_part('template-parts/sidebar-sector');
        }
        ?>
    </aside>

    <main class="ghd-main-content">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <h2>Detalles del Pedido: <?php the_title(); ?></h2>
            </div> 
            <div class="header-actions">
                <a href="<?php echo esc_url($volver_url); ?>" class="ghd-btn ghd-btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> <span><?php echo esc_html($volver_texto); ?></span>
                </a>
            </div>
        </header>

        <div class="ghd-single-order-grid">
            <!-- Columna Izquierda -->
            <div class="ghd-card">
                <h3 class="card-section-title">Información del Cliente</h3>
                <p><strong>Nombre:</strong> <?php echo esc_html(get_field('nombre_cliente')); ?></p>
                <p><strong>Email:</strong> <a href="mailto:<?php echo esc_attr(get_field('cliente_email')); ?>"><?php echo esc_html(get_field('cliente_email')); ?></a></p>
                <p><strong>Teléfono:</strong> <?php echo esc_html(get_field('cliente_telefono')); ?></p>
            </div>
            
            <div class="ghd-card">
                 <h3 class="card-section-title">Detalles del Producto</h3>
                 <?php 
                 // CORRECCIÓN 1: Usamos el nombre de campo correcto 'imagen_del_producto'
                 $imagen_producto_url = get_field('imagen_del_producto');
                 if ($imagen_producto_url) : ?>
                    <img src="<?php echo esc_url($imagen_producto_url); ?>" alt="Producto" style="max-width: 150px; border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--color-borde);">
                 <?php endif; ?>
                 
                 <!-- CORRECCIÓN 2: Mostramos los campos que faltaban -->
                 <p><strong>Producto:</strong> <?php echo esc_html(get_field('nombre_producto')); ?></p>
                 <p><strong>Especificaciones:</strong> <?php echo nl2br(esc_html(get_field('especificaciones_producto'))); ?></p>
            </div>

            <!-- Columna Derecha -->
            <div class="ghd-card">
                <h3 class="card-section-title">Estado y Prioridad</h3>
                <p><strong>Estado Actual:</strong> <?php echo esc_html(get_field('estado_pedido')); ?></p>
                <p><strong>Sector Actual:</strong> <?php echo esc_html(get_field('sector_actual')); ?></p>
                <p><strong>Prioridad:</strong> <?php echo esc_html(get_field('prioridad_pedido')); ?></p>
            </div>
        </div>

        <?php endwhile; endif; ?>
    </main>
</div>

<?php get_footer(); ?>