<?php
/**
 * functions.php - Versión 2.8 - Consolidación Administrativa
 */

// --- 1. CARGA DE ESTILOS Y SCRIPTS ---
add_action('wp_enqueue_scripts', 'ghd_enqueue_assets');
function ghd_enqueue_assets() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', false);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', [], '6.4.2');
    wp_enqueue_style('ghd-style', get_stylesheet_uri(), ['parent-style'], '2.8'); // Versión actualizada
    wp_enqueue_script('ghd-app', get_stylesheet_directory_uri() . '/js/app.js', [], '2.8', true); // Versión actualizada
    wp_localize_script('ghd-app', 'ghd_ajax', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ghd-ajax-nonce')]);
}

// --- 2. REGISTRO DE CUSTOM POST TYPES ---
add_action('init', 'ghd_registrar_cpt_historial');
function ghd_registrar_cpt_historial() {
    register_post_type('ghd_historial', ['labels' => ['name' => 'Historial de Producción'], 'public' => false, 'show_ui' => true, 'show_in_menu' => 'edit.php?post_type=orden_produccion', 'supports' => ['title']]);
}

// --- 3. FUNCIONES DE AYUDA ---
function ghd_get_sectores() { return ['Carpintería', 'Corte', 'Costura', 'Tapicería', 'Embalaje', 'Logística']; } // 'Administrativo' ya no es un sector de tarea activa en este flujo
function ghd_get_mapa_roles_a_campos() {
    // 'rol_administrativo' ya no es un rol de sector activo para la interfaz de tareas
    return [
        'rol_carpinteria' => 'estado_carpinteria', 
        'rol_corte' => 'estado_corte', 
        'rol_costura' => 'estado_costura', 
        'rol_tapiceria' => 'estado_tapiceria', 
        'rol_embalaje' => 'estado_embalaje', 
        'rol_logistica' => 'estado_logistica'
    ];
}

/**
 * Función para calcular los KPIs de un sector dado un campo de estado.
 * Reutilizable para la carga inicial y las respuestas AJAX.
 * @param string $campo_estado El nombre del campo ACF (ej. 'estado_corte').
 * @return array Un array con 'total_pedidos', 'total_prioridad_alta', 'tiempo_promedio_str', 'completadas_hoy'.
 */
function ghd_calculate_sector_kpis($campo_estado) {
    // Pedidos activos (Pendientes o En Progreso para el campo_estado dado)
    $pedidos_args = [
        'post_type'      => 'orden_produccion', 
        'posts_per_page' => -1, 
        'meta_query'     => [
            [
                'key'     => $campo_estado, 
                'value'   => ['Pendiente', 'En Progreso'], 
                'compare' => 'IN'
            ]
        ]
    ];
    $pedidos_query = new WP_Query($pedidos_args);
    
    $total_pedidos = $pedidos_query->post_count;
    $total_prioridad_alta = 0; 
    $total_tiempo_espera = 0; 
    $ahora = current_time('U');

    if ($pedidos_query->have_posts()) {
        foreach ($pedidos_query->posts as $pedido) {
            if (get_field('prioridad_pedido', $pedido->ID) === 'Alta') { 
                $total_prioridad_alta++; 
            }
            $total_tiempo_espera += $ahora - get_the_modified_time('U', $pedido->ID);
        }
    }
    
    $tiempo_promedio_str = '0.0h';
    if ($total_pedidos > 0) {
        $promedio_horas = ($total_tiempo_espera / $total_pedidos) / 3600;
        $tiempo_promedio_str = number_format($promedio_horas, 1) . 'h';
    }

    // Pedidos completados hoy para el campo_estado dado
    $completadas_hoy = 0;
    $today_start = strtotime('today', current_time('timestamp', true));
    $today_end   = strtotime('tomorrow - 1 second', current_time('timestamp', true));

    $completadas_hoy_args = [
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => $campo_estado,
                'value'   => 'Completado', // Buscar pedidos con este campo marcado como "Completado"
                'compare' => '=',
            ],
        ],
        'date_query' => [ 
            'after'     => date('Y-m-d H:i:s', $today_start),
            'before'    => date('Y-m-d H:i:s', $today_end),
            'inclusive' => true,
            'column'    => 'post_modified_gmt', // Se asume que 'post_modified_gmt' se actualiza al cambiar el campo
        ],
    ];
    $completadas_hoy_query = new WP_Query($completadas_hoy_args);
    $completadas_hoy = $completadas_hoy_query->post_count;

    return [
        'total_pedidos'        => $total_pedidos,
        'total_prioridad_alta' => $total_prioridad_alta,
        'tiempo_promedio_str'  => $tiempo_promedio_str,
        'completadas_hoy'      => $completadas_hoy,
    ];
}

