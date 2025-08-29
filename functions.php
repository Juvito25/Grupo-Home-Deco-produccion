<?php
/**
 * functions.php - Versión 3.1 - Con Filtro de Búsqueda Corregido
 */

// --- 1. CARGA DE ESTILOS Y SCRIPTS ---
add_action('wp_enqueue_scripts', 'ghd_enqueue_assets');
function ghd_enqueue_assets() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', false);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', [], '6.4.2');
    wp_enqueue_style('ghd-style', get_stylesheet_uri(), ['parent-style'], '3.1');
    wp_enqueue_script('ghd-app', get_stylesheet_directory_uri() . '/js/app.js', [], '3.1', true);
    
    wp_localize_script('ghd-app', 'ghd_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('ghd-ajax-nonce')
    ]);
}

// --- 2. REGISTRO DE CUSTOM POST TYPES ---
add_action('init', 'ghd_registrar_cpt_historial');
function ghd_registrar_cpt_historial() {
    register_post_type('ghd_historial', ['labels' => ['name' => 'Historial de Producción'], 'public' => false, 'show_ui' => true, 'show_in_menu' => 'edit.php?post_type=orden_produccion', 'supports' => ['title']]);
}

// --- 3. FUNCIONES DE AYUDA ---
function ghd_get_sectores() { return ['Carpintería', 'Corte', 'Costura', 'Tapicería', 'Embalaje', 'Logística']; }
function ghd_get_mapa_roles_a_campos() { /* ... (sin cambios) ... */ }
function ghd_get_next_sector($sector) { /* ... (sin cambios) ... */ }
function ghd_prepare_order_row_data($post_id) { /* ... (tu función completa y correcta aquí) ... */ }

// --- 4. LÓGICA DE LOGIN/LOGOUT ---
add_filter('login_redirect', 'ghd_custom_login_redirect', 10, 3);
function ghd_custom_login_redirect($redirect_to, $request, $user) { /* ... (tu función completa y correcta aquí) ... */ }
add_action('wp_login_failed', 'ghd_login_fail_redirect');
function ghd_login_fail_redirect($username) { /* ... (tu función completa y correcta aquí) ... */ }

// --- 5. LÓGICA AJAX ---
add_action('wp_ajax_ghd_admin_action', function() { /* ... (código existente sin cambios) ... */ });
add_action('wp_ajax_ghd_update_task_status', function() { /* ... (código existente sin cambios) ... */ });

// --- FUNCIÓN DE FILTRADO CORREGIDA Y FINAL ---
// --- REEMPLAZA ESTA FUNCIÓN COMPLETA EN TU functions.php ---

add_action('wp_ajax_ghd_filter_orders', 'ghd_filter_orders_callback');
function ghd_filter_orders_callback() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');

    // 1. OBTENER TODOS LOS PEDIDOS
    $args = array(
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
    );
    $pedidos_query = new WP_Query($args);

    // Sanitizamos los valores de los filtros
    $status_filter   = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $priority_filter = isset($_POST['priority']) ? sanitize_text_field($_POST['priority']) : '';
    $search_filter   = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

    ob_start();

    if ($pedidos_query->have_posts()) {
        $found_posts = false;
        while ($pedidos_query->have_posts()) {
            $pedidos_query->the_post();
            $post_id = get_the_ID();

            // 2. FILTRAR LOS RESULTADOS EN PHP
            $estado = get_field('estado_pedido', $post_id);
            $prioridad = get_field('prioridad_pedido', $post_id);
            $cliente = get_field('nombre_cliente', $post_id);
            $codigo = get_the_title($post_id);

            // Comprobamos si el pedido actual pasa cada filtro
            if (!empty($status_filter) && $estado !== $status_filter) {
                continue;
            }
            if (!empty($priority_filter) && $prioridad !== $priority_filter) {
                continue;
            }
            if (!empty($search_filter) && 
                stripos($cliente, $search_filter) === false && 
                stripos($codigo, $search_filter) === false) {
                continue;
            }

            // Si el pedido ha pasado todos los filtros, lo mostramos
            $found_posts = true;
            get_template_part('template-parts/order-row-admin', null, ghd_prepare_order_row_data($post_id));
        }

        if (!$found_posts) {
            echo '<tr><td colspan="9" style="text-align:center;">No se encontraron pedidos con esos filtros.</td></tr>';
        }

    } else {
        echo '<tr><td colspan="9" style="text-align:center;">No hay órdenes de producción.</td></tr>';
    }
    wp_reset_postdata();
    
    wp_send_json_success(array('html' => ob_get_clean()));

    // Añadimos el finalizador de AJAX
    wp_die();
}