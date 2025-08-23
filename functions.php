<?php
/**
 * Funciones del Tema para el Gestor de Producción de Grupo Home Deco.
 */

// --- 1. CARGA DE ESTILOS Y SCRIPTS ---
add_action('wp_enqueue_scripts', 'ghd_enqueue_assets');
function ghd_enqueue_assets() {
    // Cargar estilos del tema padre (Hello Elementor)
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    
    // Cargar librerías externas
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', false);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', array(), '6.4.2');
    
    // Cargar el archivo de estilos principal de nuestro tema hijo
    wp_enqueue_style('ghd-style', get_stylesheet_uri(), array('parent-style'), '1.2'); // Incrementamos versión

    // Cargar el archivo JavaScript principal de nuestra aplicación
    wp_enqueue_script('ghd-app', get_stylesheet_directory_uri() . '/js/app.js', array(), '1.2', true);
}

// --- 2. PASAR DATOS DE PHP A JAVASCRIPT ---
add_action('wp_enqueue_scripts', 'ghd_localize_scripts');
function ghd_localize_scripts() {
    // Solo cargamos estos datos si estamos en una página de nuestra aplicación
    if (is_page_template('template-admin-dashboard.php') || is_page_template('template-sector-dashboard.php')) {
        wp_localize_script('ghd-app', 'ghd_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ghd-ajax-nonce')
        ));
    }
}


// --- 3. FUNCIONES DE AYUDA GLOBALES ---

/**
 * Devuelve la lista de sectores de producción.
 * Centraliza la lógica para un mantenimiento fácil.
 */
function ghd_get_sectores_produccion() {
    return array('Carpintería', 'Costura', 'Tapicería', 'Logística');
}

/**
 * Devuelve el siguiente sector en el flujo de producción.
 * @param string $current_sector El sector actual.
 * @return string|null El siguiente sector o null si es el final.
 */
function ghd_get_next_sector($current_sector) {
    $flujo_produccion = array(
        'Carpintería' => 'Costura',
        'Costura'     => 'Tapicería',
        'Tapicería'   => 'Logística',
        'Logística'   => 'Completado'
    );
    return isset($flujo_produccion[$current_sector]) ? $flujo_produccion[$current_sector] : null;
}


// --- 4. INTEGRACIÓN CON ADVANCED CUSTOM FIELDS (ACF) ---

/**
 * Rellena dinámicamente el campo de selección 'sector_actual' de ACF
 * con los valores de nuestra función ghd_get_sectores_produccion().
 */
add_filter('acf/load_field/name=sector_actual', 'ghd_acf_load_sectores');
function ghd_acf_load_sectores($field) {
    $field['choices'] = array();
    $field['choices']['Pendiente'] = 'Pendiente'; // Estado inicial
    $sectores = ghd_get_sectores_produccion();
    foreach ($sectores as $sector) {
        $field['choices'][$sector] = $sector;
    }
    return $field;
}


// --- 5. LÓGICA DE PETICIONES AJAX ---

/**
 * Manejador AJAX para actualizar un pedido desde el Panel de Administrador.
 */
add_action('wp_ajax_ghd_update_order', 'ghd_update_order_callback');
function ghd_update_order_callback() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'No tienes permisos.'));
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $field    = isset($_POST['field']) ? sanitize_key($_POST['field']) : '';
    $value    = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '';

    if (!$order_id || !$field || !$value) {
        wp_send_json_error(array('message' => 'Faltan datos.'));
    }

    // Actualizamos el campo principal (prioridad_pedido o sector_actual)
    update_field($field, $value, $order_id);
    
    // Lógica adicional: si se asigna un sector, también se actualiza el estado.
    if ($field === 'sector_actual') {
        update_field('estado_pedido', $value, $order_id);
    }

    // Preparamos datos para la respuesta al frontend
    $response_data = array();
    if ($field === 'prioridad_pedido') {
        $new_class = 'tag-green';
        if ($value === 'Alta') $new_class = 'tag-red';
        elseif ($value === 'Media') $new_class = 'tag-yellow';
        $response_data['new_class'] = $new_class;
    }

    wp_send_json_success($response_data);
}

/**
 * Manejador AJAX para mover un pedido al siguiente sector desde el Panel de Sector.
 */
add_action('wp_ajax_ghd_move_to_next_sector', 'ghd_move_to_next_sector_callback');
function ghd_move_to_next_sector_callback() {
    check_ajax_referer('ghd_ajax-nonce', 'nonce'); // Usamos el mismo nonce para simplificar
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'No tienes permisos.'));
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error(array('message' => 'ID de pedido no válido.'));
    }

    $current_sector = get_field('sector_actual', $order_id);
    $next_sector = ghd_get_next_sector($current_sector);

    if (!$next_sector) {
        wp_send_json_error(array('message' => 'No se pudo determinar el siguiente sector.'));
    }

    // Actualizamos ambos campos para mantener la consistencia
    update_field('sector_actual', $next_sector, $order_id);
    update_field('estado_pedido', $next_sector, $order_id);

    wp_send_json_success(array('message' => 'Pedido movido a ' . $next_sector));
}