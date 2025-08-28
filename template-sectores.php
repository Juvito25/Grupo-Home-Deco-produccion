<?php
/**
 * Template Name: GHD - Vista de Sectores
 * Versión final y funcional.
 */

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    auth_redirect();
}
get_header(); 
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
            $panel_sector_url = home_url('/mis-tareas/');
            
            // CORRECCIÓN CLAVE 1: Usamos el nombre de función correcto y unificado: ghd_get_sectores()
            $sectores = ghd_get_sectores();
            
            foreach ($sectores as $sector) :
                // La lógica para construir el nombre del campo a partir del nombre del sector
                $clean_sector_name = strtolower($sector);
                $clean_sector_name = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $clean_sector_name);
                $campo_estado = 'estado_' . $clean_sector_name;

                // CORRECCIÓN CLAVE 2: La consulta ahora cuenta 'Pendiente' y 'En Progreso'
                $query = new WP_Query([
                    'post_type'      => 'orden_produccion',
                    'posts_per_page' => -1,
                    'meta_query'     => [
                        [
                            'key'     => $campo_estado,
                            'value'   => ['Pendiente', 'En Progreso'],
                            'compare' => 'IN',
                        ]
                    ]
                ]);
                $pedidos_en_sector = $query->post_count;
                wp_reset_postdata();

                $link_al_panel = add_query_arg('sector', urlencode($sector), $panel_sector_url);
            ?>
                <div class="ghd-sector-card">
                    <h3 class="sector-card-title"><?php echo esc_html($sector); ?></h3>
                    <p class="sector-card-stat">Pedidos Activos: <?php echo $pedidos_en_sector; ?></p>
                    <a href="<?php echo esc_url($link_al_panel); ?>" class="ghd-btn ghd-btn-secondary">Ver Panel</a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
<?php get_footer(); ?>