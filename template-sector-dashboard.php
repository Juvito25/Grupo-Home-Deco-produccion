<?php
/* Template Name: GHD - Panel de Sector */

// El control de acceso y redirección ahora está en functions.php

get_header(); // Ahora sí podemos llamar al header de forma segura

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;
$user_role = !empty($user_roles) ? $user_roles[0] : '';

$role_to_sector_map = array(
    'rol_carpinteria' => 'Carpintería',
    'rol_costura'     => 'Costura',
    'rol_tapiceria'   => 'Tapicería',
    'rol_logistica'   => 'Logística',
);
$sector_name = isset($role_to_sector_map[$user_role]) ? $role_to_sector_map[$user_role] : '';
?>

<div class="ghd-app-wrapper">
    <!-- BARRA LATERAL (TRABAJADOR) - CORREGIDA -->
    <aside class="ghd-sidebar">
        <div class="sidebar-header">
            <h1 class="logo">Mi Puesto</h1>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li class="active"><a href="#"><i class="fa-solid fa-inbox"></i> <span>Mis Tareas</span></a></li>
                <li><a href="#"><i class="fa-solid fa-user-circle"></i> <span><?php echo esc_html($current_user->display_name); ?></span></a></li>
                <li><a href="<?php echo wp_logout_url(home_url()); ?>"><i class="fa-solid fa-sign-out-alt"></i> <span>Cerrar Sesión</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="ghd-main-content">
        <header class="ghd-main-header">
            <h2>Tareas de <?php echo esc_html($sector_name); ?></h2>
        </header>

        <div class="ghd-sector-tasks-grid">
            <?php
            if (!empty($sector_name)) {
                $args = array(
                    'post_type'      => 'orden_produccion',
                    'posts_per_page' => -1,
                    'orderby'        => 'meta_value',
                    'meta_key'       => 'prioridad_pedido',
                    'order'          => 'DESC',
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
            <!-- REEMPLAZA LA TARJETA DE TAREA EXISTENTE CON ESTA VERSIÓN FINAL -->
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
                        <span class="info-value"><?php echo esc_html(get_field('nombre_producto')); ?></span>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary">Ver Detalles</a>
                    <button 
                        class="ghd-btn ghd-btn-primary move-to-next-sector-btn" 
                        data-order-id="<?php echo get_the_ID(); ?>"
                        data-nonce="<?php echo wp_create_nonce('ghd_move_order_nonce'); ?>">
                        Mover a Siguiente Sector
                    </button>
                </div>
            </div>
            <?php 
                    endwhile;
                else:
            ?>
                <p class="no-tasks-message">No tienes tareas asignadas en este momento.</p>
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