<?php
/**
 * Template Name: GHD - Panel de Reportes
 * Versión 2.0 con layout de columnas.
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
                <h2>Reportes de Producción</h2>
            </div> 
        </header>

        <!-- NUEVA ESTRUCTURA DE GRID PARA LOS REPORTES -->
        <div class="ghd-reports-grid">
            
            <!-- Columna Principal (Gráfico de Barras) -->
            <div class="report-main">
                <div class="ghd-card">
                    <h3 class="card-section-title">Pedidos por Estado Actual</h3>
                    <div class="chart-container">
                        <canvas id="pedidosPorEstadoChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Columna Lateral (Gráfico Circular y futuras tarjetas) -->
            <!-- <div class="report-sidebar">
                <div class="ghd-card">
                    <h3 class="card-section-title">Carga de Trabajo por Sector</h3>
                    <div class="chart-container">
                        <canvas id="cargaPorSectorChart"></canvas>
                    </div>
                </div>
            </div> -->
            <div class="report-sidebar">
                <div class="ghd-card">
                    <h3 class="card-section-title">Carga de Trabajo por Sector</h3>
                    <div class="chart-container">
                        <canvas id="cargaPorSectorChart"></canvas>
                    </div>
                </div>

                <!-- AÑADE ESTA NUEVA TARJETA COMPLETA -->
                <div class="ghd-card">
                    <h3 class="card-section-title">Pedidos por Prioridad</h3>
                    <div class="chart-container">
                        <canvas id="pedidosPorPrioridadChart"></canvas>
                    </div>
                </div>
        </div>

        </div>

    </main>
</div>
<?php get_footer(); ?>