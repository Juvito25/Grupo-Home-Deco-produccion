<?php
/**
 * Template Part para la barra lateral del panel de Administrador / Control Final.
 * V2 - Muestra enlaces condicionalmente según el rol del usuario.
 */
?>
<aside class="ghd-sidebar">
    <div class="sidebar-header">
        <h3 class="logo">Gestor de Producción</h3>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="active">
                <a href="<?php echo esc_url(home_url('/panel-de-control/')); ?>">
                    <i class="fa-solid fa-tachometer-alt"></i>
                    <span>Panel de Control</span>
                </a>
            </li>

            <?php // --- ENLACES SOLO PARA ADMINISTRADORES --- ?>
            <?php if (current_user_can('manage_options')) : ?>
                <li>
                    <a href="#"> <!-- Actualiza este enlace a la página de Nuevo Pedido -->
                        <i class="fa-solid fa-plus"></i>
                        <span>Nuevo Pedido</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url(home_url('/sectores-de-produccion/')); ?>">
                        <i class="fa-solid fa-industry"></i>
                        <span>Sectores de Producción</span>
                    </a>
                </li>
                <li>
                    <a href="#"> <!-- Actualiza este enlace a la página de Clientes -->
                        <i class="fa-solid fa-users"></i>
                        <span>Clientes</span>
                    </a>
                </li>
                <li>
                    <a href="#"> <!-- Actualiza este enlace a la página de Reportes -->
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Reportes</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php // --- FIN DE ENLACES SOLO PARA ADMINISTRADORES --- ?>

            <li>
                <a href="#"> <!-- Actualiza este enlace a la página de Pedidos Archivados -->
                    <i class="fa-solid fa-archive"></i>
                    <span>Pedidos Archivados</span>
                </a>
            </li>

            <?php if (current_user_can('manage_options')) : ?>
                <li>
                    <a href="#"> <!-- Actualiza este enlace a la página de Configuración -->
                        <i class="fa-solid fa-cog"></i>
                        <span>Configuración</span>
                    </a>
                </li>
            <?php endif; ?>

            <li style="margin-top: auto; padding-top: 1rem; border-top: 1px solid #555;">
                <a href="<?php echo wp_logout_url(home_url('/iniciar-sesion/')); ?>">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>