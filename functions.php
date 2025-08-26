<?php
/**
 * functions.php - Versión Estable V1 - CORREGIDA Y FINAL
 */

// --- 1. CARGA DE ESTILOS Y SCRIPTS ---
add_action('wp_enqueue_scripts', 'ghd_enqueue_assets');
function ghd_enqueue_assets() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', false);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', array(), '6.4.2');
    wp_enqueue_style('ghd-style', get_stylesheet_uri(), array('parent-style'), '3.2');
    wp_enqueue_script('ghd-app', get_stylesheet_directory_uri() . '/js/app.js', array(), '3.2', true);

    if (is_page_template('template-admin-dashboard.php') || is_page_template('template-sector-dashboard.php') || is_singular('orden_produccion')) {
        wp_localize_script('ghd-app', 'ghd_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ghd-ajax-nonce')
        ));
    }
}

// --- 2. FUNCIONES DE AYUDA ---
function ghd_get_sectores_produccion() { return ['Carpintería', 'Costura', 'Tapicería', 'Logística']; }
function ghd_get_next_sector($sector) { $flujo = ['Carpintería' => 'Costura', 'Costura' => 'Tapicería', 'Tapicería' => 'Logística', 'Logística' => 'Completado']; return $flujo[$sector] ?? null; }

function ghd_prepare_order_row_data($post_id) {
    $estado = get_field('estado_pedido', $post_id);
    $prioridad = get_field('prioridad_pedido', $post_id);
    $prioridad_class = 'tag-green'; if ($prioridad == 'Alta') $prioridad_class = 'tag-red'; elseif ($prioridad == 'Media') $prioridad_class = 'tag-yellow';
    $estado_class = 'tag-gray'; if (in_array($estado, ghd_get_sectores_produccion())) $estado_class = 'tag-blue'; elseif ($estado == 'Completado') $estado_class = 'tag-green';
    
    return [ 'post_id' => $post_id, 'titulo' => get_the_title($post_id), 'nombre_cliente'  => get_field('nombre_cliente', $post_id), 'nombre_producto' => get_field('nombre_producto', $post_id), 'estado' => $estado, 'prioridad' => $prioridad, 'sector_actual' => get_field('sector_actual', $post_id), 'fecha_del_pedido' => get_field('fecha_del_pedido', $post_id), 'prioridad_class' => $prioridad_class, 'estado_class' => $estado_class ];
}

// --- 3. LÓGICA AJAX ---
// --- REEMPLAZA ESTA FUNCIÓN COMPLETA ---
add_action('wp_ajax_ghd_update_order', function() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('edit_posts')) { wp_send_json_error(); }

    $id = intval($_POST['order_id']);
    $field = sanitize_key($_POST['field']);
    $value = sanitize_text_field($_POST['value']);

    update_field($field, $value, $id);
    if ($field === 'sector_actual') {
        update_field('estado_pedido', $value, $id);

        // --- LÓGICA NUEVA: Crear un post de historial ---
        $historial_post_id = wp_insert_post(array(
            'post_title'   => 'Asignado a: ' . $value,
            'post_type'    => 'ghd_historial',
            'post_status'  => 'publish',
        ));
        // Vinculamos el evento de historial con la orden de producción
        if ($historial_post_id) {
            add_post_meta($historial_post_id, '_orden_produccion_id', $id);
        }
    }
    
    ob_start();
    get_template_part('template-parts/order-row-admin', null, ghd_prepare_order_row_data($id));
    wp_send_json_success(['html' => ob_get_clean()]);
});

add_action('wp_ajax_ghd_move_to_next_sector', function() {
    check_ajax_referer('ghd_move_order_nonce', 'nonce');
    if (!current_user_can('read')) { wp_send_json_error(); }

    $id = intval($_POST['order_id']);
    $next = ghd_get_next_sector(get_field('sector_actual', $id));

    if ($next) {
        update_field('sector_actual', $next, $id);
        update_field('estado_pedido', $next, $id);

        // --- LÓGICA NUEVA: Crear un post de historial ---
        $historial_post_id = wp_insert_post(array(
            'post_title'   => 'Movido a: ' . $next,
            'post_type'    => 'ghd_historial',
            'post_status'  => 'publish',
        ));
        // Vinculamos el evento de historial con la orden de producción
        if ($historial_post_id) {
            add_post_meta($historial_post_id, '_orden_produccion_id', $id);
        }

        wp_send_json_success(['message' => 'Pedido movido.']);
    } else {
        wp_send_json_error(['message' => 'No se pudo mover el pedido.']);
    }
});

// --- REEMPLAZA ESTA FUNCIÓN COMPLETA EN TU functions.php ---