/**
 * Función para calcular los KPIs para la sección de Pedidos Pendientes de Cierre del Admin principal.
 * @return array Un array con 'total_pedidos_cierre', 'total_prioridad_alta_cierre', 'tiempo_promedio_str_cierre', 'completadas_hoy_cierre'.
 */
function ghd_calculate_admin_closure_kpis() {
    // Pedidos pendientes de cierre para el Admin (nuevo estado 'Pendiente de Cierre Admin')
    $kpi_args = [
        'post_type' => 'orden_produccion', 'posts_per_page' => -1,
        'meta_query' => [['key' => 'estado_pedido', 'value' => 'Pendiente de Cierre Admin', 'compare' => '=']]
    ];
    $kpi_query = new WP_Query($kpi_args);
    
    $kpi_data = [
        'total_pedidos_cierre' => $kpi_query->post_count,
        'total_prioridad_alta_cierre' => 0,
        'tiempo_promedio_str_cierre' => '0.0h',
        'completadas_hoy_cierre' => 0 
    ];
    
    $total_tiempo_espera = 0;
    $ahora = current_time('U');
    if ($kpi_query->have_posts()) {
        foreach ($kpi_query->posts as $pedido) {
            if (get_field('prioridad_pedido', $pedido->ID) === 'Alta') { $kpi_data['total_prioridad_alta_cierre']++; }
            // Calcular el tiempo de espera desde que el pedido entró en este estado (fecha de modificación)
            $total_tiempo_espera += $ahora - get_the_modified_time('U', $pedido->ID);
        }
    }
    if ($kpi_data['total_pedidos_cierre'] > 0) {
        $promedio_horas = ($total_tiempo_espera / $kpi_data['total_pedidos_cierre']) / 3600;
        $kpi_data['tiempo_promedio_str_cierre'] = number_format($promedio_horas, 1) . 'h';
    }

    // --- Lógica para calcular 'Completadas Hoy' (pedidos archivados hoy por el Admin) ---
    $today_start = strtotime('today', current_time('timestamp', true));
    $today_end   = strtotime('tomorrow - 1 second', current_time('timestamp', true));

    $completadas_hoy_args = [
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => 'estado_pedido',
                'value'   => 'Completado y Archivado', // Buscamos este estado final
                'compare' => '=',
            ],
        ],
        'date_query' => [ 
            'after'     => date('Y-m-d H:i:s', $today_start),
            'before'    => date('Y-m-d H:i:s', $today_end),
            'inclusive' => true,
            'column'    => 'post_modified_gmt',
        ],
    ];
    $completadas_hoy_query = new WP_Query($completadas_hoy_args);
    $kpi_data['completadas_hoy_cierre'] = $completadas_hoy_query->post_count;

    return $kpi_data;
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
            // Para otros roles (incluyendo 'rol_administrativo' si existe pero sin panel dedicado), 
            // siempre se redirige al template-sector-dashboard.php.
            // Si el rol_administrativo no tiene un campo_estado mapeado, verá "No tienes tareas pendientes."
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


