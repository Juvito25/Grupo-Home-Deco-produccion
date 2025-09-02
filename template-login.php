<?php
/**
 * Template Name: GHD - Iniciar Sesión
 * Descripción: Página de inicio de sesión personalizada.
 */

// Si el usuario ya está logueado, redirigirlo a su dashboard
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    if (in_array('administrator', (array) $user->roles)) {
        $admin_pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-admin-dashboard.php']);
        $redirect_url = !empty($admin_pages) ? get_permalink($admin_pages[0]) : admin_url();
    } else {
        $sector_pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-sector-dashboard.php']);
        $sector_dashboard_url = !empty($sector_pages) ? get_permalink($sector_pages[0]) : home_url();
        
        $user_roles = $user->roles;
        $user_role = !empty($user_roles) ? $user_roles[0] : '';
        $mapa_roles = ghd_get_mapa_roles_a_campos(); // Esta función debe estar definida en functions.php
        if (array_key_exists($user_role, $mapa_roles)) {
            $sector_name = ucfirst(str_replace(['rol_', '_'], ' ', $user_role));
            $clean_sector_name = strtolower(str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $sector_name));
            // Ya no añadimos el parámetro ?sector aquí, el template-sector-dashboard lo determinará.
            $redirect_url = $sector_dashboard_url; 
        } else {
            $redirect_url = $sector_dashboard_url;
        }
    }
    wp_redirect( $redirect_url );
    exit;
}

// --- LÓGICA DE PROCESAMIENTO DEL FORMULARIO DE LOGIN (SI NO ESTÁ LOGUEADO) ---
$login_error_message = '';
if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
    $creds = array();
    $creds['user_login'] = sanitize_user( $_POST['log'] );
    $creds['user_password'] = $_POST['pwd'];
    $creds['remember'] = isset( $_POST['rememberme'] );

    // Eliminar la redirección automática de wp_signon si ocurre, para control manual
    add_filter( 'login_redirect', '__return_false' ); 
    $user = wp_signon( $creds, false ); 
    remove_filter( 'login_redirect', '__return_false' );

    if ( is_wp_error( $user ) ) {
        // Autenticación fallida
        $login_error_message = $user->get_error_message();
    } else {
        // Autenticación exitosa
        // Redirigir al usuario a su dashboard según su rol
        if ( in_array('administrator', (array) $user->roles) ) {
            $admin_pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-admin-dashboard.php']);
            $redirect_url = !empty($admin_pages) ? get_permalink($admin_pages[0]) : admin_url();
        } else {
            $sector_pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-sector-dashboard.php']);
            $sector_dashboard_url = !empty($sector_pages) ? get_permalink($sector_pages[0]) : home_url();
            
            $user_roles = $user->roles;
            $user_role = !empty($user_roles) ? $user_roles[0] : '';
            $mapa_roles = ghd_get_mapa_roles_a_campos();
            if (array_key_exists($user_role, $mapa_roles)) {
                // Ya no añadimos el parámetro ?sector aquí, el template-sector-dashboard lo determinará.
                $redirect_url = $sector_dashboard_url; 
            } else {
                $redirect_url = $sector_dashboard_url;
            }
        }
        wp_redirect( $redirect_url );
        exit;
    }
}

// Mensaje de error si viene de una redirección con ?login=failed o del POST fallido
if (empty($login_error_message) && isset($_GET['login']) && $_GET['login'] === 'failed') {
    $login_error_message = isset($_GET['message']) ? sanitize_text_field(urldecode($_GET['message'])) : 'Usuario o contraseña incorrectos.';
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar Sesión - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body class="page-template-template-login">
    <div class="ghd-login-split-layout">
        <div class="login-image-panel"></div>
        <div class="login-form-panel">
            <div class="ghd-login-box">
                <div class="login-form-box">
                    <h2 class="login-title">Gestor de Flujo de Producción</h2>
                    <p class="login-subtitle">Inicia sesión para acceder a tu panel</p>

                    <?php 
                    if (!empty($login_error_message)) {
                        echo '<div class="login-error"><strong>Error:</strong> ' . esc_html($login_error_message) . '</div>';
                    }
                    ?>

                    <form name="loginform" id="loginform" action="<?php echo esc_url( home_url('/iniciar-sesion/') ); ?>" method="post">
                        <p>
                            <label for="user_login">Correo Electrónico</label>
                            <input type="text" name="log" id="user_login" class="input" value="<?php echo ( isset( $_POST['log'] ) ? esc_attr( wp_unslash( $_POST['log'] ) ) : '' ); ?>" size="20" autocomplete="username" required>
                        </p>
                        <p>
                            <label for="user_pass">Contraseña</label>
                            <input type="password" name="pwd" id="user_pass" class="input" value="" size="20" autocomplete="current-password" required>
                        </p>
                        <p class="login-remember">
                            <input name="rememberme" type="checkbox" id="rememberme" value="forever" <?php echo ( isset( $_POST['rememberme'] ) ? 'checked="checked"' : '' ); ?>>
                            <label for="rememberme">Recordarme</label>
                        </p>
                        <p class="login-submit">
                            <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="Iniciar Sesión">
                        </p>
                        <?php // wp_nonce_field( 'log-in' ); // Nonce ya no es estrictamente necesario si wp_signon maneja la seguridad ?>
                        <input type="hidden" name="redirect_to" value="<?php echo esc_url( home_url('/') ); ?>">
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php wp_footer(); ?>
</body>
</html>