add_action('wp_ajax_ghd_refresh_tasks', 'ghd_refresh_tasks_callback');
function ghd_refresh_tasks_callback() {
    // Seguridad
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('read')) {
        wp_send_json_error();
    }

    // Obtener datos del usuario actual para filtrar
    $user = wp_get_current_user();
    $role = $user->roles[0] ?? '';
    $role_to_sector_map = ['rol_carpinteria' => 'Carpintería', 'rol_costura' => 'Costura', 'rol_tapiceria' => 'Tapicería', 'rol_logistica' => 'Logística'];
    $sector = $role_to_sector_map[$role] ?? '';

    if (empty($sector)) {
        wp_send_json_error();
    }

    // Ejecutar la misma consulta que en la plantilla
    $query = new WP_Query([
        'post_type' => 'orden_produccion',
        'posts_per_page' => -1,
        'meta_query' => [['key' => 'sector_actual', 'value' => $sector]]
    ]);

    ob_start(); // Iniciar captura de HTML

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $p = get_field('prioridad_pedido');
            $pc = ($p == 'Alta') ? 'tag-red' : (($p == 'Media') ? 'tag-yellow' : 'tag-green');
            ?>
            <div class="ghd-task-card" id="order-<?php echo get_the_ID(); ?>">
                <div class="card-header">
                    <h3><?php the_title(); ?></h3>
                    <span class="ghd-tag <?php echo $pc; ?>"><?php echo esc_html($p); ?></span>
                </div>
                <div class="card-body">
                    <p><strong>Cliente:</strong> <?php echo esc_html(get_field('nombre_cliente')); ?></p>
                    <p><strong>Producto:</strong> <?php echo esc_html(get_field('nombre_producto')); ?></p>
                </div>
                <div class="card-footer">
                    <a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary">Detalles</a>
                    
                    <?php
                    // --- LÓGICA CORREGIDA Y AÑADIDA AQUÍ ---
                    // Añadimos la misma comprobación que en la plantilla principal.
                    $sector_actual_tarjeta = get_field('sector_actual', get_the_ID());
                    $sectores_permitidos_tarjeta = array('Tapicería', 'Logística');

                    if (in_array($sector_actual_tarjeta, $sectores_permitidos_tarjeta)) :
                    ?>
                        <a href="<?php echo get_stylesheet_directory_uri(); ?>/generar-remito.php?pedido_id=<?php echo get_the_ID(); ?>" 
                           class="ghd-btn ghd-btn-secondary" 
                           target="_blank">
                           Generar Remito
                        </a>
                    <?php endif; ?>

                    <button class="ghd-btn ghd-btn-primary move-to-next-sector-btn" data-order-id="<?php echo get_the_ID(); ?>" data-nonce="<?php echo wp_create_nonce('ghd_move_order_nonce'); ?>">
                        Mover
                    </button>
                </div>
            </div>
            <?php
        }
    } else {
        echo '<p>No tienes tareas asignadas.</p>';
    }
    wp_reset_postdata();

    // Enviar el HTML capturado de vuelta al navegador
    wp_send_json_success(['html' => ob_get_clean()]);
}

// --- MANEJADOR AJAX PARA FILTRAR PEDIDOS EN EL PANEL DE ADMIN ---

add_action('wp_ajax_ghd_filter_orders', 'ghd_filter_orders_callback');
function ghd_filter_orders_callback() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');

    // 1. OBTENER TODOS LOS PEDIDOS - SIN FILTROS COMPLEJOS
    // Esta es la consulta más simple y robusta posible.
    $args = array(
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
    );
    $pedidos_query = new WP_Query($args);

    // Sanitizamos los valores de los filtros una sola vez
    $status_filter   = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $priority_filter = isset($_POST['priority']) ? sanitize_text_field($_POST['priority']) : '';
    $search_filter   = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

    ob_start();

    if ($pedidos_query->have_posts()) {
        $found_posts = false;
        while ($pedidos_query->have_posts()) {
            $pedidos_query->the_post();
            $post_id = get_the_ID();

            // 2. FILTRAR LOS RESULTADOS EN PHP (MÉTODO A PRUEBA DE BALAS)
            // Obtenemos los datos de cada pedido
            $estado = get_field('estado_pedido', $post_id);
            $prioridad = get_field('prioridad_pedido', $post_id);
            $cliente = get_field('nombre_cliente', $post_id);
            $codigo = get_the_title($post_id);

            // Comprobamos si el pedido actual pasa cada filtro
            if (!empty($status_filter) && $estado !== $status_filter) {
                continue; // Si no coincide, saltamos al siguiente pedido
            }
            if (!empty($priority_filter) && $prioridad !== $priority_filter) {
                continue; // Si no coincide, saltamos al siguiente pedido
            }
            if (!empty($search_filter) && 
                stripos($cliente, $search_filter) === false && 
                stripos($codigo, $search_filter) === false) {
                continue; // Si no coincide ni en cliente ni en código, saltamos al siguiente
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
}

// --- REGISTRO DEL CUSTOM POST TYPE PARA EL HISTORIAL DE PRODUCCIÓN ---
add_action('init', 'ghd_registrar_cpt_historial');
function ghd_registrar_cpt_historial() {
    $args = array(
        'labels'        => array('name' => 'Historial de Producción'),
        'public'        => false, // No es visible en el frontend del sitio
        'publicly_queryable' => false,
        'show_ui'       => true,  // Sí lo queremos ver en el panel de admin
        'show_in_menu'  => 'edit.php?post_type=orden_produccion', // Lo anidamos bajo "Órdenes de Producción"
        'supports'      => array('title', 'editor'), // Usaremos el título para el evento y el editor para notas
        'capability_type' => 'post',
        'rewrite'       => false,
    );
    register_post_type('ghd_historial', $args);
}