// --- 5. LÓGICA AJAX ---
add_action('wp_ajax_ghd_admin_action', function() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce'); 
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'No tienes permisos para editar posts.']);

    $id = intval($_POST['order_id']); 
    $type = sanitize_key($_POST['type']);

    if ($type === 'start_production') {
        // Asegúrate de que el pedido exista y esté en el estado correcto antes de iniciar
        if (get_post_type($id) !== 'orden_produccion') {
            wp_send_json_error(['message' => 'ID de pedido no válido.']);
        }
        if (get_field('estado_pedido', $id) !== 'Pendiente de Asignación') {
            wp_send_json_error(['message' => 'El pedido no está pendiente de asignación.']);
        }

        update_field('estado_carpinteria', 'Pendiente', $id);
        update_field('estado_corte', 'Pendiente', $id);
        update_field('estado_pedido', 'En Producción', $id); // Actualiza el estado general del pedido
        
        // Registrar en historial
        wp_insert_post([
            'post_title' => 'Producción Iniciada para ' . get_the_title($id),
            'post_type' => 'ghd_historial',
            'meta_input' => ['_orden_produccion_id' => $id]
        ]);
        
        wp_send_json_success(['message' => 'Producción iniciada con éxito.']);
    }
    
    wp_send_json_error(['message' => 'Acción no reconocida.']);
});

add_action('wp_ajax_ghd_update_task_status', function() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('read')) wp_send_json_error();

    $id = intval($_POST['order_id']);
    $field = sanitize_key($_POST['field']); // campo_estado (ej. estado_corte)
    $value = sanitize_text_field($_POST['value']); // nuevo valor (ej. En Progreso, Completado)
    
    update_field($field, $value, $id);
    wp_insert_post(['post_title' => ucfirst(str_replace(['estado_', '_'], ' ', $field)) . ' -> ' . $value, 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
    
    // Recalcular KPIs del sector DESPUÉS de actualizar el campo
    $sector_kpi_data = ghd_calculate_sector_kpis($field); // <-- Calculamos los KPIs del sector
    
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
        // Regla 4: Logística -> Pendiente de Cierre Admin (Consolidación)
        // Eliminamos el 'estado_administrativo = Pendiente' como tarea para un rol específico
        // Ahora el Admin principal lo manejará.
        if (get_field('estado_logistica', $id) == 'Completado' && get_field('estado_pedido', $id) !== 'Pendiente de Cierre Admin') { // Aseguramos no sobreescribir si ya está en ese estado
            update_field('estado_pedido', 'Pendiente de Cierre Admin', $id); // Nuevo estado general
            update_field('estado_administrativo', 'Listo para Archivar', $id); // Para seguimiento interno, ya no como una "tarea"
            wp_insert_post(['post_title' => 'Entrega Completada -> Pendiente de Cierre Admin', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
        }

        wp_send_json_success(['message' => 'Tarea completada.', 'kpi_data' => $sector_kpi_data]); // <-- Devolvemos los KPIs
    } else {
        // Si no se completó, devolvemos el HTML de la tarjeta actualizada y los KPIs.
        ob_start();

        $prioridad_pedido = get_field('prioridad_pedido', $id);
        $prioridad_class = '';
        if ($prioridad_pedido === 'Alta') {
            $prioridad_class = 'prioridad-alta';
        } elseif ($prioridad_pedido === 'Media') {
            $prioridad_class = 'prioridad-media';
        } else {
            $prioridad_class = 'prioridad-baja';
        }

        $task_card_args = [
            'post_id'         => $id,
            'titulo'          => get_the_title($id),
            'prioridad_class' => $prioridad_class,
            'prioridad'       => $prioridad_pedido,
            'nombre_cliente'  => get_field('nombre_cliente', $id),
            'nombre_producto' => get_field('nombre_producto', $id),
            'permalink'       => get_permalink($id),
            'campo_estado'    => $field, 
            'estado_actual'   => $value, 
        ];
        
        get_template_part('template-parts/task-card', null, $task_card_args);
        $html = ob_get_clean();
        wp_send_json_success(['message' => 'Estado actualizado.', 'html' => $html, 'kpi_data' => $sector_kpi_data]); // <-- Devolvemos los KPIs
    }
    wp_die();
});

// --- MANEJADOR AJAX PARA ARCHIVAR PEDIDOS (AHORA LLAMADO POR EL ADMIN PRINCIPAL) ---
add_action('wp_ajax_ghd_archive_order', 'ghd_archive_order_callback');
function ghd_archive_order_callback() {
    // 1. Seguridad: Verificar nonce y permisos
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    // Ahora, solo el Admin principal puede archivar
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos para archivar pedidos.']);
    }
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

// --- NUEVOS MANEJADORES AJAX PARA REFRESCO ---

/**
 * AJAX Handler para refrescar las tareas y KPIs de un sector específico.
 */
add_action('wp_ajax_ghd_refresh_sector_tasks', 'ghd_refresh_sector_tasks_callback');
function ghd_refresh_sector_tasks_callback() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('read')) wp_send_json_error(['message' => 'No tienes permisos.']);

    $campo_estado = sanitize_key($_POST['campo_estado']);
    if (empty($campo_estado)) {
        wp_send_json_error(['message' => 'Campo de estado no proporcionado.']);
    }

    // 1. Recalcular KPIs para el sector
    $sector_kpi_data = ghd_calculate_sector_kpis($campo_estado);

    // 2. Regenerar el HTML de las tareas
    ob_start();
    $pedidos_query = new WP_Query([
        'post_type'      => 'orden_produccion', 
        'posts_per_page' => -1, 
        'meta_query'     => [
            [
                'key'     => $campo_estado, 
                'value'   => ['Pendiente', 'En Progreso'], 
                'compare' => 'IN'
            ]
        ]
    ]);

    if ($pedidos_query->have_posts()) : 
        while ($pedidos_query->have_posts()) : $pedidos_query->the_post();
            $current_order_id = get_the_ID();
            $current_status = get_field($campo_estado, $current_order_id);
            $prioridad_pedido = get_field('prioridad_pedido', $current_order_id);
            $prioridad_class = '';
            if ($prioridad_pedido === 'Alta') {
                $prioridad_class = 'prioridad-alta';
            } elseif ($prioridad_pedido === 'Media') {
                $prioridad_class = 'prioridad-media';
            } else {
                $prioridad_class = 'prioridad-baja';
            }

            $task_card_args = [
                'post_id'         => $current_order_id,
                'titulo'          => get_the_title($current_order_id),
                'prioridad_class' => $prioridad_class,
                'prioridad'       => $prioridad_pedido,
                'nombre_cliente'  => get_field('nombre_cliente', $current_order_id),
                'nombre_producto' => get_field('nombre_producto', $current_order_id),
                'permalink'       => get_permalink($current_order_id),
                'campo_estado'    => $campo_estado,
                'estado_actual'   => $current_status,
            ];
            get_template_part('template-parts/task-card', null, $task_card_args);
        endwhile;
    else: ?>
        <p class="no-tasks-message">No tienes tareas pendientes.</p>
    <?php endif; wp_reset_postdata(); 
    $tasks_html = ob_get_clean();

    wp_send_json_success([
        'message'  => 'Tareas de sector actualizadas.',
        'tasks_html' => $tasks_html,
        'kpi_data' => $sector_kpi_data
    ]);
    wp_die();
}

