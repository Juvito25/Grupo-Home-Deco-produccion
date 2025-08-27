<?php
/**
 * Template Name: GHD - Vista de Sectores
 * Versión 2.1 - Añadida la cabecera principal.
 */

// --- CONTROL DE ACCESO ---
if (!is_user_logged_in()) {
    auth_redirect();
}
// Un usuario que no es administrador NO DEBE VER esta página.
if (!current_user_can('manage_options')) {
    wp_redirect(home_url('/mi-puesto/'));
    exit;
}

// --- COMIENZO DE LA VISTA ---
get_header(); // <-- ESTA LÍNEA AHORA ESTÁ EN SU LUGAR CORRECTO
?>

<div class="ghd-app-wrapper">
    
    <?php get_template_part('template-parts/sidebar-admin'); ?>

    <main class="ghd-main-content">
        
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <h2>Sectores de Producción</h2>
            </div> 
        </header>

        <div class="ghd-sector-card-grid">
            <?php
            // Usamos nuestra función de ayuda para obtener la lista de sectores
            $sectores = ghd_get_sectores_produccion();
            
            foreach ($sectores as $sector) :
                // Hacemos una consulta rápida para contar los pedidos en este sector
                $query = new WP_Query([
                    'post_type' => 'orden_produccion',
                    'post_status' => 'publish',
                    'meta_key' => 'sector_actual',
                    'meta_value' => $sector,
                    'posts_per_page' => -1
                ]);
                $pedidos_en_sector = $query->post_count;
                wp_reset_postdata();
            ?>
                <div class="ghd-sector-card">
                    <h3 class="sector-card-title"><?php echo esc_html($sector); ?></h3>
                    <p class="sector-card-stat">Pedidos Activos: <?php echo $pedidos_en_sector; ?></p>
                    <a href="#" class="ghd-btn ghd-btn-secondary">Ver Panel</a>
                </div>
            <?php endforeach; ?>
        </div>

    </main>
</div>
<?php get_footer(); ?>