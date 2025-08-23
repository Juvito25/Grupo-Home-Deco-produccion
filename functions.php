<?php
// Cargar estilos del tema padre
add_action('wp_enqueue_scripts', 'ghd_enqueue_parent_styles');
function ghd_enqueue_parent_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
}

// Cargar nuestros estilos, fuentes Y SCRIPTS personalizados
add_action('wp_enqueue_scripts', 'ghd_enqueue_assets');
function ghd_enqueue_assets() {
    // Fuentes y Librerías
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', false);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', array(), '6.4.2');
    
    // Archivo de estilos principal
    wp_enqueue_style('ghd-style', get_stylesheet_uri(), array(), '1.1');

    // Archivo JavaScript principal
    wp_enqueue_script('ghd-app', get_stylesheet_directory_uri() . '/js/app.js', array(), '1.1', true);

    // Pasar variables de PHP a JavaScript (AJAX)
    wp_localize_script('ghd-app', 'ghd_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('ghd-ajax-nonce')
    ));
}

// --- FUNCIONES DE AYUDA GLOBALES ---

// Función para obtener la lista de sectores de producción
function ghd_get_sectores_produccion() {
    return array('Carpintería', 'Costura', 'Tapicería', 'Logística');
}

// Función para determinar el siguiente sector en el flujo
function ghd_get_next_sector($current_sector) {
    $flujo_produccion = array(
        'Carpintería' => 'Costura',
        'Costura'     => 'Tapicería',
        'Tapicería'   => 'Logística',
        'Logística'   => 'Completado'
    );
    return isset($flujo_produccion[$current_sector]) ? $flujo_produccion[$current_sector] : null;
}

// Rellenar dinámicamente el campo ACF 'sector_actual'
add_filter('acf/load_field/name=sector_actual', 'ghd_acf_load_sectores');
function ghd_acf_load_sectores($field) {
    $field['choices'] = array();
    $field['choices']['Pendiente'] = 'Pendiente';
    $sectores = ghd_get_sectores_produccion();
    foreach ($sectores as $sector) {
        $field['choices'][$sector] = $sector;
    }
    return $field;
}


// --- LÓGICA AJAX ---

// Manejador AJAX para el Panel de Administrador
add_action('wp_ajax_ghd_update_order', 'ghd_update_order_callback');
function ghd_update_order_callback() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'No tienes permisos.'));
        return;
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $field    = isset($_POST['field']) ? sanitize_key($_POST['field']) : '';
    $value    = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '';

    if (!$order_id || !$field || !$value) {
        wp_send_json_error(array('message' => 'Faltan datos.'));
        return;
    }

    update_field($field, $value, $order_id);
    
    // Si estamos cambiando el sector, TAMBIÉN debemos cambiar el estado.
    if ($field === 'sector_actual') {
        update_field('estado_pedido', $value, $order_id);
    }

    $response_data = array();
    if ($field === 'prioridad_pedido') {
        $new_class = 'tag-green';
        if ($value === 'Alta') $new_class = 'tag-red';
        elseif ($value === 'Media') $new_class = 'tag-yellow';
        $response_data['new_class'] = $new_class;
    }

    wp_send_json_success($response_data);
}

// Manejador AJAX para el Panel de Sector
add_action('wp_ajax_ghd_move_to_next_sector', 'ghd_move_to_next_sector_callback');
function ghd_move_to_next_sector_callback() {
    check_ajax_referer('ghd_move_order_nonce', 'nonce');
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'No tienes permisos.'));
        return;
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error(array('message' => 'ID de pedido no válido.'));
        return;
    }

    $current_sector = get_field('sector_actual', $order_id);
    $next_sector = ghd_get_next_sector($current_sector);

    if (!$next_sector) {
        wp_send_json_error(array('message' => 'No se pudo determinar el siguiente sector.'));
        return;
    }

    // Actualizamos AMBOS campos
    update_field('sector_actual', $next_sector, $order_id);
    update_field('estado_pedido', $next_sector, $order_id);

    wp_send_json_success(array('message' => 'Pedido movido a ' . $next_sector));
}