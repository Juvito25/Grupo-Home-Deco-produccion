<?php
// --- FUNCIÓN GLOBAL PARA OBTENER LA LISTA DE SECTORES DE PRODUCCIÓN ---
function ghd_get_sectores_produccion() {
    return array(
        'Carpintería',
        'Costura',
        'Tapicería',
        'Logística',
        // Si en el futuro añades un nuevo sector, solo lo añades aquí.
    );
}
// Cargar nuestros estilos, fuentes Y SCRIPTS personalizados
add_action( 'wp_enqueue_scripts', 'ghd_enqueue_assets' );
function ghd_enqueue_assets() {
    // Cargar la fuente Inter desde Google Fonts
    wp_enqueue_style( 'google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', false );
    
    // Cargar la librería de iconos Font Awesome
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', array(), '6.4.2' );
    
    // Cargar nuestro style.css del tema hijo
    // (Asumiendo que también cargas los estilos del padre en otra función)
    wp_enqueue_style( 'ghd-style', get_stylesheet_uri(), array(), '1.0' );

    // Cargar nuestro script JS personalizado
    wp_enqueue_script( 'ghd-app', get_stylesheet_directory_uri() . '/js/app.js', array(), '1.0', true );
}


// --- REGISTRO DE LA ACCIÓN AJAX Y PASO DE VARIABLES A JS ---
add_action('wp_enqueue_scripts', 'ghd_localize_scripts');
function ghd_localize_scripts() {
    // Solo si estamos en la página del panel
    if (is_page_template('template-admin-dashboard.php')) {
        wp_localize_script('ghd-app', 'ghd_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ghd-ajax-nonce')
        ));
    }
}

// --- FUNCIÓN QUE MANEJA LA PETICIÓN AJAX ---
add_action('wp_ajax_ghd_update_order', 'ghd_update_order_callback');
function ghd_update_order_callback() {
    // 1. Seguridad: Verificar el nonce
    check_ajax_referer('ghd-ajax-nonce', 'nonce');

    // 2. Seguridad: Verificar permisos del usuario
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'No tienes permisos para realizar esta acción.'));
        return;
    }

    // 3. Recoger y sanitizar los datos
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $field    = isset($_POST['field']) ? sanitize_key($_POST['field']) : '';
    $value    = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '';

    if (!$order_id || !$field || !$value) {
        wp_send_json_error(array('message' => 'Faltan datos.'));
        return;
    }

    // 4. Actualizar el campo en la base de datos
    update_field($field, $value, $order_id);
    
    $response_data = array();

    // Si hemos cambiado la prioridad, calculamos la nueva clase CSS para devolverla
    if ($field === 'prioridad_pedido') {
        $new_class = 'tag-green'; // Baja por defecto
        if ($value === 'Alta') {
            $new_class = 'tag-red';
        } elseif ($value === 'Media') {
            $new_class = 'tag-yellow';
        }
        $response_data['new_class'] = $new_class;
    }

    // 5. Enviar respuesta de éxito
    wp_send_json_success($response_data);
}

// --- RELLENAR DINÁMICAMENTE EL CAMPO ACF 'sector_actual' ---
add_filter('acf/load_field/name=sector_actual', 'ghd_acf_load_sectores');
function ghd_acf_load_sectores($field) {
    // Limpiamos las opciones existentes
    $field['choices'] = array();
    
    // Añadimos 'Pendiente' como primera opción
    $field['choices']['Pendiente'] = 'Pendiente';

    // Obtenemos la lista de sectores de nuestra función central
    $sectores = ghd_get_sectores_produccion();
    foreach ($sectores as $sector) {
        $field['choices'][$sector] = $sector;
    }

    return $field;
}