/**
 * AJAX Handler para refrescar la sección de Pedidos Pendientes de Cierre y sus KPIs del Admin principal.
 */
add_action('wp_ajax_ghd_refresh_admin_closure_section', 'ghd_refresh_admin_closure_section_callback');
function ghd_refresh_admin_closure_section_callback() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'No tienes permisos.']);

    // 1. Recalcular KPIs para la sección de cierre
    $admin_closure_kpis = ghd_calculate_admin_closure_kpis();

    // 2. Regenerar el HTML de la tabla de cierre
    ob_start();
    $args_cierre = array(
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'estado_pedido',
                'value'   => 'Pendiente de Cierre Admin',
                'compare' => '=',
            ),
        ),
        'orderby' => 'date',
        'order'   => 'ASC',
    );
    $pedidos_cierre_query = new WP_Query($args_cierre);

    if ($pedidos_cierre_query->have_posts()) :
        while ($pedidos_cierre_query->have_posts()) : $pedidos_cierre_query->the_post();
        ?>
            <tr id="order-row-closure-<?php echo get_the_ID(); ?>">
                <td><a href="<?php the_permalink(); ?>" style="color: var(--color-rojo); font-weight: 600;"><?php the_title(); ?></a></td>
                <td><?php echo esc_html(get_field('nombre_cliente')); ?></td>
                <td><?php echo esc_html(get_field('nombre_producto')); ?></td>
                <td><?php echo get_the_date(); ?></td>
                <td>
                    <button class="ghd-btn ghd-btn-primary archive-order-btn" data-order-id="<?php echo get_the_ID(); ?>">
                        Archivar Pedido
                    </button>
                </td>
            </tr>
        <?php
        endwhile;
    else: 
    ?>
        <tr><td colspan="5" style="text-align:center;">No hay pedidos pendientes de cierre.</td></tr>
    <?php
    endif;
    wp_reset_postdata(); 
    $closure_table_html = ob_get_clean();

    wp_send_json_success([
        'message'          => 'Sección de cierre actualizada.',
        'table_html'       => $closure_table_html,
        'kpi_data'         => $admin_closure_kpis
    ]);
    wp_die();
}

