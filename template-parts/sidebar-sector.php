<?php
$current_user = wp_get_current_user();

// Obtenemos dinámicamente la URL de la página del panel de sector
$sector_dashboard_url = home_url('/'); // Fallback
$sector_pages = get_posts(array(
    'post_type' => 'page', 'fields' => 'ids', 'nopaging' => true,
    'meta_key' => '_wp_page_template', 'meta_value' => 'template-sector-dashboard.php'
));
if (!empty($sector_pages)) {
    $sector_dashboard_url = get_permalink($sector_pages[0]);
}
?>

<div class="sidebar-header">
    <h1 class="logo">Mi Puesto</h1>
</div>
<nav class="sidebar-nav">
    <ul>
        <li class="active"><a href="<?php echo esc_url($sector_dashboard_url); ?>"><i class="fa-solid fa-inbox"></i> <span>Mis Tareas</span></a></li>
        <li><a href="#"><i class="fa-solid fa-user-circle"></i> <span><?php echo esc_html($current_user->display_name); ?></span></a></li>
        <li><a href="<?php echo wp_logout_url(home_url()); ?>"><i class="fa-solid fa-sign-out-alt"></i> <span>Cerrar Sesión</span></a></li>
    </ul>
</nav>