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
            // Obtener la URL base del panel de tareas del sector
            $sector_dashboard_page = get_posts([
                'post_type'  => 'page',
                'fields'     => 'ids',
                'nopaging'   => true,
                'meta_key'   => '_wp_page_template',
                'meta_value' => 'template-sector-dashboard.php'
            ]);
            $panel_sector_base_url = !empty($sector_dashboard_page) ? get_permalink($sector_dashboard_page[0]) : home_url();
            
            //  Usamos ghd_get_sectores() que sí existe en functions.php
                    // Obtener la URL base del panel de tareas del sector
            $sector_dashboard_page = get_posts([
                'post_type'  => 'page',
                'fields'     => 'ids',
                'nopaging'   => true,
                'meta_key'   => '_wp_page_template',
                'meta_value' => 'template-sector-dashboard.php'
            ]);
            $panel_sector_base_url = !empty($sector_dashboard_page) ? get_permalink($sector_dashboard_page[0]) : home_url();
            
            // CORRECCIÓN CLAVE: Usamos ghd_get_sectores() que ahora devuelve ['clave' => 'Nombre legible']
            $sectores_map = ghd_get_sectores(); // Obtener el array asociativo clave => nombre legible
            
            foreach ($sectores_map as $sector_key => $sector_display_name) : // Iteramos sobre la clave y el nombre legible
                // La clave (ej. 'carpinteria') ya está normalizada (minúsculas, sin tildes)
                $campo_estado = 'estado_' . $sector_key; // Usamos la clave limpia para construir el campo_estado

                $query = new WP_Query([
                    'post_type'      => 'orden_produccion',
                    'posts_per_page' => -1,
                    'meta_query'     => [
                        [
                            'key'     => $campo_estado,
                            'value'   => ['Pendiente', 'En Progreso'], // Mostrar también 'En Progreso' para un conteo más real
                            'compare' => 'IN',
                        ]
                    ]
                ]);
                $pedidos_en_sector = $query->post_count;
                wp_reset_postdata();

                // Construir el link al panel de tareas de este sector específico
                // ENVIAMOS LA CLAVE NORMALIZADA (ej. 'carpinteria') en la URL
                $link_al_panel = add_query_arg('sector', urlencode($sector_key), $panel_sector_base_url);
            ?>
                <div class="ghd-sector-card">
                    <h3 class="sector-card-title"><?php echo esc_html($sector_display_name); ?></h3>
                    <p class="sector-card-stat">Pedidos Activos: <?php echo $pedidos_en_sector; ?></p>
                    <a href="<?php echo esc_url($link_al_panel); ?>" class="ghd-btn ghd-btn-primary">Ver Panel</a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
<?php get_footer(); ?>