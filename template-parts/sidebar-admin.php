<?php
/**
 * Template Part para el sidebar del panel de administrador.
 * Versión final con clase 'active' dinámica.
 */
$admin_dashboard_url = home_url('/panel-de-control/');
$sectores_url = home_url('/sectores-de-produccion/');
$clientes_url = home_url('/clientes/');
$reportes_url = home_url('/reportes/');
?>

<div class="ghd-sidebar">
<div class="sidebar-header">
    <h1 class="logo">Gestor de Producción</h1>
</div>
<nav class="sidebar-nav">
    <ul>
        <li class="<?php if (is_page('panel-de-control')) echo 'active'; ?>">
            <a href="<?php echo esc_url($admin_dashboard_url); ?>"><i class="fa-solid fa-table-columns"></i> <span>Panel de Control</span></a>
        </li>
        <li><a href="#"><i class="fa-solid fa-plus"></i> <span>Nuevo Pedido</span></a></li>
        <li class="<?php if (is_page('sectores-de-produccion')) echo 'active'; ?>">
            <a href="<?php echo esc_url($sectores_url); ?>"><i class="fa-solid fa-cubes"></i> <span>Sectores de Producción</span></a>
        </li>
        <li class="<?php if (is_page('clientes')) echo 'active'; ?>">
            <a href="<?php echo esc_url($clientes_url); ?>"><i class="fa-solid fa-users"></i> <span>Clientes</span></a>
        </li>
        <li class="<?php if (is_page('reportes')) echo 'active'; ?>">
            <a href="<?php echo esc_url($reportes_url); ?>"><i class="fa-solid fa-chart-pie"></i> <span>Reportes</span></a>
        </li>
        <li><a href="#"><i class="fa-solid fa-gear"></i> <span>Configuración</span></a></li>
        <li><a href="<?php echo wp_logout_url(home_url('/iniciar-sesion/')); ?>"><i class="fa-solid fa-sign-out-alt"></i> <span>Cerrar Sesión</span></a></li>
    </ul>
</nav>
</div>