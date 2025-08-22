<?php
/*
 * Template Name: GHD - Panel de Sector
 */

get_header(); 

// Primero, nos aseguramos de que el usuario haya iniciado sesión.
if (!is_user_logged_in()) {
    // Si no ha iniciado sesión, lo redirigimos a la página de login.
    wp_redirect(wp_login_url());
    exit;
}

// Obtenemos los datos del usuario actual
$current_user = wp_get_current_user();
$user_roles = $current_user->roles;
$user_role = !empty($user_roles) ? $user_roles[0] : '';

// Convertimos el ID del rol (ej: 'rol_carpinteria') al nombre del sector (ej: 'Carpintería')
$sector_name = '';
if (strpos($user_role, 'rol_') === 0) {
    $sector_name = ucfirst(str_replace('rol_', '', $user_role));
}

?>

<div class="ghd-app-wrapper">
    <!-- El sidebar será diferente para los trabajadores -->
    <aside class="ghd-sidebar">
        <div class="sidebar-header">
            <h1 class="logo">Mi Puesto</h1>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li class="active"><a href="#"><i class="fa-solid fa-inbox"></i> <span>Mis Tareas</span></a></li>
                <li><a href="#"><i class="fa-solid fa-user-circle"></i> <span><?php echo esc_html($current_user->display_name); ?></span></a></li>
                <li><a href="<?php echo wp_logout_url(); ?>"><i class="fa-solid fa-sign-out-alt"></i> <span>Cerrar Sesión</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="ghd-main-content">
        <header class="ghd-main-header">
            <h2>Tareas de <?php echo esc_html($sector_name); ?></h2>
            <!-- Aquí podríamos añadir un botón de "Refrescar" o filtros si fuera necesario -->
        </header>

        <div class="ghd-sector-tasks-grid">
            <?php
            // Solo si hemos identificado un sector válido para el usuario...
            if (!empty($sector_name)) {
                // Preparamos la consulta para obtener solo los pedidos de este sector
                $args = array(
                    'post_type'      => 'orden_produccion',
                    'posts_per_page' => -1,
                    'orderby'        => 'meta_value', // Ordenaremos por prioridad
                    'meta_key'       => 'prioridad_pedido',
                    'order'          => 'DESC', // 'Alta' suele estar al final, así que DESC lo pone primero
                    'meta_query'     => array(
                        array(
                            'key'     => 'sector_actual',
                            'value'   => $sector_name, // ¡LA MAGIA OCURRE AQUÍ!
                            'compare' => '=',
                        ),
                    ),
                );

                $sector_query = new WP_Query($args);

                if ($sector_query->have_posts()) :
                    while ($sector_query->have_posts()) : $sector_query->the_post();
                        $prioridad = get_field('prioridad_pedido');
                        $prioridad_class = 'tag-green'; // Baja por defecto
                        if ($prioridad == 'Alta') $prioridad_class = 'tag-red';
                        if ($prioridad == 'Media') $prioridad_class = 'tag-yellow';
            ?>
            
            <!-- MAQUETACIÓN DE LA TARJETA DE PEDIDO (AÚN NO TIENE ESTILOS) -->
            <div class="ghd-task-card">
                <div class="card-header">
                    <h3><?php the_title(); ?></h3>
                    <span class="ghd-tag <?php echo $prioridad_class; ?>"><?php echo esc_html($prioridad); ?></span>
                </div>
                <div class="card-body">
                    <p><strong>Cliente:</strong> <?php echo esc_html(get_field('nombre_cliente')); ?></p>
                    <p><strong>Producto:</strong> [Aquí irá el nombre del producto]</p>
                </div>
                <div class="card-footer">
                    <button class="ghd-btn ghd-btn-secondary">Ver Detalles</button>
                    <button class="ghd-btn ghd-btn-primary">Mover a Siguiente Sector</button>
                </div>
            </div>

            <?php 
                    endwhile;
                else:
            ?>
                <p>No tienes tareas asignadas en este momento.</p>
            <?php
                endif;
                wp_reset_postdata();
            } else {
            ?>
                <p>No tienes un rol de producción asignado. Contacta al administrador.</p>
            <?php } ?>
        </div>
    </main>
</div>

<?php get_footer(); ?>