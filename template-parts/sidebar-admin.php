<?php
/**
 * Template Part para el sidebar del panel de administrador.
 * Versión final y corregida.
 */
$admin_dashboard_url = home_url('/');
$admin_pages = get_posts(array(
    'post_type'  => 'page',
    'fields'     => 'ids',
    'nopaging'   => true,
    'meta_key'   => '_wp_page_template',
    'meta_value' => 'template-admin-dashboard.php'
));
if (!empty($admin_pages)) {
    $admin_dashboard_url = get_permalink($admin_pages[0]);
}
?>
<div class="sidebar-content-wrapper">
<div class="sidebar-header">
    <h1 class="logo">Gestor de Producción</h1>
</div>
<nav class="sidebar-nav">
    <ul>
        <li class="active"><a href="<?php echo esc_url($admin_dashboard_url); ?>"><i class="fa-solid fa-table-columns"></i> <span>Panel de Control</span></a></li>
        <li><a href="#"><i class="fa-solid fa-plus"></i> <span>Nuevo Pedido</span></a></li>
        <li><a href="#"><i class="fa-solid fa-cubes"></i> <span>Sectores de Producción</span></a></li>
        <li><a href="#"><i class="fa-solid fa-users"></i> <span>Clientes</span></a></li>
        <li><a href="#"><i class="fa-solid fa-chart-pie"></i> <span>Reportes</span></a></li>
        <li><a href="#"><i class="fa-solid fa-gear"></i> <span>Configuración</span></a></li>
        
        <!-- LÍNEA CORREGIDA: Añadida la etiqueta <a> de apertura -->
        <li><a href="<?php echo wp_logout_url(home_url()); ?>"><i class="fa-solid fa-sign-out-alt"></i> <span>Cerrar Sesión</span></a></li>
    </ul>
</nav>
</div>