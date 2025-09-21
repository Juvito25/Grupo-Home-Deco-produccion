<?php
/**
 * Template Part para el sidebar del panel de Control Final (Macarena).
 * Muestra solo enlaces relevantes para su rol.
 */

$current_user = wp_get_current_user();

// Obtener URLs de las páginas relevantes para Control Final
$panel_control_url = get_permalink(get_page_by_path('panel-de-control')->ID ?? 0); // Su propio panel
$pedidos_archivados_url = get_permalink(get_page_by_path('pedidos-archivados')->ID ?? 0); // Pedidos archivados
// Puedes añadir otras URLs si Macarena tiene otras secciones específicas
?>

<aside class="ghd-sidebar">
    <div class="ghd-sidebar-header">
        <h3>Control Final</h3>
        <button id="mobile-menu-close" class="ghd-btn-icon"><i class="fa-solid fa-times"></i></button>
    </div>
    <nav class="ghd-sidebar-nav">
        <ul>
            <li class="nav-item">
                <a href="<?php echo esc_url($panel_control_url); ?>" 
                   class="nav-link <?php echo (is_page_template('template-admin-dashboard.php') && current_user_can('control_final_macarena') && !current_user_can('manage_options')) ? 'is-active' : ''; ?>">
                    <i class="fa-solid fa-clipboard-check"></i> Pedidos Pendientes
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo esc_url($pedidos_archivados_url); ?>" 
                   class="nav-link <?php echo (is_page_template('template-pedidos-archivados.php')) ? 'is-active' : ''; ?>">
                    <i class="fa-solid fa-box-archive"></i> Pedidos Archivados
                </a>
            </li>
            <!-- Se pueden añadir más enlaces aquí si Macarena tiene otras secciones -->
        </ul>
    </nav>
    <div class="ghd-sidebar-footer">
        <a href="<?php echo wp_logout_url(home_url('/iniciar-sesion/')); ?>" class="nav-link logout-link">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar Sesión
        </a>
    </div>
</aside>