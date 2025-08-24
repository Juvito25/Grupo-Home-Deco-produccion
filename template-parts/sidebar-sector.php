<?php
/**
 * Template Part para el sidebar del panel de sector.
 * Versión final con wrapper interno.
 */
$current_user = wp_get_current_user();
$sector_dashboard_url = home_url('/');
$sector_pages = get_posts(array(
    'post_type'  => 'page', 'fields' => 'ids', 'nopaging' => true,
    'meta_key'   => '_wp_page_template', 'meta_value' => 'template-sector-dashboard.php'
));
if (!empty($sector_pages)) { $sector_dashboard_url = get_permalink($sector_pages[0]); }
?>

<!-- Contenedor interno para controlar el layout con Flexbox -->
<div class="sidebar-content-wrapper">
    <div class="sidebar-header">
        <h1 class="logo">Mi Puesto</h1>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="active">
                <a href="<?php echo esc_url($sector_dashboard_url); ?>">
                    <i class="fa-solid fa-inbox"></i>
                    <span>Mis Tareas</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fa-solid fa-user-circle"></i>
                    <span><?php echo esc_html($current_user->display_name); ?></span>
                </a>
            </li>
            <li>
                <a href="<?php echo wp_logout_url(home_url()); ?>">
                    <i class="fa-solid fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </li>
        </ul>
    </nav>
</div>