<?php
/**
 * Template Name: GHD - Perfil de Usuario de Sector
 * Descripción: Muestra la información del perfil del usuario logueado.
 */

if (!is_user_logged_in()) {
    auth_redirect();
}

get_header();

$current_user = wp_get_current_user();
$user_display_name = $current_user->display_name;
$user_login = $current_user->user_login;
$user_email = $current_user->user_email;
$user_roles = $current_user->roles;
$user_role = !empty($user_roles) ? $user_roles[0] : 'No asignado';

// Mapeo de roles a nombres de sector legibles
$mapa_roles_a_nombres_sector = [
    'administrator'      => 'Administrador Principal',
    'rol_carpinteria'    => 'Carpintería',
    'rol_corte'          => 'Corte',
    'rol_costura'        => 'Costura',
    'rol_tapiceria'      => 'Tapicería',
    'rol_embalaje'       => 'Embalaje',
    'rol_logistica'      => 'Logística',
    'rol_administrativo' => 'Administrativo (Cierre de Pedidos)',
];
$display_role = $mapa_roles_a_nombres_sector[$user_role] ?? 'Rol Desconocido';

?>

<div class="ghd-app-wrapper">
    <?php 
    // Mostrar sidebar del admin si es admin, o el de sector si es de sector
    if (current_user_can('manage_options')) {
        get_template_part('template-parts/sidebar-admin');
    } else {
        get_template_part('template-parts/sidebar-sector');
    }
    ?>

    <main class="ghd-main-content">
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <h2>Perfil de Usuario: <?php echo esc_html($user_display_name); ?></h2>
                
                <?php
                // Botón "Volver" inteligente para el perfil
                $back_url = home_url(); // Fallback
                if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
                    $back_url = esc_url($_SERVER['HTTP_REFERER']);
                } else {
                    // Si no hay referer, ir al dashboard principal del usuario
                    if (current_user_can('manage_options')) {
                        $admin_dashboard_page = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-admin-dashboard.php']);
                        $back_url = !empty($admin_dashboard_page) ? get_permalink($admin_dashboard_page[0]) : home_url();
                    } else {
                        $sector_dashboard_page = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-sector-dashboard.php']);
                        $back_url = !empty($sector_dashboard_page) ? get_permalink($sector_dashboard_page[0]) : home_url();
                    }
                }
                ?>
                <a href="<?php echo esc_url($back_url); ?>" class="ghd-btn ghd-btn-secondary"><i class="fa-solid fa-arrow-left"></i> Volver</a>
            </div>
            <div class="header-actions">
                <!-- Aquí podrías añadir un botón para "Editar Perfil" si esa funcionalidad existiera -->
            </div>
        </header>

        <div class="ghd-main-content-body">
            <div class="ghd-card ghd-user-profile-card">
                <h3 class="card-section-title">Información del Perfil</h3>
                <div class="profile-info-grid">
                    <div class="profile-detail">
                        <strong>Nombre Completo:</strong>
                        <span><?php echo esc_html($user_display_name); ?></span>
                    </div>
                    <div class="profile-detail">
                        <strong>Nombre de Usuario:</strong>
                        <span><?php echo esc_html($user_login); ?></span>
                    </div>
                    <div class="profile-detail">
                        <strong>Email:</strong>
                        <span><?php echo esc_html($user_email); ?></span>
                    </div>
                    <div class="profile-detail">
                        <strong>Rol Asignado:</strong>
                        <span><?php echo esc_html($display_role); ?></span>
                    </div>
                    <!-- Puedes añadir más campos de perfil si los gestionas (ej. a través de ACF para usuarios) -->
                    <!-- <div class="profile-detail">
                        <strong>Teléfono:</strong>
                        <span><?php // echo esc_html(get_user_meta($current_user->ID, 'user_phone', true)); ?></span>
                    </div> -->
                </div>
            </div>
        </div>
    </main>
</div>

<?php get_footer(); ?>