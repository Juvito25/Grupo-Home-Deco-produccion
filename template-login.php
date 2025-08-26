<?php
/**
 * Template Name: GHD - Página de Login
 * Versión 1.1 - Corregida para mostrar el formulario correctamente.
 */

// Si el usuario ya ha iniciado sesión, lo redirigimos al panel correspondiente.
if (is_user_logged_in()) {
    if (current_user_can('manage_options')) {
        $dashboard_url = home_url('/panel-de-control/');
    } else {
        $dashboard_url = home_url('/mi-puesto/');
    }
    wp_redirect($dashboard_url);
    exit;
}

// --- COMIENZO DE LA VISTA ---
// Usamos get_header() para cargar los estilos, pero ignoraremos el bucle de contenido.
get_header(); 
?>

<div class="ghd-login-page">
    <div class="ghd-login-box">
        <h1 class="login-title">Gestor de Flujo de Producción</h1>
        <p class="login-subtitle">Inicia sesión para acceder a tu panel</p>

        <?php
        // Mostramos un mensaje de error si el login falló
        if (isset($_GET['login']) && $_GET['login'] == 'failed') {
            echo '<p class="login-error"><strong>ERROR:</strong> El usuario o la contraseña no son correctos.</p>';
        }
        ?>

        <?php 
        // Mostramos el formulario de login de WordPress, pero personalizado
        wp_login_form(array(
            'redirect'       => home_url(), // La redirección real la maneja nuestro hook en functions.php
            'label_username' => 'Correo Electrónico',
            'label_password' => 'Contraseña',
            'label_remember' => 'Recordarme',
            'label_log_in'   => 'Iniciar Sesión',
            'remember'       => true,
        )); 
        ?>
    </div>
</div>

<?php 
// Usamos get_footer() para cerrar la página correctamente.
get_footer(); 
?>