/**
 * Función para obtener los datos de pedidos en producción y sus KPIs.
 * Reutilizable para la carga inicial y las respuestas AJAX.
 * @return array Un array con 'pedidos_data' (HTML) y 'kpi_data'.
 */
function ghd_get_pedidos_en_produccion_data() {
    $pedidos_args = [
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => 'estado_pedido',
                'value'   => ['En Producción', 'En Costura', 'En Tapicería/Embalaje', 'Listo para Entrega', 'Despachado'], // Todos los estados intermedios
                'compare' => 'IN',
            ],
        ],
        'orderby' => ['prioridad_pedido' => 'ASC', 'date' => 'ASC'],
    ];
    $pedidos_query = new WP_Query($pedidos_args);

    $kpi_data = [
        'total_pedidos_produccion'        => $pedidos_query->post_count,
        'total_prioridad_alta_produccion' => 0,
        'tiempo_promedio_str_produccion'  => '0.0h', // Tiempo promedio desde inicio de producción
        'completadas_hoy_produccion'      => 0, // Pedidos que salieron de producción hoy
    ];

    $total_tiempo_produccion = 0;
    $ahora = current_time('U');
    $mapa_roles_a_campos = ghd_get_mapa_roles_a_campos(); // Usar el mapeo de campos

    ob_start();
    if ($pedidos_query->have_posts()) :
        while ($pedidos_query->have_posts()) : $pedidos_query->the_post();
            $order_id = get_the_ID();
            $prioridad = get_field('prioridad_pedido', $order_id);
            if ($prioridad === 'Alta') {
                $kpi_data['total_prioridad_alta_produccion']++;
            }

            // Calcular tiempo de producción desde que pasó a "En Producción"
            $produccion_iniciada_time = get_post_meta($order_id, 'historial_produccion_iniciada_timestamp', true); // Se necesita guardar este timestamp

            if ($produccion_iniciada_time) {
                $total_tiempo_produccion += $ahora - $produccion_iniciada_time;
            } else {
                $total_tiempo_produccion += $ahora - get_the_modified_time('U', $order_id); // Fallback
            }
            ?>
            <tr id="order-row-prod-<?php echo $order_id; ?>">
                <td><a href="<?php the_permalink(); ?>" style="color: var(--color-rojo); font-weight: 600;"><?php the_title(); ?></a></td>
                <td><?php echo esc_html(get_field('nombre_cliente', $order_id)); ?></td>
                <td><?php echo esc_html(get_field('nombre_producto', $order_id)); ?></td>
                <td><?php echo esc_html(get_field('estado_pedido', $order_id)); ?></td>
                <td>
                    <div class="production-substatus-badges">
                        <?php
                        foreach ($mapa_roles_a_campos as $role_key => $field_key) {
                            $sub_estado = get_field($field_key, $order_id);
                            $badge_class = '';
                            if ($sub_estado === 'Completado') {
                                $badge_class = 'status-green';
                            } elseif ($sub_estado === 'En Progreso') {
                                $badge_class = 'status-yellow';
                            } elseif ($sub_estado === 'Pendiente') {
                                $badge_class = 'status-blue';
                            } else { // No Asignado
                                $badge_class = 'status-gray';
                            }
                            ?>
                            <span class="ghd-badge <?php echo esc_attr($badge_class); ?>">
                                <?php echo ucfirst(str_replace('estado_', '', $field_key)); ?>: <?php echo esc_html($sub_estado); ?>
                            </span>
                            <?php
                        }
                        ?>
                    </div>
                </td>
                <td><a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary ghd-btn-small">Ver</a></td>
            </tr>
            <?php
        endwhile;
    else : ?>
        <tr><td colspan="6" style="text-align:center;">No hay pedidos actualmente en producción.</td></tr>
    <?php endif;
    wp_reset_postdata();
    $production_tasks_html = ob_get_clean();

    if ($kpi_data['total_pedidos_produccion'] > 0) {
        $promedio_horas = ($total_tiempo_produccion / $kpi_data['total_pedidos_produccion']) / 3600;
        $kpi_data['tiempo_promedio_str_produccion'] = number_format($promedio_horas, 1) . 'h';
    }

    // Completadas hoy (salieron de producción hoy - ej. pasaron a 'Pendiente de Cierre Admin')
    $today_start = strtotime('today', current_time('timestamp', true));
    $today_end   = strtotime('tomorrow - 1 second', current_time('timestamp', true));

    $completed_production_today_args = [
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => 'estado_pedido',
                'value'   => 'Pendiente de Cierre Admin', // O cualquier otro estado que indique que salió de producción
                'compare' => '=',
            ],
        ],
        'date_query' => [
            'after'     => date('Y-m-d H:i:s', $today_start),
            'before'    => date('Y-m-d H:i:s', $today_end),
            'inclusive' => true,
            'column'    => 'post_modified_gmt',
        ],
    ];
    $completed_production_today_query = new WP_Query($completed_production_today_args);
    $kpi_data['completadas_hoy_produccion'] = $completed_production_today_query->post_count;

    return ['tasks_html' => $production_tasks_html, 'kpi_data' => $kpi_data];
}

