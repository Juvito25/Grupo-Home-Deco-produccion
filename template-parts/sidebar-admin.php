<?php
/**
 * Template Part para el sidebar del panel de administrador.
 * Usado por template-admin-dashboard.php y template-ventas.php, entre otros.
 */

// Obtener el usuario actual
$current_user = wp_get_current_user();
$user_roles = (array) $current_user->roles;

// Determinar si el usuario tiene rol de Gerente de Ventas o Admin
$can_view_sales_panel = current_user_can('gerente_ventas') || current_user_can('manage_options');

// Obtener URLs de las páginas
$panel_control_url = get_permalink(get_page_by_path('panel-de-control')->ID ?? 0);
$nuevo_pedido_url = get_permalink(get_page_by_path('nuevo-pedido')->ID ?? 0); // Si tienes una página para nuevo pedido o se abre modal
$sectores_produccion_url = get_permalink(get_page_by_path('sectores-de-produccion')->ID ?? 0); // Ajusta este slug
$clientes_url = get_permalink(get_page_by_path('clientes')->ID ?? 0); // Ajusta este slug
$reportes_url = get_permalink(get_page_by_path('reportes')->ID ?? 0); // Ajusta este slug
$pedidos_archivados_url = get_permalink(get_page_by_path('pedidos-archivados')->ID ?? 0); // Ajusta este slug
$configuracion_url = get_permalink(get_page_by_path('configuracion')->ID ?? 0); // Ajusta este slug
$panel_ventas_url = get_permalink(get_page_by_path('panel-de-ventas')->ID ?? 0); // <-- NUEVO: URL del panel de ventas


$current_page_id = get_the_ID();
?>
<aside class="ghd-sidebar">
    <div class="ghd-sidebar-header">
        <h3>Gestor de Producción</h3>
        <button id="mobile-menu-close" class="ghd-btn-icon"><i class="fa-solid fa-times"></i></button>
    </div>
    <nav class="ghd-sidebar-nav">
        <ul>
            <li class="nav-item">
                <a href="<?php echo esc_url($panel_control_url); ?>" class="nav-link <?php echo (is_page_template('template-admin-dashboard.php')) ? 'is-active' : ''; ?>">
                    <i class="fa-solid fa-gauge-high"></i> Panel de Control
                </a>
            </li>
            <li class="nav-item">
                <a href="#" id="abrir-nuevo-pedido-modal-sidebar" class="nav-link"> <!-- Asume que el botón de nuevo pedido abre un modal -->
                    <i class="fa-solid fa-plus"></i> Nuevo Pedido
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo esc_url($sectores_produccion_url); ?>" class="nav-link <?php echo (is_page_template('template-sectores.php') || is_page_template('template-sector-dashboard.php')) ? 'is-active' : ''; ?>">
                    <i class="fa-solid fa-boxes-stacked"></i> Sectores de Producción
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo esc_url($clientes_url); ?>" class="nav-link <?php echo (is_page_template('template-clientes.php')) ? 'is-active' : ''; ?>">
                    <i class="fa-solid fa-users"></i> Clientes
                </a>
            </li>
            
            <?php if ($can_view_sales_panel) : // Mostrar solo si el usuario puede ver el panel de ventas ?>
            <li class="nav-item">
                <a href="<?php echo esc_url($panel_ventas_url); ?>" class="nav-link <?php echo (is_page_template('template-ventas.php')) ? 'is-active' : ''; ?>">
                    <i class="fa-solid fa-money-bill-transfer"></i> Panel de Vendedoras
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-item">
                <a href="<?php echo esc_url($reportes_url); ?>" class="nav-link <?php echo (is_page_template('template-reportes.php')) ? 'is-active' : ''; ?>">
                    <i class="fa-solid fa-chart-line"></i> Reportes
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo esc_url($pedidos_archivados_url); ?>" class="nav-link <?php echo (is_page_template('template-pedidos-archivados.php')) ? 'is-active' : ''; ?>">
                    <i class="fa-solid fa-box-archive"></i> Pedidos Archivados
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo esc_url($configuracion_url); ?>" class="nav-link <?php echo (is_page_template('template-configuracion.php')) ? 'is-active' : ''; ?>">
                    <i class="fa-solid fa-gear"></i> Configuración
                </a>
            </li>
        </ul>
    </nav>
    <div class="ghd-sidebar-footer">
        <a href="<?php echo wp_logout_url(home_url('/iniciar-sesion/')); ?>" class="nav-link logout-link">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar Sesión
        </a>
    </div>
</aside>
<?php
// Script para abrir el modal de nuevo pedido desde el sidebar
// Asegura que el ID 'abrir-nuevo-pedido-modal' se use para el modal original.
// Y este listener se añada si el sidebar está presente.
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarNuevoPedidoBtn = document.getElementById('abrir-nuevo-pedido-modal-sidebar');
    const nuevoPedidoModal = document.getElementById('nuevo-pedido-modal'); // Asegúrate que este ID sea el del modal

    if (sidebarNuevoPedidoBtn && nuevoPedidoModal) {
        sidebarNuevoPedidoBtn.addEventListener('click', function(e) {
            e.preventDefault();
            nuevoPedidoModal.style.display = 'flex';
        });
    }
});
</script>