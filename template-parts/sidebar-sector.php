<?php
/**
 * Template Part para el sidebar del panel de sector.
 * Versión final con clase 'active' dinámica.
 */
$current_user = wp_get_current_user();
$sector_dashboard_url = home_url('/mis-tareas/');
$login_url = home_url('/iniciar-sesion/');
?>

<div class="ghd-sidebar">
<div class="sidebar-header">
    <h1 class="logo">Mi Puesto</h1>
</div>
<nav class="sidebar-nav">
    <ul>
        <li class="<?php if (is_page('mis-tareas') || is_singular('orden_produccion')) echo 'active'; ?>">
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
            <a href="<?php echo wp_logout_url($login_url); ?>">
                <i class="fa-solid fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
        </li>
    </ul>
</nav>
</div>