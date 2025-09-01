<?php
/**
 * Template Name: GHD - Configuración
 * Descripción: Página de configuración del sistema.
 */

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    auth_redirect();
}
get_header(); 
?>

<div class="ghd-app-wrapper">
    
    <?php get_template_part('template-parts/sidebar-admin'); ?>

    <main class="ghd-main-content">
        
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <h2>Configuración del Sistema</h2>
            </div> 
        </header>

        <div class="ghd-main-content-body">
            <div class="ghd-card">
                <h3 class="card-section-title">Información General de la Empresa</h3>
                <?php
                // Para manejar esta opción, idealmente usarías un campo de Opciones de ACF.
                // Si no tienes ACF Options Pages, puedes usar get_option/update_option o hardcodearlo temporalmente.
                // Asumiremos un campo de opciones de ACF llamado 'razon_social_empresa'.
                $razon_social = get_field('razon_social_empresa', 'option'); // 'option' es el ID para ACF Options Page
                if (empty($razon_social)) {
                    $razon_social = 'GRUPO DECO HOME S.R.L.'; // Valor por defecto
                }
                ?>
                <p><strong>Razón Social:</strong> <?php echo esc_html($razon_social); ?></p>
                <p>Aquí podés gestionar las configuraciones generales de tu sistema.</p>
                
                <p style="margin-top: 1.5rem; font-size: 0.9em; color: var(--texto-secundario);">
                    Para editar la Razón Social, por favor, andá a las opciones de tema de ACF (si está configurado) o a la configuración de tu tema de WordPress.
                </p>
                <!-- Aquí podrías añadir más opciones de configuración a futuro -->
            </div>
            
            <div class="ghd-card" style="margin-top: 1.5rem;">
                <h3 class="card-section-title">Opciones de Notificación (n8n)</h3>
                <p>Aquí se podrían configurar las URLs de los webhooks de n8n, plantillas de mensaje, etc.</p>
                <p style="font-size: 0.9em; color: var(--texto-secundario);">
                    Actualmente, la configuración de webhooks está en el código (`functions.php`).
                </p>
            </div>
        </div>
    </main>
</div>
<?php get_footer(); ?>