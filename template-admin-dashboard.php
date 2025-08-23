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
        <div class="ghd-card ghd-filters">
             <input type="search" placeholder="Buscar Pedidos...">
             <select><option>Todos los Estados</option></select>
             <select><option>Todas las Prioridades</option></select>
             <select><option>Todos los Sectores</option></select>
             <button class="ghd-btn ghd-btn-tertiary">Restablecer</button>
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
                    $args = array(
                        'post_type'      => 'orden_produccion',
                        'posts_per_page' => -1,
                        'orderby'        => 'date',
                        'order'          => 'ASC',
                        'meta_query'     => array(
                            array(
                                'key'     => 'estado_pedido',
                                'value'   => 'Pendiente',
                                'compare' => '=',
                            ),
                        ),
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
                    <tr>
                        <td><input type="checkbox"></td>
                        <td><strong><?php echo esc_html(get_the_title()); ?></strong></td>
                        <td><?php echo esc_html(get_field('nombre_cliente')); ?></td>
                        <td><span class="ghd-tag tag-gray"><?php echo esc_html($estado); ?></span></td>
                        <td><span class="ghd-tag <?php echo $prioridad_class; ?>"><?php echo esc_html($prioridad); ?></span></td>
                        <td><?php echo esc_html($sector_actual); ?></td>
                        <td><?php echo esc_html(get_field('fecha_pedido')); ?></td>
                        <td class="actions-cell">
                            <div class="actions-dropdown">
                                <button class="ghd-btn-icon actions-toggle" data-order-id="<?php echo get_the_ID(); ?>">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <div class="actions-menu">
                                    <div class="actions-menu-group">
                                        <span class="actions-menu-title">Cambiar Prioridad</span>
                                        <a href="#" class="action-link" data-action="change_priority" data-value="Alta">Alta</a>
                                        <a href="#" class="action-link" data-action="change_priority" data-value="Media">Media</a>
                                        <a href="#" class="action-link" data-action="change_priority" data-value="Baja">Baja</a>
                                    </div>
                                    <div class="actions-menu-group">
                                        <span class="actions-menu-title">Asignar Sector</span>
                                        <?php
                                        $sectores = ghd_get_sectores_produccion();
                                        foreach ($sectores as $sector) {
                                            if ($sector === $sector_actual) {
                                                echo '<span class="action-link is-current">' . esc_html($sector) . ' (Actual)</span>';
                                            } else {
                                                echo '<a href="#" class="action-link" data-action="change_sector" data-value="' . esc_attr($sector) . '">' . esc_html($sector) . '</a>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
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