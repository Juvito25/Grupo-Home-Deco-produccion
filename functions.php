<?php
/**
 * functions.php - Versión 2.7 - Lógica de Login Añadida
 */

// --- 1. CARGA DE ESTILOS Y SCRIPTS ---
add_action('wp_enqueue_scripts', 'ghd_enqueue_assets');
function ghd_enqueue_assets() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', false);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', [], '6.4.2');
    wp_enqueue_style('ghd-style', get_stylesheet_uri(), ['parent-style'], '2.7');
    wp_enqueue_script('ghd-app', get_stylesheet_directory_uri() . '/js/app.js', [], '2.7', true);
    wp_localize_script('ghd-app', 'ghd_ajax', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ghd-ajax-nonce')]);
}

// --- 2. REGISTRO DE CUSTOM POST TYPES ---
add_action('init', 'ghd_registrar_cpt_historial');
function ghd_registrar_cpt_historial() {
    register_post_type('ghd_historial', ['labels' => ['name' => 'Historial de Producción'], 'public' => false, 'show_ui' => true, 'show_in_menu' => 'edit.php?post_type=orden_produccion', 'supports' => ['title']]);
}

// --- 3. FUNCIONES DE AYUDA ---
function ghd_get_sectores() { return ['Carpintería', 'Corte', 'Costura', 'Tapicería', 'Embalaje', 'Logística', 'Administrativo']; }
function ghd_get_mapa_roles_a_campos() {
    return ['rol_carpinteria' => 'estado_carpinteria', 'rol_corte' => 'estado_corte', 'rol_costura' => 'estado_costura', 'rol_tapiceria' => 'estado_tapiceria', 'rol_embalaje' => 'estado_embalaje', 'rol_logistica' => 'estado_logistica', 'rol_administrativo' => 'estado_administrativo'];
}

// --- 4. LÓGICA DE LOGIN/LOGOUT (AÑADIDA Y CORREGIDA) ---
add_filter('login_redirect', 'ghd_custom_login_redirect', 10, 3);
function ghd_custom_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('administrator', $user->roles)) {
            // Buscamos dinámicamente la URL de la página del panel de admin
            $admin_pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-admin-dashboard.php']);
            return !empty($admin_pages) ? get_permalink($admin_pages[0]) : home_url();
        } else {
            // Para otros roles, buscamos la URL de la página del panel de sector
            $sector_pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-sector-dashboard.php']);
            return !empty($sector_pages) ? get_permalink($sector_pages[0]) : home_url();
        }
    }
    return $redirect_to;
}
add_action('wp_login_failed', 'ghd_login_fail_redirect');
function ghd_login_fail_redirect($username) {
    $referrer = $_SERVER['HTTP_REFERER'];
    if (!empty($referrer) && !strstr($referrer, 'wp-login') && !strstr($referrer, 'wp-admin')) {
        wp_redirect(home_url('/iniciar-sesion/?login=failed'));
        exit;
    }
}
add_action('after_setup_theme', 'ghd_hide_admin_bar');
function ghd_hide_admin_bar() {
    if (!current_user_can('manage_options')) {
        show_admin_bar(false);
    }
}


// --- 4. LÓGICA AJAX ---
add_action('wp_ajax_ghd_admin_action', function() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce'); if (!current_user_can('edit_posts')) wp_send_json_error();
    $id = intval($_POST['order_id']); $type = sanitize_key($_POST['type']);
    if ($type === 'start_production') {
        update_field('estado_carpinteria', 'Pendiente', $id);
        update_field('estado_corte', 'Pendiente', $id);
        update_field('estado_pedido', 'En Producción', $id);
        wp_insert_post(['post_title' => 'Producción Iniciada', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
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
    
    update_field($field, $value, $id);
    wp_insert_post(['post_title' => ucfirst(str_replace(['estado_', '_'], ' ', $field)) . ' -> ' . $value, 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
    
    if ($value === 'Completado') {
        // Regla 1: Carpintería y Corte -> Costura
        if (get_field('estado_carpinteria', $id) == 'Completado' && get_field('estado_corte', $id) == 'Completado' && get_field('estado_costura', $id) == 'No Asignado') {
            update_field('estado_costura', 'Pendiente', $id); update_field('estado_pedido', 'En Costura', $id);
            wp_insert_post(['post_title' => 'Fase 1 completa -> A Costura', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
        }
        // Regla 2: Costura -> Tapicería y Embalaje
        if (get_field('estado_costura', $id) == 'Completado' && get_field('estado_tapiceria', $id) == 'No Asignado') {
            update_field('estado_tapiceria', 'Pendiente', $id); update_field('estado_embalaje', 'Pendiente', $id);
            update_field('estado_pedido', 'En Tapicería/Embalaje', $id);
            wp_insert_post(['post_title' => 'Fase Costura completa -> A Tapicería/Embalaje', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
        }
        // Regla 3: Tapicería y Embalaje -> Logística
        if (get_field('estado_tapiceria', $id) == 'Completado' && get_field('estado_embalaje', $id) == 'Completado' && get_field('estado_logistica', $id) == 'No Asignado') {
            update_field('estado_logistica', 'Pendiente', $id); update_field('estado_pedido', 'Listo para Entrega', $id);
            wp_insert_post(['post_title' => 'Fase Tapicería/Embalaje completa -> A Logística', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
        }
        // NUEVA REGLA 4: Logística -> Administrativo
        if (get_field('estado_logistica', $id) == 'Completado' && get_field('estado_administrativo', $id) == 'No Asignado') {
            update_field('estado_administrativo', 'Pendiente', $id);
            update_field('estado_pedido', 'Pendiente Administrativo', $id); // Estado general para el admin
            wp_insert_post(['post_title' => 'Entrega Completada -> A Administrativo', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
        }

        wp_send_json_success(['message' => 'Tarea completada.']);
    } else {
        // Si no se completó, devolvemos el HTML de la tarjeta actualizada.
        ob_start();
        get_template_part('template-parts/task-card-v2', null, ['id' => $id, 'campo_estado' => $field]);
        $html = ob_get_clean();
        wp_send_json_success(['message' => 'Estado actualizado.', 'html' => $html]);
    }
    wp_die();
});

// --- MANEJADOR AJAX PARA ARCHIVAR PEDIDOS DESDE EL PANEL ADMINISTRATIVO ---
add_action('wp_ajax_ghd_archive_order', 'ghd_archive_order_callback');
function ghd_archive_order_callback() {
    // 1. Seguridad: Verificar nonce y permisos
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('manage_options') && !current_user_can('rol_administrativo')) {
        wp_send_json_error(['message' => 'No tienes permisos.']);
    }

    // 2. Validación de Datos
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error(['message' => 'ID de pedido no válido.']);
    }

    // 3. Actualizar los campos para cerrar el pedido
    update_field('estado_administrativo', 'Archivado', $order_id);
    update_field('estado_pedido', 'Completado y Archivado', $order_id);

    // 4. Añadir la entrada final al historial
    wp_insert_post([
        'post_title'   => 'Pedido Cerrado y Archivado',
        'post_type'    => 'ghd_historial',
        'post_status'  => 'publish',
        'meta_input'   => ['_orden_produccion_id' => $order_id]
    ]);

    // 5. Enviar respuesta de éxito
    wp_send_json_success(['message' => 'Pedido archivado con éxito.']);
    wp_die();
}