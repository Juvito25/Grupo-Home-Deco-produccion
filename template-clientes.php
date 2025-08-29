<?php
/**
 * Template Name: GHD - Vista de Clientes (V2 - Diseño Mejorado)
 */
if (!is_user_logged_in() || !current_user_can('manage_options')) { auth_redirect(); }
get_header(); 

// --- Lógica para obtener clientes (sin cambios) ---
$clientes = array();
$query = new WP_Query(['post_type' => 'orden_produccion', 'posts_per_page' => -1, 'fields' => 'ids']);
if ($query->have_posts()) {
    foreach ($query->posts as $post_id) {
        $nombre_cliente = get_field('nombre_cliente', $post_id);
        if (!empty($nombre_cliente)) { $clientes[] = $nombre_cliente; }
    }
}
wp_reset_postdata();
$clientes_unicos = array_unique($clientes);
sort($clientes_unicos);
?>

<div class="ghd-app-wrapper">
    <?php get_template_part('template-parts/sidebar-admin'); ?>
    <main class="ghd-main-content">
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <h2>Listado de Clientes</h2>
            </div> 
        </header>

        <div class="ghd-card">
            <div class="client-list">
                <?php if (!empty($clientes_unicos)) : foreach ($clientes_unicos as $cliente) : 
                    // CORRECCIÓN: Usamos un parámetro de URL estándar (?buscar=)
                    $link_pedidos = add_query_arg('cliente', urlencode($cliente), home_url('/info-cliente/'));
                ?>
                    <div class="client-list-item">
                        <span class="client-name"><?php echo esc_html($cliente); ?></span>
                        
                        <!-- CORRECCIÓN: Cambiado el icono y el texto del botón -->
                        <a href="<?php echo esc_url($link_pedidos); ?>" class="ghd-btn ghd-btn-secondary">
                            <i class="fa-solid fa-user"></i> <span>Info del Cliente</span>
                        </a>
                    </div>
                <?php endforeach; else: ?>
                    <p>No se encontraron clientes.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<?php get_footer(); ?>