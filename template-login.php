<?php
/**
 * Template Name: GHD - Iniciar Sesión
 * Descripción: Página de inicio de sesión personalizada.
 */

// Si el usuario ya está logueado, redirigirlo usando el filtro login_redirect
// Esto asegura que la lógica de ghd_custom_login_redirect se aplique.
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    // Obtener la URL a la que se redirigiría con el filtro login_redirect
    // Pasamos null como requested_redirect_to para que el filtro decida
    $redirect_to_url = apply_filters( 'login_redirect', admin_url(), '', $user ); 
    wp_redirect( $redirect_to_url );
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
        // No hacer wp_redirect aquí. Dejar que WordPress use el filtro 'login_redirect'.
        // Simplemente redirigir a donde wp_signon enviaría por defecto, que luego será filtrado.
        // La URL oculta del formulario ya apunta a home_url('/'), que será filtrado.
        wp_redirect( apply_filters( 'login_redirect', home_url(), home_url(), $user ) );
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