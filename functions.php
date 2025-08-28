<?php
/**
 * functions.php - Versión 2.1 - Lógica de Flujo y Redirección Corregidas
 */

// --- 1. CARGA DE ESTILOS Y SCRIPTS ---
add_action('wp_enqueue_scripts', 'ghd_enqueue_assets');
function ghd_enqueue_assets() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', false);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', [], '6.4.2');
    wp_enqueue_style('ghd-style', get_stylesheet_uri(), ['parent-style'], '2.1');
    wp_enqueue_script('ghd-app', get_stylesheet_directory_uri() . '/js/app.js', [], '2.1', true);
    wp_localize_script('ghd-app', 'ghd_ajax', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ghd-ajax-nonce')]);
}

// --- 2. REGISTRO DE CUSTOM POST TYPES ---
add_action('init', 'ghd_registrar_cpt_historial');
function ghd_registrar_cpt_historial() {
    register_post_type('ghd_historial', ['labels' => ['name' => 'Historial de Producción'], 'public' => false, 'show_ui' => true, 'show_in_menu' => 'edit.php?post_type=orden_produccion', 'supports' => ['title']]);
}

// --- 3. FUNCIONES DE AYUDA ---
function ghd_get_sectores() { return ['Carpintería', 'Corte', 'Costura', 'Tapicería', 'Embalaje', 'Logística']; }
function ghd_get_mapa_roles_a_campos() {
    return ['rol_carpinteria' => 'estado_carpinteria', 'rol_corte' => 'estado_corte', 'rol_costura' => 'estado_costura', 'rol_tapiceria' => 'estado_tapiceria', 'rol_embalaje' => 'estado_embalaje', 'rol_logistica' => 'estado_logistica' ];
}

// --- 4. LÓGICA DE LOGIN/LOGOUT ---
add_filter('login_redirect', 'ghd_custom_login_redirect', 10, 3);
function ghd_custom_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('administrator', $user->roles)) {
            $pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-admin-dashboard.php']);
            return !empty($pages) ? get_permalink($pages[0]) : home_url();
        } else {
            $pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-sector-dashboard.php']);
            return !empty($pages) ? get_permalink($pages[0]) : home_url();
        }
    }
    return $redirect_to;
}
// (Aquí puedes añadir tus otras funciones de login como ghd_hide_admin_bar, etc.)


// --- 5. LÓGICA AJAX ---
add_action('wp_ajax_ghd_admin_action', function() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error();
    $id = intval($_POST['order_id']); $type = sanitize_key($_POST['type']);
    if ($type === 'start_production') {
        update_field('estado_carpinteria', 'Pendiente', $id);
        update_field('estado_corte', 'Pendiente', $id);
        update_field('estado_pedido', 'En Producción', $id);
        wp_insert_post(['post_title' => 'Producción Iniciada por Admin', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
        wp_send_json_success(['message' => 'Producción iniciada.']);
    }
    wp_send_json_error(['message' => 'Acción no reconocida.']);
});


add_action('wp_ajax_ghd_update_task_status', function() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('read')) wp_send_json_error();

    $id = intval($_POST['order_id']);
    $field = sanitize_key($_POST['field']);
    $value = sanitize_text_field($_POST['value']);
    
    // Actualizamos el estado del sub-proceso
    update_field($field, $value, $id);
    wp_insert_post(['post_title' => ucfirst(str_replace(['estado_', '_'], ' ', $field)) . ' -> ' . $value, 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
    
    // --- LÓGICA DE TRANSICIÓN DE FASE ---
    if ($value === 'Completado') {
        
        // Regla 1: Si Carpintería Y Corte están completos, pasamos a Costura.
        if ( ( $field === 'estado_carpinteria' || $field === 'estado_corte' ) && 
             get_field('estado_carpinteria', $id) == 'Completado' && 
             get_field('estado_corte', $id) == 'Completado' && 
             get_field('estado_costura', $id) == 'No Asignado' ) {
            
            update_field('estado_costura', 'Pendiente', $id);
            update_field('estado_pedido', 'En Costura', $id);
            wp_insert_post(['post_title' => 'Fase 1 completa, movido a Costura', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
        }
        
        // NUEVA REGLA 2: Si Costura está completo, pasamos a Tapicería y Embalaje.
        if ( $field === 'estado_costura' && 
             get_field('estado_tapiceria', $id) == 'No Asignado' ) {
            
            update_field('estado_tapiceria', 'Pendiente', $id);
            update_field('estado_embalaje', 'Pendiente', $id);
            update_field('estado_pedido', 'En Tapicería/Embalaje', $id);
            wp_insert_post(['post_title' => 'Fase Costura completa, movido a Tapicería/Embalaje', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
        }

        // (Aquí irían las demás reglas de transición, ej: Tapicería/Embalaje -> Logística)
    }
    
    wp_send_json_success(['message' => 'Estado actualizado.']);
});