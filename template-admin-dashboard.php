<?php
/*
 * Template Name: GHD - Panel de Administrador
 */

// --- CONTROL DE ACCESO ---
// Si el usuario no ha iniciado sesión, lo mandamos a la página de login.
if (!is_user_logged_in()) {
    auth_redirect();
}
// Si el usuario ha iniciado sesión PERO NO es un administrador, lo mandamos a la página de inicio.
if (!current_user_can('manage_options')) {
    wp_redirect(home_url());
    exit;
}

get_header(); // Carga el header de WordPress
?>

<div class="ghd-app-wrapper">
    
    <!-- BARRA LATERAL (ADMINISTRADOR) -->
    <aside class="ghd-sidebar">
        <div class="sidebar-header">
            <h1 class="logo">Gestor de Producción</h1>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li class="active"><a href="#"><i class="fa-solid fa-table-columns"></i> <span>Panel de Control</span></a></li>
                <li><a href="#"><i class="fa-solid fa-plus"></i> <span>Nuevo Pedido</span></a></li>
                <li><a href="#"><i class="fa-solid fa-cubes"></i> <span>Sectores de Producción</span></a></li>
                <li><a href="#"><i class="fa-solid fa-users"></i> <span>Clientes</span></a></li>
                <li><a href="#"><i class="fa-solid fa-chart-pie"></i> <span>Reportes</span></a></li>
                <li><a href="#"><i class="fa-solid fa-gear"></i> <span>Configuración</span></a></li>
            </ul>
        </nav>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="ghd-main-content">
        
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h2>Panel de Administrador</h2>
            </div> 
            <div class="header-actions">
                <button class="ghd-btn ghd-btn-secondary">Exportar Datos</button>
                <button class="ghd-btn ghd-btn-primary">Nuevo Pedido</button>
            </div>
        </header>

        <!-- FILTROS -->
        <!-- SECCIÓN DE FILTROS - CON IDs -->
        <div class="ghd-card ghd-filters">
            <input type="search" id="ghd-search-filter" placeholder="Buscar por Código o Cliente...">
            
            <select id="ghd-status-filter">
                <option value="">Todos los Estados</option>
                <option value="Pendiente">Pendiente</option>
                <?php
                // Rellenamos dinámicamente los estados/sectores
                $sectores_para_filtro = ghd_get_sectores_produccion();
                foreach ($sectores_para_filtro as $sector) {
                    echo '<option value="' . esc_attr($sector) . '">' . esc_html($sector) . '</option>';
                }
                ?>
                <option value="Completado">Completado</option>
            </select>
            
            <select id="ghd-priority-filter">
                <option value="">Todas las Prioridades</option>
                <option value="Alta">Alta</option>
                <option value="Media">Media</option>
                <option value="Baja">Baja</option>
            </select>
            
            <button id="ghd-reset-filters" class="ghd-btn ghd-btn-tertiary">Restablecer</button>
        </div>
        <!-- TABLA DE PEDIDOS -->
        <div class="ghd-card ghd-table-wrapper">
            <table class="ghd-table">
                <thead>
                    <tr>
                        <th><input type="checkbox"></th>
                        <th>Código de Pedido</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>Prioridad</th>
                        <th>Próximo Sector</th>
                        <th>Fecha del Pedido</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // CONSULTA MODIFICADA: Mostrar TODOS los pedidos
                    $args = array(
                        'post_type'      => 'orden_produccion',
                        'posts_per_page' => -1, // -1 para traer todos
                        'orderby'        => 'date',
                        'order'          => 'DESC', // Los más nuevos primero
                    );
                    $pedidos_query = new WP_Query($args);

                    if ($pedidos_query->have_posts()) :
                        while ($pedidos_query->have_posts()) : $pedidos_query->the_post();
                            $estado = get_field('estado_pedido');
                            $prioridad = get_field('prioridad_pedido');
                            $sector_actual = get_field('sector_actual');

                            $prioridad_class = 'tag-green';
                            if ($prioridad == 'Alta') $prioridad_class = 'tag-red';
                            elseif ($prioridad == 'Media') $prioridad_class = 'tag-yellow';
                    ?>
                    <?php get_template_part('template-parts/order-row-admin'); ?>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                    <tr>
                        <td colspan="8" style="text-align:center;">No hay órdenes pendientes de asignación.</td>
                    </tr>
                    <?php
                    endif;
                    wp_reset_postdata(); 
                    ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<?php get_footer(); ?>