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
    
    return [ 'post_id' => $post_id, 'titulo' => get_the_title($post_id), 'nombre_cliente'  => get_field('nombre_cliente', $post_id), 'nombre_producto' => get_field('nombre_producto', $post_id), 'estado' => $estado, 'prioridad' => $prioridad, 'sector_actual' => get_field('sector_actual', $post_id), 'fecha_pedido' => get_field('fecha_pedido', $post_id), 'prioridad_class' => $prioridad_class, 'estado_class' => $estado_class ];
}

// --- 3. LÓGICA AJAX ---
add_action('wp_ajax_ghd_update_order', function() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce'); if (!current_user_can('edit_posts')) wp_send_json_error();
    $id = intval($_POST['order_id']); $field = sanitize_key($_POST['field']); $value = sanitize_text_field($_POST['value']);
    update_field($field, $value, $id); if ($field === 'sector_actual') update_field('estado_pedido', $value, $id);
    ob_start(); get_template_part('template-parts/order-row-admin', null, ghd_prepare_order_row_data($id));
    wp_send_json_success(['html' => ob_get_clean()]);
});

add_action('wp_ajax_ghd_move_to_next_sector', function() {
    check_ajax_referer('ghd_move_order_nonce', 'nonce'); 
    if (!current_user_can('read')) wp_send_json_error();
    $id = intval($_POST['order_id']); $next = ghd_get_next_sector(get_field('sector_actual', $id));
    if ($next) { update_field('sector_actual', $next, $id); update_field('estado_pedido', $next, $id); wp_send_json_success(['message' => 'Pedido movido.']); } 
    else { wp_send_json_error(['message' => 'No se pudo mover el pedido.']); }
});

add_action('wp_ajax_ghd_refresh_tasks', function() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce'); if (!current_user_can('read')) wp_send_json_error();
    $user = wp_get_current_user(); $role = $user->roles[0] ?? '';
    $role_to_sector_map = ['rol_carpinteria' => 'Carpintería', 'rol_costura' => 'Costura', 'rol_tapiceria' => 'Tapicería', 'rol_logistica' => 'Logística'];
    $sector = $role_to_sector_map[$role] ?? '';
    if (empty($sector)) { wp_send_json_error(); }
    $query = new WP_Query(['post_type' => 'orden_produccion', 'posts_per_page' => -1, 'meta_query' => [['key' => 'sector_actual', 'value' => $sector]]]);
    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $p = get_field('prioridad_pedido'); $pc = ($p == 'Alta') ? 'tag-red' : (($p == 'Media') ? 'tag-yellow' : 'tag-green');
            ?>
            <div class="ghd-task-card" id="order-<?php echo get_the_ID(); ?>">
                <div class="card-header"><h3><?php the_title(); ?></h3><span class="ghd-tag <?php echo $pc; ?>"><?php echo esc_html($p); ?></span></div>
                <div class="card-body"><p><strong>Cliente:</strong> <?php echo esc_html(get_field('nombre_cliente')); ?></p><p><strong>Producto:</strong> <?php echo esc_html(get_field('nombre_producto')); ?></p></div>
                <div class="card-footer">
                    <a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary">Detalles</a>
                    <button class="ghd-btn ghd-btn-primary move-to-next-sector-btn" data-order-id="<?php echo get_the_ID(); ?>" data-nonce="<?php echo wp_create_nonce('ghd_move_order_nonce'); ?>">Mover</button>
                </div>
            </div>
            <?php
        }
    } else { echo '<p>No tienes tareas asignadas.</p>'; }
    wp_reset_postdata();
    wp_send_json_success(['html' => ob_get_clean()]);
});

add_action('wp_ajax_ghd_filter_orders', function() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');

    $args = [
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => ['relation' => 'AND'],
    ];

    if (!empty($_POST['status'])) { $args['meta_query'][] = ['key' => 'estado_pedido', 'value' => sanitize_text_field($_POST['status'])]; }
    if (!empty($_POST['priority'])) { $args['meta_query'][] = ['key' => 'prioridad_pedido', 'value' => sanitize_text_field($_POST['priority'])]; }
    
    if (!empty($_POST['search'])) {
        $search_term = sanitize_text_field($_POST['search']);
        $search_query = [
            'relation' => 'OR',
            ['key' => 'nombre_cliente', 'value' => $search_term, 'compare' => 'LIKE'],
        ];
        $title_query_args = ['post_type' => 'orden_produccion', 'posts_per_page' => -1, 's' => $search_term, 'fields' => 'ids'];
        $title_query = new WP_Query($title_query_args);
        $title_post_ids = $title_query->posts;
        if (!empty($title_post_ids)) { $search_query[] = ['key' => 'ID', 'value' => $title_post_ids, 'compare' => 'IN']; }
        $args['meta_query'][] = $search_query;
    }

    add_filter('posts_join', 'ghd_filter_orders_posts_join');
    add_filter('posts_where', 'ghd_filter_orders_posts_where');

    $pedidos_query = new WP_Query($args);
    
    remove_filter('posts_join', 'ghd_filter_orders_posts_join');
    remove_filter('posts_where', 'ghd_filter_orders_posts_where');
    
    ob_start();
    if ($pedidos_query->have_posts()) {
        while ($pedidos_query->have_posts()) {
            $pedidos_query->the_post();
            get_template_part('template-parts/order-row-admin', null, ghd_prepare_order_row_data(get_the_ID()));
        }
    } else { echo '<tr><td colspan="9" style="text-align:center;">No se encontraron pedidos.</td></tr>'; }
    wp_reset_postdata();
    wp_send_json_success(['html' => ob_get_clean()]);

    // ¡¡¡CORRECCIÓN CLAVE!!! Siempre finalizamos la ejecución después de una petición AJAX.
    exit; 
});

// Funciones de filtro auxiliares para la búsqueda (se mantienen separadas para poder quitarlas)
function ghd_filter_orders_posts_join($join) {
    global $wpdb;
    if (isset($query->query_vars['meta_query'])) { // Evita errores si no hay meta_query
        $join .= " LEFT JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id ";
    }
    return $join;
}
function ghd_filter_orders_posts_where($where) {
    global $wpdb;
    // Esto es un parche para la búsqueda en el título cuando se usa 'post__in' a través de meta_query
    // Reemplaza la condición de meta_key='ID' por una búsqueda directa en posts.ID
    $where = str_replace("(`{$wpdb->postmeta}`.`meta_key` = 'ID' AND `{$wpdb->postmeta}`.`meta_value` IN", "(`{$wpdb->posts}`.`ID` IN", $where);
    return $where;
}