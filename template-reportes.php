<?php
/**
 * Template Name: GHD - Reportes
 */
if ( ! is_user_logged_in() || ( ! current_user_can('gerente_ventas') && ! current_user_can('manage_options') ) ) {
    auth_redirect();
}
get_header(); 
?>

<div class="ghd-app-wrapper">
    
    <?php get_template_part('template-parts/sidebar-sales'); ?>

    <main class="ghd-main-content">
        
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <h2>Reportes de Producción</h2>
            </div> 
            <div class="header-actions">
                <!-- Aquí podrías añadir filtros por fecha, sector, etc. si fueran necesarios a futuro -->
            </div>
        </header>

        <div class="ghd-reports-grid">
            <!-- GRÁFICO 1: PEDIDOS POR ESTADO ACTUAL -->
            <div class="ghd-card report-card">
                <h3 class="card-section-title">Pedidos por Estado Actual</h3>
                <div class="chart-container">
                    <canvas id="pedidosPorEstadoChart"></canvas>
                </div>
            </div>

            <!-- GRÁFICO 2: CARGA DE TRABAJO POR SECTOR -->
            <div class="ghd-card report-card">
                <h3 class="card-section-title">Carga de Trabajo por Sector</h3>
                <div class="chart-container">
                    <canvas id="cargaPorSectorChart"></canvas>
                </div>
            </div>

            <!-- GRÁFICO 3: PEDIDOS POR PRIORIDAD -->
            <div class="ghd-card report-card">
                <h3 class="card-section-title">Pedidos por Prioridad</h3>
                <div class="chart-container">
                    <canvas id="pedidosPorPrioridadChart"></canvas>
                </div>
            </div>
        </div>
    </main>
</div>
<?php get_footer(); ?>