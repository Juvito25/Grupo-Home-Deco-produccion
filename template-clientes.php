<?php
/**
 * Template Name: GHD - Vista de Clientes
 */

// --- CONTROL DE ACCESO ---
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    auth_redirect();
}

get_header(); 

// --- LÓGICA PARA OBTENER CLIENTES ÚNICOS ---
$clientes = array();
$query = new WP_Query([
    'post_type' => 'orden_produccion',
    'posts_per_page' => -1,
    'fields' => 'ids' // Solo necesitamos los IDs, es más rápido
]);

if ($query->have_posts()) {
    foreach ($query->posts as $post_id) {
        $nombre_cliente = get_field('nombre_cliente', $post_id);
        if (!empty($nombre_cliente)) {
            $clientes[] = $nombre_cliente;
        }
    }
}
wp_reset_postdata();

// Filtramos para tener una lista única y la ordenamos alfabéticamente
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
                <?php if (!empty($clientes_unicos)) : ?>
                    <?php foreach ($clientes_unicos as $cliente) : 
                        // Creamos una URL que incluye un "hash" (#) que nuestro JavaScript podrá leer.
                        // Esto evita recargas de página y hace que el filtro se aplique vía AJAX.
                        $link_pedidos = home_url('/panel-de-control/#buscar=' . urlencode($cliente));
                    ?>
                        <div class="client-list-item">
                            <span class="client-name"><?php echo esc_html($cliente); ?></span>
                            <a href="<?php echo esc_url($link_pedidos); ?>" class="ghd-btn ghd-btn-secondary">
                                <i class="fa-solid fa-list-ul"></i> Ver Pedidos
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No se encontraron clientes.</p>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>
<?php get_footer(); ?>