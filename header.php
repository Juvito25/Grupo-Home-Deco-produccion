<?php
/**
 * El header para nuestro tema.
 * Versión V2.1 con lógica condicional para mostrarse solo en la aplicación.
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
// --- LÓGICA CONDICIONAL ---
// Comprobamos si la página actual es una de nuestras plantillas de la aplicación.
if ( is_page_template('template-admin-dashboard.php') || is_page_template('template-sector-dashboard.php') || is_singular('orden_produccion') ) :
?>

<!-- INICIO DE LA CABECERA PROFESIONAL (SOLO PARA LA APP) -->
<header class="ghd-pro-header">
    <div class="header-logo-title">
        <img src="<?php echo get_stylesheet_directory_uri(); ?>/img/logo.png" alt="Logo Grupo Home Deco" class="header-logo">
        <div class="header-title-group">
            <span class="main-title">Gestor de Flujo de Producción</span>
            <?php 
            $sub_title = 'Panel de Control';
            if (is_page_template('template-sector-dashboard.php')) {
                $current_user = wp_get_current_user();
                $user_roles = $current_user->roles;
                $user_role = !empty($user_roles) ? $user_roles[0] : '';
                $role_to_sector_map = array('rol_carpinteria' => 'Carpintería', 'rol_costura' => 'Costura', 'rol_tapiceria' => 'Tapicería', 'rol_logistica' => 'Logística');
                $sector_name = isset($role_to_sector_map[$user_role]) ? $role_to_sector_map[$user_role] : '';
                $sub_title = $sector_name . ' Dashboard';
            } elseif (is_singular('orden_produccion')) {
                $sub_title = 'Detalles del Pedido';
            }
            ?>
            <span class="sub-title"><?php echo esc_html($sub_title); ?></span>
        </div>
    </div>
    <div class="header-user-profile">
        <?php if (is_user_logged_in()) : 
            $current_user = wp_get_current_user();
            $user_role_name = !empty($current_user->roles) ? ucfirst(str_replace(['rol_', '_'], ' ', $current_user->roles[0])) : '';
        ?>
            <div class="user-info">
                <span class="user-name"><?php echo esc_html($current_user->display_name); ?></span>
                <span class="user-role"><?php echo esc_html($user_role_name); ?></span>
            </div>
            <div class="user-avatar">
                <?php echo get_avatar($current_user->ID, 32); ?>
            </div>
        <?php endif; ?>
    </div>
</header>
<!-- FIN DE LA CABECERA PROFESIONAL -->

<?php 
endif; // Fin de la condición
?>