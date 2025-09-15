<?php
/**
 * Template Part para la barra lateral del panel de Administrador / Control Final.
 * V3 - Lógica de enlace activo corregida.
 */

// Obtener el slug de la página actual para determinar el enlace activo.
$current_slug = get_post_field('post_name', get_queried_object_id());
?>
<aside class="ghd-sidebar">
    <div class="sidebar-header">
        <h3 class="logo">Gestor de Producción</h3>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php if ($current_slug === 'panel-de-control' || is_singular('orden_produccion')) echo 'active'; ?>">
                <a href="<?php echo esc_url(home_url('/panel-de-control/')); ?>">
                    <i class="fa-solid fa-tachometer-alt"></i>
                    <span>Panel de Control</span>
                </a>
            </li>

            <?php if (current_user_can('manage_options')) : ?>
                <li>
                    <a href="<?php echo esc_url(home_url('/nuevo-pedido/')); ?>">
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
                    <a href="#">
                        <i class="fa-solid fa-users"></i>
                        <span>Clientes</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Reportes</span>
                    </a>
                </li>
            <?php endif; ?>

            <li class="<?php if ($current_slug === 'pedidos-archivados') echo 'active'; ?>">
                <a href="<?php echo esc_url(home_url('/pedidos-archivados/')); ?>">
                    <i class="fa-solid fa-archive"></i>
                    <span>Pedidos Archivados</span>
                </a>
            </li>

            <?php if (current_user_can('manage_options')) : ?>
                <li>
                    <a href="#">
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