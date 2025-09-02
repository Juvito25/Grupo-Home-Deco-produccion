<?php
/**
 * Template Part: Sidebar para el Administrador
 */

// Obtener la URL base del panel de control
$admin_dashboard_url = get_posts([
    'post_type'  => 'page',
    'fields'     => 'ids',
    'nopaging'   => true,
    'meta_key'   => '_wp_page_template',
    'meta_value' => 'template-admin-dashboard.php'
]);
$admin_dashboard_url = !empty($admin_dashboard_url) ? get_permalink($admin_dashboard_url[0]) : home_url();

// Obtener la URL de la página de "Sectores de Producción"
$sectores_page_url = get_posts([
    'post_type'  => 'page',
    'fields'     => 'ids',
    'nopaging'   => true,
    'meta_key'   => '_wp_page_template',
    'meta_value' => 'template-sectores.php'
]);
$sectores_page_url = !empty($sectores_page_url) ? get_permalink($sectores_page_url[0]) : home_url();

// Obtener la URL de la página de "Clientes"
$clientes_page_url = get_posts([
    'post_type'  => 'page',
    'fields'     => 'ids',
    'nopaging'   => true,
    'meta_key'   => '_wp_page_template',
    'meta_value' => 'template-clientes.php'
]);
$clientes_page_url = !empty($clientes_page_url) ? get_permalink($clientes_page_url[0]) : home_url();

// Obtener la URL de la página de "Reportes"
$reportes_page_url = get_posts([
    'post_type'  => 'page',
    'fields'     => 'ids',
    'nopaging'   => true,
    'meta_key'   => '_wp_page_template',
    'meta_value' => 'template-reportes.php'
]);
$reportes_page_url = !empty($reportes_page_url) ? get_permalink($reportes_page_url[0]) : home_url();


// Función para determinar si un enlace está activo
function is_sidebar_link_active($template_name) {
    global $post;
    if (is_page_template($template_name)) {
        return 'active';
    }
    return '';
}

?>

<aside class="ghd-sidebar">
    <div class="sidebar-header">
        <h1 class="logo">Gestor de Producción</h1>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo is_sidebar_link_active('template-admin-dashboard.php'); ?>">
                <a href="<?php echo esc_url($admin_dashboard_url); ?>">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Panel de Control</span>
                </a>
            </li>
            <!-- "Administrativo" se elimina ya que su función se consolida en "Panel de Control" -->
            <li>
                <a href="<?php echo esc_url($admin_dashboard_url); ?>?action=new-order"> <!-- Puedes ajustar esta URL para tu página de Nuevo Pedido -->
                    <i class="fa-solid fa-plus"></i>
                    <span>Nuevo Pedido</span>
                </a>
            </li>
            <li class="<?php echo is_sidebar_link_active('template-sectores.php'); ?>">
                <a href="<?php echo esc_url($sectores_page_url); ?>">
                    <i class="fa-solid fa-people-carry-box"></i>
                    <span>Sectores de Producción</span>
                </a>
            </li>
            <li class="<?php echo is_sidebar_link_active('template-clientes.php'); ?>">
                <a href="<?php echo esc_url($clientes_page_url); ?>">
                    <i class="fa-solid fa-users"></i>
                    <span>Clientes</span>
                </a>
            </li>
            <li class="<?php echo is_sidebar_link_active('template-reportes.php'); ?>">
                <a href="<?php echo esc_url($reportes_page_url); ?>">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Reportes</span>
                </a>
            </li>
             <?php
            // URL para la página de Pedidos Archivados
            $archived_orders_page_url = get_posts([
                'post_type'  => 'page',
                'fields'     => 'ids',
                'nopaging'   => true,
                'meta_key'   => '_wp_page_template',
                'meta_value' => 'template-pedidos-archivados.php'
            ]);
            $archived_orders_url = !empty($archived_orders_page_url) ? get_permalink($archived_orders_page_url[0]) : home_url();
            ?>
            <li class="<?php echo is_sidebar_link_active('template-pedidos-archivados.php'); ?>">
                <a href="<?php echo esc_url($archived_orders_url); ?>">
                    <i class="fa-solid fa-box-archive"></i> <!-- Icono de archivo -->
                    <span>Pedidos Archivados</span>
                </a>
            </li>
                        <?php
            // URL para la página de Configuración
            $config_page_url = get_posts([
                'post_type'  => 'page',
                'fields'     => 'ids',
                'nopaging'   => true,
                'meta_key'   => '_wp_page_template',
                'meta_value' => 'template-configuracion.php'
            ]);
            $config_url = !empty($config_page_url) ? get_permalink($config_page_url[0]) : '#';
            ?>
            <li class="<?php echo is_sidebar_link_active('template-configuracion.php'); ?>">
                <a href="<?php echo esc_url($config_url); ?>">
                    <i class="fa-solid fa-gear"></i>
                    <span>Configuración</span>
                </a>
            </li>
                        <?php
            // Obtener la URL de tu página de login personalizada
            $login_page_query = get_posts([
                'post_type'  => 'page',
                'fields'     => 'ids',
                'nopaging'   => true,
                'meta_key'   => '_wp_page_template',
                'meta_value' => 'template-login.php' // Asumo que tu plantilla de login se llama template-login.php
            ]);
            $custom_login_url = !empty($login_page_query) ? get_permalink($login_page_query[0]) : wp_login_url(); // Fallback

            // Generar la URL de logout con el redirect correcto
            $logout_url = wp_logout_url( $custom_login_url );
            ?>
            <li>
                <a href="<?php echo esc_url($logout_url); ?>">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>