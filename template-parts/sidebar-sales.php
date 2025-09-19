<?php
/**
 * Template Part para el sidebar del panel de Vendedoras y Gerentes de Ventas.
 * Muestra solo enlaces relevantes para ventas/comisiones.
 */

$current_user = wp_get_current_user();

// Obtener URLs de las páginas
$panel_ventas_url = get_permalink(get_page_by_path('panel-de-ventas')->ID ?? 0); // Ajusta este slug

// Determinar si el usuario es Gerente de Ventas o Administrador para mostrar opciones adicionales
$es_gerente_ventas_o_admin = current_user_can('gerente_ventas') || current_user_can('manage_options');

// Asume que el "Panel de Vendedoras" es siempre el panel activo para este sidebar
?>
<aside class="ghd-sidebar">
    <div class="ghd-sidebar-header">
        <h3>Gestor de Ventas</h3>
        <button id="mobile-menu-close" class="ghd-btn-icon"><i class="fa-solid fa-times"></i></button>
    </div>
    <nav class="ghd-sidebar-nav">
        <ul>
            <?php 
            $current_page_id = get_the_ID(); // Obtener el ID de la página actual una vez
            ?>
            <li class="nav-item">
                <a href="<?php echo esc_url($panel_ventas_url); ?>" 
                   class="nav-link <?php echo ($current_page_id === (get_page_by_path('panel-de-ventas')->ID ?? 0)) ? 'is-active' : ''; ?>">
                    <i class="fa-solid fa-money-bill-transfer"></i> Mis Ventas
                </a>
            </li>
            <?php if ($es_gerente_ventas_o_admin) : ?>
            <li class="nav-item">
                <a href="<?php echo esc_url(get_permalink(get_page_by_path('reportes')->ID ?? 0)); ?>" 
                   class="nav-link <?php echo ($current_page_id === (get_page_by_path('reportes')->ID ?? 0)) ? 'is-active' : ''; ?>">
                    <i class="fa-solid fa-chart-line"></i> Reportes (Ventas)
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo esc_url(get_permalink(get_page_by_path('configuracion-comisiones')->ID ?? 0)); ?>" 
                   class="nav-link <?php echo ($current_page_id === (get_page_by_path('configuracion-comisiones')->ID ?? 0)) ? 'is-active' : ''; ?>">
                    <i class="fa-solid fa-gear"></i> Configuración Comisiones
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="ghd-sidebar-footer">
        <a href="<?php echo wp_logout_url(home_url('/iniciar-sesion/')); ?>" class="nav-link logout-link">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar Sesión
        </a>
    </div>
</aside>