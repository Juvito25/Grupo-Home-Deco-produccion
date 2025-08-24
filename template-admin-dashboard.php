<?php
/* Template Name: GHD - Panel de Administrador */

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
                <button class="ghd-btn ghd-btn-secondary">
                    <i class="fa-solid fa-download"></i> <span>Exportar Datos</span>
                </button>
                <button class="ghd-btn ghd-btn-primary">
                    <i class="fa-solid fa-plus"></i> <span>Nuevo Pedido</span>
                </button>
            </div>
        </header>

        <!-- INICIO DE LA NUEVA SECCIÓN DE FILTROS -->
        <div class="ghd-filters-wrapper">
            <div class="filter-group">
                <label for="ghd-search-filter">Buscar Pedidos</label>
                <input type="search" id="ghd-search-filter" placeholder="Buscar...">
            </div>
            <div class="filter-group">
                <label for="ghd-status-filter">Estado</label>
                <select id="ghd-status-filter">
                    <option value="">Todos los Estados</option>
                    <option value="Pendiente">Pendiente</option>
                    <?php foreach (ghd_get_sectores_produccion() as $sector) { echo '<option value="' . esc_attr($sector) . '">' . esc_html($sector) . '</option>'; } ?>
                    <option value="Completado">Completado</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="ghd-priority-filter">Prioridad</label>
                <select id="ghd-priority-filter">
                    <option value="">Todas las Prioridades</option>
                    <option value="Alta">Alta</option>
                    <option value="Media">Media</option>
                    <option value="Baja">Baja</option>
                </select>
            </div>
            <div class="filter-group">
                <button id="ghd-reset-filters" class="ghd-btn-icon" title="Restablecer filtros">
                    <i class="fa-solid fa-rotate-left"></i>
                </button>
            </div>
        </div>
        <!-- TABLA DE PEDIDOS -->
        <div class="ghd-card ghd-table-wrapper">
            <table class="ghd-table">
                <thead>
                    <tr>
                        <th><input type="checkbox"></th>
                        <th>Código de Pedido</th>
                        <th>Cliente</th>
                        <th>Producto</th>
                        <th>Estado</th>
                        <th>Prioridad</th>
                        <th>Sector Actual</th>
                        <th>Fecha del Pedido</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="ghd-orders-table-body">
                    <?php
                    // CONSULTA: Por defecto, muestra TODOS los pedidos.
                    // En template-admin-dashboard.php
                    $args = array(
                        'post_type'      => 'orden_produccion',
                        'posts_per_page' => -1,
                        'orderby'        => 'date',
                        'order'          => 'DESC', // Los más nuevos primero
                    );
                    $pedidos_query = new WP_Query($args);
                    /* OPCIONAL: Para volver a la vista de "Bandeja de Entrada" (solo pendientes), descomenta este bloque
                    $args['meta_query'] = array(
                        array(
                            'key'     => 'estado_pedido',
                            'value'   => 'Pendiente',
                            'compare' => '=',
                        ),
                    );
                    */

                    $pedidos_query = new WP_Query($args);

                    if ($pedidos_query->have_posts()) :
                        while ($pedidos_query->have_posts()) : $pedidos_query->the_post();
                            
                            // 1. Recopilamos y preparamos todos los datos en el archivo principal
                            $estado = get_field('estado_pedido');
                            $prioridad = get_field('prioridad_pedido');
                            
                            $prioridad_class = 'tag-green';
                            if ($prioridad == 'Alta') $prioridad_class = 'tag-red';
                            elseif ($prioridad == 'Media') $prioridad_class = 'tag-yellow';

                            $estado_class = 'tag-gray';
                            if (in_array($estado, ghd_get_sectores_produccion())) {
                                $estado_class = 'tag-blue';
                            } elseif ($estado == 'Completado') {
                                $estado_class = 'tag-green';
                            }
                            
                            // 2. Creamos el array de datos para pasar al archivo de la fila
                            $args_fila = array(
                                'post_id'         => get_the_ID(),
                                'titulo'          => get_the_title(),
                                'nombre_cliente'  => get_field('nombre_cliente'),
                                'nombre_producto' => get_field('nombre_producto'), // Asegúrate de que este campo existe en ACF
                                'estado'          => $estado,
                                'prioridad'       => $prioridad,
                                'sector_actual'   => get_field('sector_actual'),
                                'fecha_del_pedido'    => get_field('fecha_del_pedido'),
                                'prioridad_class' => $prioridad_class,
                                'estado_class'    => $estado_class,
                            );

                            // 3. Llamamos al template part y le pasamos el array de datos
                            get_template_part('template-parts/order-row-admin', null, $args_fila);

                        endwhile;
                    else: 
                    ?>
                    <tr>
                        <td colspan="8" style="text-align:center;">No se encontraron órdenes de producción.</td>
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