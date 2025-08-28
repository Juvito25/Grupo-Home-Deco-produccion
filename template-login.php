<?php
/**
 * Template Name: GHD - Página de Login
 * Versión 3.0 - Con layout de dos columnas.
 */

if (is_user_logged_in()) {
    $dashboard_url = current_user_can('manage_options') ? home_url('/panel-de-control/') : home_url('/mis-tareas/');
    wp_redirect($dashboard_url);
    exit;
}

get_header(); 
?>

<!-- El contenedor principal ahora tiene una clase para el layout dividido -->
<div class="ghd-login-split-layout"> 
    
    <!-- Columna 1: El panel de la imagen -->
    <div class="login-image-panel">
        <!-- La imagen se añadirá con CSS como fondo -->
    </div>

    <!-- Columna 2: El panel del formulario -->
    <div class="login-form-panel">
        <div class="ghd-login-box">
            <h1 class="login-title">Gestor de Flujo de Producción</h1>
            <p class="login-subtitle">Inicia sesión para acceder a tu panel</p>

            <?php
            if (isset($_GET['login']) && $_GET['login'] == 'failed') {
                echo '<p class="login-error"><strong>ERROR:</strong> El usuario o la contraseña no son correctos.</p>';
            }
            ?>

            <?php 
            wp_login_form(array(
                'redirect'       => home_url(),
                'label_username' => 'Correo Electrónico',
                'label_password' => 'Contraseña',
                'label_remember' => 'Recordarme',
                'label_log_in'   => 'Iniciar Sesión',
                'remember'       => true,
            )); 
            ?>
        </div>
    </div>

</div>

<?php get_footer(); ?>