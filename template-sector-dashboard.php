<?php
/* Template Name: GHD - Panel de Sector */

// --- CONTROL DE ACCESO ---
// 1. Si el usuario no ha iniciado sesión, lo mandamos a la página de login.
if (!is_user_logged_in()) {
    auth_redirect();
}
// 2. Si el usuario es un Administrador, no debería estar aquí. Lo mandamos a su propio panel.
if (current_user_can('manage_options')) {
    // Buscamos dinámicamente la URL del panel de admin
    $admin_dashboard_url = home_url('/'); // Fallback
    $admin_pages = get_posts(array(
        'post_type'  => 'page', 'fields' => 'ids', 'nopaging' => true,
        'meta_key' => '_wp_page_template', 'meta_value' => 'template-admin-dashboard.php'
    ));
    if (!empty($admin_pages)) {
        $admin_dashboard_url = get_permalink($admin_pages[0]);
    }
    wp_redirect($admin_dashboard_url);
    exit;
}

get_header(); // Carga el header de WordPress

// Obtenemos los datos del usuario actual para personalizar la vista
$current_user = wp_get_current_user();
$user_roles = $current_user->roles;
$user_role = !empty($user_roles) ? $user_roles[0] : '';

// Mapeamos el ID del rol (ej: 'rol_carpinteria') a un nombre legible (ej: 'Carpintería')
$sector_name = ucfirst(str_replace('rol_', '', $user_role));
?>

<div class="ghd-app-wrapper">
    
    <!-- BARRA LATERAL (TRABAJADOR) -->
    <aside class="ghd-sidebar">
        <div class="sidebar-header">
            <h1 class="logo">Mi Puesto</h1>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li class="active"><a href="#"><i class="fa-solid fa-inbox"></i> <span>Mis Tareas</span></a></li>
                <li><a href="#"><i class="fa-solid fa-user-circle"></i> <span><?php echo esc_html($current_user->display_name); ?></span></a></li>
                <li><a href="<?php echo wp_logout_url(home_url()); // Redirige al home al salir ?>"><i class="fa-solid fa-sign-out-alt"></i> <span>Cerrar Sesión</span></a></li>
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
                <h2>Tareas de <?php echo esc_html($sector_name); ?></h2>
            </div>
        </header>

        <div class="ghd-sector-tasks-grid">
            <?php
            // Solo ejecutamos la consulta si el usuario tiene un rol de sector válido
            if (!empty($sector_name) && in_array($sector_name, ghd_get_sectores_produccion())) {
                
                $args = array(
                    'post_type'      => 'orden_produccion',
                    'posts_per_page' => -1,
                    'orderby'        => 'meta_value',
                    'meta_key'       => 'prioridad_pedido',
                    'order'          => 'DESC', // Muestra 'Alta' primero
                    'meta_query'     => array(
                        array(
                            'key'     => 'sector_actual',
                            'value'   => $sector_name,
                            'compare' => '=',
                        ),
                    ),
                );
                $sector_query = new WP_Query($args);

                if ($sector_query->have_posts()) :
                    while ($sector_query->have_posts()) : $sector_query->the_post();
                        $prioridad = get_field('prioridad_pedido');
                        $prioridad_class = 'tag-green';
                        if ($prioridad == 'Alta') $prioridad_class = 'tag-red';
                        if ($prioridad == 'Media') $prioridad_class = 'tag-yellow';
            ?>
            <div class="ghd-task-card" id="order-<?php echo get_the_ID(); ?>">
                <div class="card-header">
                    <h3><?php the_title(); ?></h3>
                    <span class="ghd-tag <?php echo $prioridad_class; ?>"><?php echo esc_html($prioridad); ?></span>
                </div>
                <div class="card-body">
                    <div class="card-info-group">
                        <span class="info-label">Cliente:</span>
                        <span class="info-value"><?php echo esc_html(get_field('nombre_cliente')); ?></span>
                    </div>
                    <div class="card-info-group">
                        <span class="info-label">Producto:</span>
                        <span class="info-value"><?php echo esc_html(get_field('nombre_producto')); // NOTA: Necesitas crear este campo 'nombre_producto' en ACF. ?></span>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary">Ver Detalles</a>
                    <button 
                        class="ghd-btn ghd-btn-primary move-to-next-sector-btn" 
                        data-order-id="<?php echo get_the_ID(); ?>"
                        data-nonce="<?php echo wp_create_nonce('ghd_move_order_nonce'); // Nonce específico para esta acción ?>">
                        Mover a Siguiente Sector
                    </button>
                </div>
            </div>
            <?php 
                    endwhile;
                else:
            ?>
                <p class="no-tasks-message">No tienes tareas asignadas en este momento. ¡Buen trabajo!</p>
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