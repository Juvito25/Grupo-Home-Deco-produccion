<?php
/**
 * Template Name: GHD - Iniciar Sesión
 * Descripción: Página de inicio de sesión personalizada.
 */

// --- LÓGICA DE PROCESAMIENTO DEL FORMULARIO DE LOGIN (INICIO) ---
if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
    $creds = array();
    $creds['user_login'] = sanitize_user( $_POST['log'] ); // Asumo que el campo de usuario/email se llama 'log'
    $creds['user_password'] = $_POST['pwd']; // Asumo que el campo de contraseña se llama 'pwd'
    $creds['remember'] = isset( $_POST['rememberme'] ); // Asumo que el checkbox se llama 'rememberme'

    $user = wp_signon( $creds, false ); // 'false' para no usar el redirect de WordPress por defecto

    if ( is_wp_error( $user ) ) {
        // Autenticación fallida
        $error_message = $user->get_error_message();
        // Redirigir de nuevo a la página de login con el error
        wp_redirect( home_url('/iniciar-sesion/?login=failed&message=' . urlencode($error_message)) );
        exit;
    } else {
        // Autenticación exitosa
        // Redirigir al usuario a su dashboard según su rol

        if ( in_array('administrator', (array) $user->roles) ) {
            $admin_pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-admin-dashboard.php']);
            $redirect_url = !empty($admin_pages) ? get_permalink($admin_pages[0]) : admin_url();
        } else {
            $sector_pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-sector-dashboard.php']);
            $sector_dashboard_url = !empty($sector_pages) ? get_permalink($sector_pages[0]) : home_url();
            
            // Añadir el parámetro de sector
            $user_roles = $user->roles;
            $user_role = !empty($user_roles) ? $user_roles[0] : '';
            $mapa_roles = ghd_get_mapa_roles_a_campos(); // Esta función debe estar definida en functions.php
            if (array_key_exists($user_role, $mapa_roles)) {
                $sector_name = ucfirst(str_replace(['rol_', '_'], ' ', $user_role));
                $clean_sector_name = strtolower(str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $sector_name));
                $redirect_url = add_query_arg('sector', urlencode($clean_sector_name), $sector_dashboard_url);
            } else {
                $redirect_url = $sector_dashboard_url; // Fallback si no es un rol de sector mapeado
            }
        }
        
        wp_redirect( $redirect_url );
        exit;
    }
}
// --- LÓGICA DE PROCESAMIENTO DEL FORMULARIO DE LOGIN (FIN) ---

// El resto de tu template-login.php (get_header(), HTML del formulario, etc.) va aquí abajo.
// Asegúrate de que el formulario POST a la URL de esta página.
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
                    // Mostrar mensaje de error si el login falló
                    if (isset($_GET['login']) && $_GET['login'] === 'failed') {
                        $msg = isset($_GET['message']) ? sanitize_text_field(urldecode($_GET['message'])) : 'Usuario o contraseña incorrectos.';
                        echo '<div class="login-error"><strong>Error:</strong> ' . esc_html($msg) . '</div>';
                    }
                    ?>

                    <form name="loginform" id="loginform" action="<?php echo esc_url( home_url('/iniciar-sesion/') ); ?>" method="post">
                        <p>
                            <label for="user_login">Correo Electrónico</label>
                            <input type="text" name="log" id="user_login" class="input" value="" size="20" autocomplete="username" required>
                        </p>
                        <p>
                            <label for="user_pass">Contraseña</label>
                            <input type="password" name="pwd" id="user_pass" class="input" value="" size="20" autocomplete="current-password" required>
                        </p>
                        <p class="login-remember">
                            <input name="rememberme" type="checkbox" id="rememberme" value="forever">
                            <label for="rememberme">Recordarme</label>
                        </p>
                        <p class="login-submit">
                            <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="Iniciar Sesión">
                        </p>
                        <?php wp_nonce_field( 'log-in' ); // Nonce para seguridad si tu formulario lo necesita ?>
                        <input type="hidden" name="redirect_to" value="<?php echo esc_url( home_url('/') ); ?>">
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php wp_footer(); ?>
</body>
</html>