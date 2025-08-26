<?php
/**
 * Template Part para el sidebar del panel de administrador.
 * Versión final con logout corregido.
 */
$admin_dashboard_url = home_url('/panel-de-control/');
$login_url = home_url('/iniciar-sesion/'); // Obtenemos la URL de nuestra página de login
?>

<div class="sidebar-header">
    <h1 class="logo">Gestor de Producción</h1>
</div>
<nav class="sidebar-nav">
    <ul>
        <li class="active"><a href="<?php echo esc_url($admin_dashboard_url); ?>"><i class="fa-solid fa-table-columns"></i> <span>Panel de Control</span></a></li>
        <li><a href="#"><i class="fa-solid fa-plus"></i> <span>Nuevo Pedido</span></a></li>
        <li><a href="#"><i class="fa-solid fa-cubes"></i> <span>Sectores</span></a></li>
        <li><a href="#"><i class="fa-solid fa-users"></i> <span>Clientes</span></a></li>
        <li><a href="#"><i class="fa-solid fa-chart-pie"></i> <span>Reportes</span></a></li>
        <li><a href="#"><i class="fa-solid fa-gear"></i> <span>Configuración</span></a></li>
        <li>
            <!-- CORRECCIÓN CLAVE: Redirige a nuestra página de login personalizada -->
            <a href="<?php echo wp_logout_url($login_url); ?>">
                <i class="fa-solid fa-sign-out-alt"></i> 
                <span>Cerrar Sesión</span>
            </a>
        </li>
    </ul>
</nav>