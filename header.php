<?php
/**
 * El header para nuestro tema.
 * Versión V2.2 - Añadida la plantilla de Sectores a la lógica condicional.
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
// --- LÓGICA CONDICIONAL CORREGIDA ---
// Añadimos la nueva plantilla 'template-sectores.php' a la lista.
if ( 
    is_page_template('template-admin-dashboard.php') || 
    is_page_template('template-sector-dashboard.php') || 
    is_page_template('template-sectores.php') || // <-- LÍNEA AÑADIDA
    is_singular('orden_produccion') 
) :
?>

<!-- INICIO DE LA CABECERA PROFESIONAL (SOLO PARA LA APP) -->
<header class="ghd-pro-header">
    <div class="header-logo-title">
        <a href="<?php echo home_url(); ?>" class="site-logo-link">
            <h1 class="main-title">GRUPO DECO HOME</h1> <!-- Texto grande -->
            <span class="sub-title">S.R.L</span> <!-- Texto pequeño debajo -->
            <!-- La imagen del logo.png ya NO va aquí si el texto la reemplaza visualmente -->
        </a>
        <div class="app-name-group"> <!-- Nuevo div para el nombre de la aplicación -->
            <h1 class="main-title-app">Gestor de Flujo de Producción</h1>
            <span class="sub-title-app">Panel de Control</span>
        </div>
    </div>
    <?php $current_user = wp_get_current_user(); ?>
    <div class="header-user-profile">
        <div class="user-info">
            <span class="user-name"><?php echo esc_html($current_user->display_name); ?></span>
            <span class="user-role"><?php echo esc_html(ucfirst($current_user->roles[0])); ?></span>
        </div>
        <div class="user-avatar-wrapper">
            <?php 
            $avatar_url = get_avatar_url($current_user->ID, ['size' => 40]);
            if ($avatar_url) : ?>
                <img src="<?php echo esc_url($avatar_url); ?>" alt="Avatar de <?php echo esc_attr($current_user->display_name); ?>" class="user-avatar-img">
            <?php else : ?>
                <i class="fa-solid fa-circle-user user-avatar-placeholder"></i>
            <?php endif; ?>
        </div>
    </div>
</header><!-- FIN DE LA CABECERA PROFESIONAL -->

<?php 
endif; // Fin de la condición
?>