/**
 * AJAX Handler para refrescar la sección de Pedidos en Producción y sus KPIs del Admin principal.
 */
add_action('wp_ajax_ghd_refresh_production_tasks', 'ghd_refresh_production_tasks_callback');
function ghd_refresh_production_tasks_callback() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'No tienes permisos.']);

    $data = ghd_get_pedidos_en_produccion_data();

    wp_send_json_success([
        'message'  => 'Pedidos en producción actualizados.',
        'tasks_html' => $data['tasks_html'],
        'kpi_data' => $data['kpi_data']
    ]);
    wp_die();
}

// También, necesitamos asegurarnos de que el timestamp de "Producción Iniciada" se guarde.
// Modificamos ghd_admin_action para esto.
add_action('wp_ajax_ghd_admin_action', function() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce'); 
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'No tienes permisos para editar posts.']);

    $id = intval($_POST['order_id']); 
    $type = sanitize_key($_POST['type']);

    if ($type === 'start_production') {
        if (get_post_type($id) !== 'orden_produccion') {
            wp_send_json_error(['message' => 'ID de pedido no válido.']);
        }
        if (get_field('estado_pedido', $id) !== 'Pendiente de Asignación') {
            wp_send_json_error(['message' => 'El pedido no está pendiente de asignación.']);
        }

        update_field('estado_carpinteria', 'Pendiente', $id);
        update_field('estado_corte', 'Pendiente', $id);
        update_field('estado_pedido', 'En Producción', $id);
        update_post_meta($id, 'historial_produccion_iniciada_timestamp', current_time('U')); // <-- Guardamos el timestamp

        wp_insert_post([
            'post_title' => 'Producción Iniciada para ' . get_the_title($id),
            'post_type' => 'ghd_historial',
            'meta_input' => ['_orden_produccion_id' => $id]
        ]);
        
        wp_send_json_success(['message' => 'Producción iniciada con éxito.']);
    }
    
    wp_send_json_error(['message' => 'Acción no reconocida.']);
});