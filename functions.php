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

// --- MANEJADOR AJAX PARA FILTRAR PEDIDOS EN EL PANEL DE ADMIN ---
add_action('wp_ajax_ghd_filter_orders', 'ghd_filter_orders_callback');
function ghd_filter_orders_callback() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');

    $args = array(
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => array('relation' => 'AND'), // Para combinar filtros
        's'              => '' // Para la búsqueda de texto
    );

    // Filtro por estado/sector
    if (!empty($_POST['status'])) {
        $args['meta_query'][] = array(
            'key'     => 'estado_pedido',
            'value'   => sanitize_text_field($_POST['status']),
            'compare' => '=',
        );
    }

    // Filtro por prioridad
    if (!empty($_POST['priority'])) {
        $args['meta_query'][] = array(
            'key'     => 'prioridad_pedido',
            'value'   => sanitize_text_field($_POST['priority']),
            'compare' => '=',
        );
    }
    
    // Filtro por búsqueda de texto
    if (!empty($_POST['search'])) {
        $args['s'] = sanitize_text_field($_POST['search']);
    }

    $pedidos_query = new WP_Query($args);
    
    ob_start(); // Inicia un buffer para capturar el HTML

    if ($pedidos_query->have_posts()) {
        while ($pedidos_query->have_posts()) {
            $pedidos_query->the_post();
            // --- REUTILIZAMOS EL HTML DE LA FILA DE LA TABLA ---
            // (Este es un extracto, el código completo está en el archivo de la plantilla)
            get_template_part('template-parts/order-row-admin');
        }
    } else {
        echo '<tr><td colspan="8" style="text-align:center;">No se encontraron pedidos que coincidan con los filtros.</td></tr>';
    }

    wp_reset_postdata();
    
    $html = ob_get_clean(); // Obtiene el HTML capturado
    wp_send_json_success(array('html' => $html));
}

// Para hacer esto más modular, movemos el código de la fila de la tabla a un archivo separado
// Crea una carpeta "template-parts" en tu tema hijo y dentro un archivo "order-row-admin.php"
// Pega el contenido del <tr>...</tr> de tu tabla principal en ese archivo nuevo.


// --- FUNCIÓN DE AYUDA PARA OBTENER LA URL DEL PANEL DE SECTOR ---
function ghd_get_sector_dashboard_url() {
    $args = array(
        'post_type'  => 'page',
        'fields'     => 'ids',
        'nopaging'   => true,
        'meta_key'   => '_wp_page_template',
        'meta_value' => 'template-sector-dashboard.php'
    );
    $pages = get_posts($args);
    if (!empty($pages)) {
        // Devuelve el permalink de la primera página que encuentre con esa plantilla
        return get_permalink($pages[0]);
    }
    // Si no encuentra ninguna página, devuelve la URL de inicio como fallback
    return home_url('/');
}
// --- FUNCIÓN PARA CONTROLAR EL ACCESO A LAS PÁGINAS DE LA APLICACIÓN ---
add_action('template_redirect', 'ghd_access_control');
function ghd_access_control() {
    // Solo ejecutamos esta lógica si estamos en una página que usa nuestras plantillas personalizadas.
    
    // Control para el Panel de Administrador
    if (is_page_template('template-admin-dashboard.php')) {
        // Si el usuario no es un administrador, lo redirigimos.
        if (!current_user_can('manage_options')) {
            wp_redirect(home_url('/'));
            exit;
        }
    }

    // Control para el Panel de Sector
    if (is_page_template('template-sector-dashboard.php')) {
        // Si el usuario es un administrador, lo redirigimos a su propio panel.
        if (current_user_can('manage_options')) {
            // Primero buscamos la URL del panel de admin de forma dinámica
            $admin_dashboard_url = home_url('/'); // URL por defecto
            $admin_pages = get_posts(array(
                'post_type'  => 'page', 'fields' => 'ids', 'nopaging' => true,
                'meta_key' => '_wp_page_template', 'meta_value' => 'template-admin-dashboard.php'
            ));
            if (!empty($admin_pages)) {
                $admin_dashboard_url = get_permalink($admin_pages[0]);
            }
            wp_redirect($admin_dashboard_url);
            exit;
        }
    }
}