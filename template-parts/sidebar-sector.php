<?php
/**
 * Template Part: Sidebar para los Sectores
 */

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;
$user_role = !empty($user_roles) ? $user_roles[0] : '';
$user_display_name = $current_user->display_name;

// Determinar el nombre del sector para mostrar en el sidebar
$mapa_roles_a_nombres_sector = [
    'rol_carpinteria'    => 'Carpintería',
    'rol_corte'          => 'Corte',
    'rol_costura'        => 'Costura',
    'rol_tapiceria'      => 'Tapicería',
    'rol_embalaje'       => 'Embalaje',
    'rol_logistica'      => 'Logística',
    'rol_administrativo' => 'Administrativo', // Aunque este rol ya no gestiona tareas activas aquí, su nombre puede seguir siendo útil.
];
$sector_name_for_sidebar = $mapa_roles_a_nombres_sector[$user_role] ?? 'Desconocido';

// Obtener la URL de la página de "Mis Tareas" (el propio dashboard de sector)
$sector_dashboard_url = get_posts([
    'post_type'  => 'page',
    'fields'     => 'ids',
    'nopaging'   => true,
    'meta_key'   => '_wp_page_template',
    'meta_value' => 'template-sector-dashboard.php'
]);
$sector_dashboard_url = !empty($sector_dashboard_url) ? get_permalink($sector_dashboard_url[0]) : home_url();

// Si el usuario no es un administrador viendo un sector, se añade el parámetro de sector a la URL
if (!current_user_can('manage_options')) {
    $clean_sector_name_param = strtolower(str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $sector_name_for_sidebar));
    $sector_dashboard_url = add_query_arg('sector', urlencode($clean_sector_name_param), $sector_dashboard_url);
}

// Función para determinar si un enlace está activo (simple para este sidebar)
function is_sector_link_active($template_name) {
    global $post;
    if (is_page_template($template_name)) {
        return 'active';
    }
    return '';
}

?>

<aside class="ghd-sidebar">
    <div class="sidebar-header">
        <h1 class="logo">Mi Puesto</h1>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo is_sector_link_active('template-sector-dashboard.php'); ?>">
                <a href="<?php echo esc_url($sector_dashboard_url); ?>">
                    <i class="fa-solid fa-list-check"></i>
                    <span>Mis Tareas</span>
                </a>
            </li>
            <!-- Enlace al perfil/nombre del usuario, no a una página específica sino a su rol/nombre -->
            <li>
                <a href="#">
                    <i class="fa-solid fa-user"></i>
                    <span><?php echo esc_html($user_display_name); ?></span>
                    <span style="font-size: 0.8em; margin-left: auto; color: #7f8c8d;"><?php echo esc_html($sector_name_for_sidebar); ?></span>
                </a>
            </li>
            <li>
                <a href="<?php echo wp_logout_url(home_url('/iniciar-sesion/')); ?>">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>