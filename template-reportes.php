<?php
/**
 * Template Name: GHD - Reportes
 * Descripción: Panel de reportes para Admin y Gerencia de Ventas.
 */

// Redirección de seguridad: Asegurar que solo Admin y Gerente de Ventas accedan
if ( ! is_user_logged_in() || ( ! current_user_can('gerente_ventas') && ! current_user_can('manage_options') ) ) {
    auth_redirect(); 
}

get_header(); 

$current_user = wp_get_current_user();
$es_admin = current_user_can('manage_options');
$es_gerente_ventas = current_user_can('gerente_ventas');
?>

<div class="ghd-app-wrapper">
    
    <?php 
    // --- NUEVO: Incluir el sidebar condicionalmente ---
    if ($es_admin) {
        get_template_part('template-parts/sidebar-admin'); // Admin: sidebar completo
    } elseif ($es_gerente_ventas) {
        get_template_part('template-parts/sidebar-sales'); // Gerente de Ventas: sidebar de ventas
    } else {
        // Fallback (ej. si un rol inesperado llega aquí)
        get_template_part('template-parts/sidebar-admin'); // Por defecto el de admin si no hay otro más específico
    }
    // --- FIN NUEVO ---
    ?>

    <main class="ghd-main-content">
        
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <h2>Reportes de Producción</h2>
            </div> 
            <div class="header-actions">
                <!-- Aquí podrías añadir un botón de refresco o filtros para reportes -->
            </div>
        </header>

        <div class="ghd-kpi-grid" style="margin-top: 20px;">
            <div class="ghd-card"><h3>Pedidos por Estado Actual</h3><!-- Gráfico 1 --></div>
            <div class="ghd-card"><h3>Carga de Trabajo por Sector</h3><!-- Gráfico 2 --></div>
            <div class="ghd-card"><h3>Pedidos por Prioridad</h3><!-- Gráfico 3 --></div>
        </div>

    </main>
</div>

<?php get_footer(); ?>