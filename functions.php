<?php
/**
 * functions.php - Versión 3.3 
 */

// --- 1. CARGA DE ESTILOS Y SCRIPTS ---
add_action('wp_enqueue_scripts', 'ghd_enqueue_assets');
function ghd_enqueue_assets() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', false);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', [], '6.4.2');
    wp_enqueue_style('ghd-style', get_stylesheet_uri(), ['parent-style'], '3.3'); // Versión actualizada
    wp_enqueue_script('ghd-app', get_stylesheet_directory_uri() . '/js/app.js', [], '3.3', true); // Versión actualizada

    if (!is_admin()) { // Solo localizar scripts en el frontend
        wp_localize_script('ghd-app', 'ghd_ajax', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ghd-ajax-nonce')]);
        
        global $post;
        if (is_a($post, 'WP_Post') && is_page_template('template-reportes.php')) {
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', [], '4.4.1', true);
            wp_localize_script('ghd-app', 'ghd_reports_data', ghd_get_reports_data());
        }
    }
}

// --- 2. REGISTRO DE CUSTOM POST TYPES ---
add_action('init', 'ghd_registrar_cpt_historial');
function ghd_registrar_cpt_historial() {
    register_post_type('ghd_historial', ['labels' => ['name' => 'Historial de Producción'], 'public' => false, 'show_ui' => true, 'show_in_menu' => 'edit.php?post_type=orden_produccion', 'supports' => ['title']]);
}

// --- NUEVO: REGISTRO DE CUSTOM POST TYPE PARA MODELOS DE PUNTOS ---
add_action('init', 'ghd_registrar_cpt_modelo_puntos');
function ghd_registrar_cpt_modelo_puntos() {
    $labels = [
        'name'                  => _x('Modelos de Puntos', 'Post Type General Name', 'textdomain'),
        'singular_name'         => _x('Modelo de Puntos', 'Post Type Singular Name', 'textdomain'),
        'menu_name'             => __('Puntos por Modelo', 'textdomain'),
        'name_admin_bar'        => __('Modelo de Puntos', 'textdomain'),
        'archives'              => __('Archivo de Modelos', 'textdomain'),
        'attributes'            => __('Atributos del Modelo', 'textdomain'),
        'parent_item_colon'     => __('Modelo Padre:', 'textdomain'),
        'all_items'             => __('Todos los Modelos', 'textdomain'),
        'add_new_item'          => __('Añadir Nuevo Modelo', 'textdomain'),
        'add_new'               => __('Añadir Nuevo', 'textdomain'),
        'new_item'              => __('Nuevo Modelo', 'textdomain'),
        'edit_item'             => __('Editar Modelo', 'textdomain'),
        'update_item'           => __('Actualizar Modelo', 'textdomain'),
        'view_item'             => __('Ver Modelo', 'textdomain'),
        'view_items'            => __('Ver Modelos', 'textdomain'),
        'search_items'          => __('Buscar Modelo', 'textdomain'),
        'not_found'             => __('No encontrado', 'textdomain'),
        'not_found_in_trash'    => __('No encontrado en la Papelera', 'textdomain'),
        'featured_image'        => __('Imagen Destacada', 'textdomain'),
        'set_featured_image'    => __('Establecer Imagen Destacada', 'textdomain'),
        'remove_featured_image' => __('Remover Imagen Destacada', 'textdomain'),
        'use_featured_image'    => __('Usar como Imagen Destacada', 'textdomain'),
        'insert_into_item'      => __('Insertar en Modelo', 'textdomain'),
        'uploaded_to_this_item' => __('Subido a este Modelo', 'textdomain'),
        'items_list'            => __('Lista de Modelos', 'textdomain'),
        'items_list_navigation' => __('Navegación de lista de Modelos', 'textdomain'),
        'filter_items_list'     => __('Filtrar lista de Modelos', 'textdomain'),
    ];
    $args = [
        'label'                 => __('Modelo de Puntos', 'textdomain'),
        'description'           => __('Modelos de productos y sus puntos asociados para el sistema de embalaje.', 'textdomain'),
        'labels'                => $labels,
        'supports'              => ['title'], // Solo necesitamos el título para el nombre del modelo
        'hierarchical'          => false,
        'public'                => false, // No visible en el frontend públicamente
        'show_ui'               => true, // Visible en el admin
        'show_in_menu'          => 'edit.php?post_type=orden_produccion', // Aparecerá bajo "Órdenes de Producción"
        'menu_position'         => 25,
        'show_in_admin_bar'     => false,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'capability_type'       => 'post',
        'map_meta_cap'          => true, // Permite usar capacidades estándar como 'edit_posts'
    ];
    register_post_type('ghd_modelo_puntos', $args);
}
// --- FIN CPT MODELOS DE PUNTOS ---

// --- NUEVO: REGISTRO DE ROLES DE USUARIO PERSONALIZADOS ---
add_action('after_setup_theme', 'ghd_register_custom_roles');
function ghd_register_custom_roles() {
    // Eliminar roles si existen para redefinirlos o por limpieza durante el desarrollo
    remove_role('vendedora');
    remove_role('gerente_ventas');
    remove_role('lider_corte'); remove_role('operario_corte');
    remove_role('lider_carpinteria'); remove_role('operario_carpinteria');
    remove_role('lider_costura'); remove_role('operario_costura');
    remove_role('lider_tapiceria'); remove_role('operario_tapiceria');
    remove_role('lider_embalaje'); remove_role('operario_embalaje');
    remove_role('lider_logistica'); remove_role('operario_logistica'); // Fleteros
    remove_role('control_final_macarena');

    // Obtener capacidades básicas para construir nuevos roles
    $subscriber_caps = get_role('subscriber') ? get_role('subscriber')->capabilities : [];
    $contributor_caps = get_role('contributor') ? get_role('contributor')->capabilities : [];
    $editor_caps = get_role('editor') ? get_role('editor')->capabilities : [];
    
    // Capacidad base para ver contenido frontend y acceder a AJAX (ghd_view_frontend es clave para nuestra app)
    $base_custom_caps = array_merge($subscriber_caps, [
        'read' => true,
        'ghd_view_frontend' => true, // Acceso general a la aplicación frontend
        // 'edit_posts' => true, // Puede ser necesario para ACF update_field en su propio contexto si no tienen un editor_cap
    ]);

    // ROLES DE VENDEDORAS
    add_role('vendedora', 'Vendedora', array_merge($base_custom_caps, [
        'ghd_view_sales' => true, // Puede ver todas las ventas (solo lectura)
        'ghd_view_own_sales' => true, // Puede ver sus propias ventas
    ]));
    add_role('gerente_ventas', 'Gerente de Ventas (Carolina)', array_merge($base_custom_caps, [
        'ghd_view_sales' => true, // Puede ver todas las ventas
        'ghd_manage_commissions' => true, // Puede configurar comisiones y premios
        'ghd_assign_alternate_manager' => true, // Puede asignar gerente alternativo
        'edit_others_posts' => true, // Si necesita editar detalles de pedidos de otras vendedoras
    ]));

    // ROLES DE SECTORES (Líderes y Operarios)
    // CAPACIDADES DE LÍDER: ver_todas_tareas, asignar_tareas
    // CAPACIDADES DE OPERARIO: ver_tareas_asignadas
    
    add_role('lider_corte', 'Líder de Corte', array_merge($base_custom_caps, [
        'ghd_view_corte' => true, 'ghd_assign_task_corte' => true, 'ghd_view_all_corte_tasks' => true,
    ]));
    add_role('operario_corte', 'Operario de Corte', array_merge($base_custom_caps, [
        'ghd_view_corte' => true, 'ghd_view_own_corte_tasks' => true,
    ]));

    add_role('lider_carpinteria', 'Líder de Carpintería', array_merge($base_custom_caps, [
        'ghd_view_carpinteria' => true, 'ghd_assign_task_carpinteria' => true, 'ghd_view_all_carpinteria_tasks' => true,
    ]));
    add_role('operario_carpinteria', 'Operario de Carpintería', array_merge($base_custom_caps, [
        'ghd_view_carpinteria' => true, 'ghd_view_own_carpinteria_tasks' => true,
    ]));

    add_role('lider_costura', 'Líder de Costura', array_merge($base_custom_caps, [
        'ghd_view_costura' => true, 'ghd_assign_task_costura' => true, 'ghd_view_all_costura_tasks' => true,
    ]));
    add_role('operario_costura', 'Operario de Costura', array_merge($base_custom_caps, [
        'ghd_view_costura' => true, 'ghd_view_own_costura_tasks' => true,
    ]));

    add_role('lider_tapiceria', 'Líder de Tapicería', array_merge($base_custom_caps, [
        'ghd_view_tapiceria' => true, 'ghd_assign_task_tapiceria' => true, 'ghd_view_all_tapiceria_tasks' => true,
    ]));
    add_role('operario_tapiceria', 'Operario de Tapicería', array_merge($base_custom_caps, [
        'ghd_view_tapiceria' => true, 'ghd_view_own_tapiceria_tasks' => true,
    ]));

    add_role('lider_embalaje', 'Líder de Embalaje', array_merge($base_custom_caps, [
        'ghd_view_embalaje' => true, 'ghd_assign_task_embalaje' => true, 'ghd_view_all_embalaje_tasks' => true,
        'ghd_register_embalaje_points' => true, // Puede registrar puntos de operarios
    ]));
    add_role('operario_embalaje', 'Operario de Embalaje', array_merge($base_custom_caps, [
        'ghd_view_embalaje' => true, 'ghd_view_own_embalaje_tasks' => true, 'ghd_register_own_embalaje' => true, // Puede registrar su propio embalaje
    ]));

    add_role('lider_logistica', 'Líder de Logística', array_merge($base_custom_caps, [
        'ghd_view_logistica' => true, 'ghd_assign_task_logistica' => true, 'ghd_view_all_logistica_tasks' => true,
    ]));
    add_role('operario_logistica', 'Operario de Logística (Fletero)', array_merge($base_custom_caps, [
        'ghd_view_logistica' => true, 'ghd_manage_own_delivery' => true, // Puede gestionar sus propias entregas (Recogido, Entregado, etc.)
    ]));

    add_role('control_final_macarena', 'Control Final (Macarena)', array_merge($editor_caps, [ // Macarena necesita más capacidades
        'ghd_view_control_final' => true,
        'ghd_upload_remito_photo' => true, // Puede subir foto de remito
        'ghd_check_payments' => true, // Puede chequear pagos
        'ghd_archive_orders' => true, // Puede archivar pedidos
    ]));
    
    // Asignar capacidad de acceso a la aplicación para el administrador
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('ghd_view_frontend');
        $admin_role->add_cap('ghd_assign_task_carpinteria'); 
        $admin_role->add_cap('ghd_assign_task_corte');
        $admin_role->add_cap('ghd_assign_task_costura');
        $admin_role->add_cap('ghd_assign_task_tapiceria');
        $admin_role->add_cap('ghd_assign_task_embalaje');
        $admin_role->add_cap('ghd_assign_task_logistica');
        $admin_role->add_cap('ghd_view_sales'); // Admin ve todas las ventas
        $admin_role->add_cap('ghd_manage_commissions'); // Admin puede gestionar comisiones
        $admin_role->add_cap('ghd_upload_remito_photo'); // Admin también puede subir fotos de remito
        $admin_role->add_cap('ghd_check_payments'); // Admin también puede chequear pagos
        $admin_role->add_cap('ghd_register_embalaje_points'); // Admin puede registrar puntos de embalaje si es necesario
        $admin_role->add_cap('ghd_register_own_embalaje'); // Admin puede registrar su propio embalaje
        $admin_role->add_cap('ghd_manage_own_delivery'); // Admin puede gestionar sus propias entregas
        $admin_role->add_cap('ghd_view_all_sector_tasks'); // <- línea agregada para que Admin vea todas las tareas de todos los sectores
    }
}
/////// Fin registro roles personalizados /////////// Fin registro roles personalizados //////

// --- 3. FUNCIONES DE AYUDA ---
function ghd_get_sectores() { 
    return [
        'carpinteria' => 'Carpintería', 
        'corte'       => 'Corte', 
        'costura'     => 'Costura', 
        'tapiceria'   => 'Tapicería', 
        'embalaje'    => 'Embalaje', 
        'logistica'   => 'Logística'
    ]; 
}

function ghd_get_mapa_roles_a_campos() {
    return [
        // Mapeo de roles REALES a campos de estado ACF
        'lider_carpinteria' => 'estado_carpinteria', 
        'operario_carpinteria' => 'estado_carpinteria',
        'lider_corte' => 'estado_corte', 
        'operario_corte' => 'estado_corte',
        'lider_costura' => 'estado_costura', 
        'operario_costura' => 'estado_costura',
        'lider_tapiceria' => 'estado_tapiceria', 
        'operario_tapiceria' => 'estado_tapiceria',
        'lider_embalaje' => 'estado_embalaje', 
        'operario_embalaje' => 'estado_embalaje',
        'lider_logistica' => 'estado_logistica', 
        'operario_logistica' => 'estado_logistica',
        'control_final_macarena' => 'estado_administrativo', // Macarena usa este campo
        // 'vendedora' y 'gerente_ventas' no tienen un 'estado_X' asociado para tareas,
        // ya que su panel es de ventas/comisiones.
    ];
}

/**
 * Asigna un color consistente a una vendedora basándose en su ID de usuario.
 * Devuelve un array con el color de fondo (HEX sólido) y el color de texto (blanco o negro).
 * @param int $user_id El ID del usuario de la vendedora.
 * @return array Un array con 'bg_color' (HEX) y 'text_color'.
 */
function ghd_get_vendedora_color($user_id) {
    $base_hex_colors = [
        '#ef4444', // Rojo vibrante
        '#f97316', // Naranja
        '#eab308', // Amarillo mostaza
        '#22c55e', // Verde esmeralda
        '#16a34a', // Verde oscuro
        '#06b6d4', // Azul cian
        '#3b82f6', // Azul brillante
        '#6366f1', // Azul índigo
        '#a855f7', // Púrpura
        '#d946ef', // Rosa fucsia
        '#ec4899', // Rosa cálido
        '#f43f5e', // Rosa rojizo
        '#6b7280', // Gris medio
        // Repite colores si es necesario o añade más para una mayor variedad
    ];

    $color_index = $user_id % count($base_hex_colors);
    $hex_color = $base_hex_colors[$color_index]; // Este será ahora el color de fondo

    // Convertir HEX a RGB para calcular luminancia
    $hex_cleaned = str_replace('#', '', $hex_color);
    if (strlen($hex_cleaned) == 3) {
        $r = hexdec(substr($hex_cleaned, 0, 1).substr($hex_cleaned, 0, 1));
        $g = hexdec(substr($hex_cleaned, 1, 1).substr($hex_cleaned, 1, 1));
        $b = hexdec(substr($hex_cleaned, 2, 1).substr($hex_cleaned, 2, 1));
    } else {
        $r = hexdec(substr($hex_cleaned, 0, 2));
        $g = hexdec(substr($hex_cleaned, 2, 2));
        $b = hexdec(substr($hex_cleaned, 4, 2));
    }

    // Calcular la luminancia relativa (BT.709) para determinar el color de texto
    $luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255; 

    // Usar un umbral para decidir si el texto es blanco o negro
    // Para colores sólidos, un umbral de 0.5 a 0.6 es un buen punto de partida.
    $text_color = ($luminance > 0.55) ? '#000000' : '#ffffff'; 
    // He ajustado el umbral a 0.55. Puedes experimentar entre 0.5 y 0.7 si es necesario.

    return [
        'bg_color'   => $hex_color, // Devolvemos el color HEX sólido
        'text_color' => $text_color
    ];
}// fin ghd_get_vendedora_color ////

/**
 * Función para calcular los KPIs de un sector dado un campo de estado.
 * Reutilizable para la carga inicial y las respuestas AJAX.
 * @param string $campo_estado El nombre del campo ACF (ej. 'estado_corte').
 * @return array Un array con 'total_pedidos', 'total_prioridad_alta', 'tiempo_promedio_str', 'completadas_hoy'.
 */
function ghd_calculate_sector_kpis($campo_estado) {
    // --- CORRECCIÓN: Definir $fecha_completado_field dinámicamente ---
    // Esta variable se espera en la meta_query para calcular 'completadas_hoy'.
    // Debe corresponder al nombre del campo ACF de la fecha de completado del sector.
    $fecha_completado_field = str_replace('estado_', 'fecha_completado_', $campo_estado);
    // --- FIN CORRECCIÓN ---

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
                'key'     => $campo_estado, // Todavía filtrar por el estado de la tarea
                'value'   => 'Completado',
                'compare' => '=',
            ],
            // --- NUEVO: Filtrar por el campo de fecha de completado ---
            [
                'key'     => $fecha_completado_field, // Usar el campo de fecha específico
                'value'   => date('Y-m-d H:i:s', $today_start),
                'compare' => '>=',
                'type'    => 'DATETIME',
            ],
            [
                'key'     => $fecha_completado_field,
                'value'   => date('Y-m-d H:i:s', $today_end),
                'compare' => '<=',
                'type'    => 'DATETIME',
            ],
            // --- FIN NUEVO ---
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
} //// fin ghd_calculate_sector_kpis ////

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

/**
 * Función para obtener los datos de pedidos en producción y sus KPIs.
 * V3 - Corregido para evitar duplicados en la visualización de estados y asignaciones.
 */
function ghd_get_pedidos_en_produccion_data() {
    $pedidos_args = [
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
        'meta_query'     => [
            ['key' => 'estado_pedido', 'value' => ['En Producción', 'En Costura', 'En Tapicería/Embalaje', 'Listo para Entrega', 'Despachado'], 'compare' => 'IN']
        ],
        'meta_key'       => 'prioridad_pedido',
        'orderby'        => ['meta_value' => 'ASC', 'date' => 'ASC']
    ];
    $pedidos_query = new WP_Query($pedidos_args);

    $kpi_data = [
        'total_pedidos_produccion'        => $pedidos_query->post_count,
        'total_prioridad_alta_produccion' => 0,
        'tiempo_promedio_str_produccion'  => '0.0h',
        'completadas_hoy_produccion'      => 0,
    ];
    
    $total_tiempo_produccion = 0;
    $ahora = current_time('U');

    ob_start();

    if ($pedidos_query->have_posts()) :
        while ($pedidos_query->have_posts()) : $pedidos_query->the_post();
            $order_id = get_the_ID();
            if (get_field('prioridad_pedido', $order_id) === 'Alta') {
                $kpi_data['total_prioridad_alta_produccion']++;
            }
            $produccion_iniciada_time = get_post_meta($order_id, 'historial_produccion_iniciada_timestamp', true);
            $total_tiempo_produccion += $ahora - ($produccion_iniciada_time ?: get_the_modified_time('U', $order_id));
            
            $vendedora_obj = get_userdata(get_field('vendedora_asignada', $order_id));
            ?>
            <tr id="order-row-prod-<?php echo $order_id; ?>">
                <td><a href="<?php the_permalink(); ?>" style="color: var(--color-rojo); font-weight: 600;"><?php the_title(); ?></a></td>
                <td><?php echo esc_html(get_field('nombre_cliente', $order_id)); ?></td>
                <td><?php echo $vendedora_obj ? esc_html($vendedora_obj->display_name) : 'N/A'; ?></td>
                <td><?php echo esc_html(get_field('nombre_producto', $order_id)); ?></td>
                <td><?php echo esc_html(get_field('material_del_producto', $order_id)); ?></td>
                <td>
                    <?php $color_producto = get_field('color_del_producto', $order_id); ?>
                    <?php if ($color_producto) : ?>
                        <span class="color-swatch" style="background-color: <?php echo esc_attr($color_producto); ?>;"></span>
                        <?php echo esc_html($color_producto); ?>
                    <?php else: echo 'N/A'; endif; ?>
                </td>
                <td class="production-observations"><?php echo nl2br(esc_html(get_field('observaciones_personalizacion', $order_id))); ?></td>
                <td><?php echo esc_html(get_field('estado_pedido', $order_id)); ?></td>
                <td>
                    <div class="production-substatus-badges">
                        <?php
                        // --- CORRECCIÓN: Iterar sobre una lista limpia de sectores para evitar duplicados ---
                        $sectores = ['carpinteria', 'corte', 'costura', 'tapiceria', 'embalaje', 'logistica'];
                        foreach ($sectores as $sector) {
                            $sub_estado = get_field('estado_' . $sector, $order_id);
                            if ($sub_estado && $sub_estado !== 'No Asignado') {
                                $badge_class = 'status-gray';
                                if ($sub_estado === 'Completado') $badge_class = 'status-green';
                                elseif ($sub_estado === 'En Progreso') $badge_class = 'status-yellow';
                                elseif ($sub_estado === 'Pendiente') $badge_class = 'status-blue';
                                echo '<span class="ghd-badge ' . esc_attr($badge_class) . '">' . esc_html(ucfirst($sector)) . ': ' . esc_html($sub_estado) . '</span>';
                            }
                        }
                        ?>
                    </div>
                </td>
                <td>
                    <div class="assigned-completed-info">
                        <?php 
                        // --- CORRECCIÓN: Usar la misma lista limpia de sectores ---
                        foreach ($sectores as $sector) {
                            $assignee_id = intval(get_field('asignado_a_' . $sector, $order_id));
                            $completed_by_id = intval(get_field('completado_por_' . $sector, $order_id));
                            
                            $assignee_obj = ($assignee_id > 0) ? get_userdata($assignee_id) : null;
                            $completed_by_obj = ($completed_by_id > 0) ? get_userdata($completed_by_id) : null;
                            
                            if ($assignee_obj || $completed_by_obj) {
                                echo '<p><strong>' . esc_html(ucfirst($sector)) . ':</strong></p>';
                                if ($assignee_obj) {
                                    echo '<span class="ghd-info-badge info-assigned">Asignado: ' . esc_html($assignee_obj->display_name) . '</span>';
                                }
                                if ($completed_by_obj) {
                                    echo '<span class="ghd-info-badge info-completed">Completado: ' . esc_html($completed_by_obj->display_name) . '</span>';
                                }
                            }
                        }
                        ?>
                    </div>
                </td>
                <td><a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary ghd-btn-small">Ver</a></td>
            </tr>
            <?php
        endwhile;
    else : ?>
        <tr><td colspan="11" style="text-align:center;">No hay pedidos actualmente en producción.</td></tr>
    <?php endif;
    wp_reset_postdata();
    $production_tasks_html = ob_get_clean();

    if ($kpi_data['total_pedidos_produccion'] > 0) {
        $kpi_data['tiempo_promedio_str_produccion'] = number_format(($total_tiempo_produccion / $kpi_data['total_pedidos_produccion']) / 3600, 1) . 'h';
    }

    // Código para calcular 'completadas_hoy_produccion' se mantiene igual...
    $today_start = strtotime('today', current_time('timestamp', true));
    $today_end   = strtotime('tomorrow - 1 second', current_time('timestamp', true));
    $completed_production_today_args = [
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
        'meta_query'     => [['key' => 'estado_pedido', 'value' => 'Pendiente de Cierre Admin', 'compare' => '=']],
        'date_query'     => [['after' => date('Y-m-d H:i:s', $today_start), 'before' => date('Y-m-d H:i:s', $today_end), 'inclusive' => true, 'column' => 'post_modified_gmt']]
    ];
    $completed_production_today_query = new WP_Query($completed_production_today_args);
    $kpi_data['completadas_hoy_produccion'] = $completed_production_today_query->post_count;
    
    return ['tasks_html' => $production_tasks_html, 'kpi_data' => $kpi_data];
}// fin ghd_get_pedidos_en_produccion_data ////

/**
 * --- Recopilar datos para la página de reportes ---
 * (Ahora está dentro del functions.php principal, no como un fragmento)
 */
function ghd_get_reports_data() {
    $reports_data = [
        'pedidos_por_estado' => [
            'labels' => [],
            'data'   => [],
            'backgroundColors' => [ // Colores predefinidos para los estados
                'Pendiente de Asignación' => '#6B7280', // Gris
                'En Producción'            => '#3b82f6', // Azul
                'En Costura'               => '#f59e0b', // Amarillo
                'En Tapicería/Embalaje'    => '#60a5fa', // Azul claro
                'Listo para Entrega'       => '#22c55e', // Verde
                'Despachado'               => '#84cc16', // Verde lima
                'Pendiente de Cierre Admin'=> '#ef4444', // Rojo
                'Completado y Archivado'   => '#10b981', // Verde oscuro
            ]
        ],
        'carga_por_sector' => [
            'labels' => [],
            'data'   => [],
            'backgroundColors' => [] // Colores dinámicos o predefinidos si los tienes
        ],
        'pedidos_por_prioridad' => [
            'labels' => ['Alta', 'Media', 'Baja'],
            'data'   => [0, 0, 0],
            'backgroundColors' => [
                'Alta'  => '#ef4444', // Rojo
                'Media' => '#f59e0b', // Amarillo
                'Baja'  => '#22c55e', // Verde
            ]
        ],
    ];

    // Consulta para todos los pedidos de producción
    $all_orders_query = new WP_Query([
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
        'post_status'    => 'publish', // Asegúrate de obtener solo los publicados
    ]);

    $estados_generales_count = [];
    $carga_por_sector_count = [];
    $mapa_roles_a_campos = ghd_get_mapa_roles_a_campos();
    $sectores = ghd_get_sectores();

    // Inicializar contadores para los gráficos
    foreach (['Pendiente de Asignación', 'En Producción', 'En Costura', 'En Tapicería/Embalaje', 'Listo para Entrega', 'Despachado', 'Pendiente de Cierre Admin', 'Completado y Archivado'] as $estado) {
        $estados_generales_count[$estado] = 0;
    }
    // Inicializar carga por sector con todos los sectores de ghd_get_sectores
    foreach ($sectores as $sector_display_name) {
        $carga_por_sector_count[$sector_display_name] = 0;
    }
    

    if ($all_orders_query->have_posts()) {
        while ($all_orders_query->have_posts()) {
            $all_orders_query->the_post();
            $order_id = get_the_ID();

            // Pedidos por Estado General
            $estado_general = get_field('estado_pedido', $order_id);
            if (isset($estados_generales_count[$estado_general])) {
                $estados_generales_count[$estado_general]++;
            } else {
                $estados_generales_count[$estado_general] = 1; // Para estados no predefinidos
            }

            // Pedidos por Prioridad
            $prioridad = get_field('prioridad_pedido', $order_id);
            if ($prioridad === 'Alta') {
                $reports_data['pedidos_por_prioridad']['data'][0]++;
            } elseif ($prioridad === 'Media') {
                $reports_data['pedidos_por_prioridad']['data'][1]++;
            } else { // Baja
                $reports_data['pedidos_por_prioridad']['data'][2]++;
            }

            // Carga de Trabajo por Sector (contar 'Pendiente' o 'En Progreso' para cada campo de sector)
            foreach ($mapa_roles_a_campos as $role_key => $field_key) {
                $sub_estado = get_field($field_key, $order_id);
                // Si el sector tiene una tarea activa (Pendiente o En Progreso), se suma a su carga
                if ($sub_estado === 'Pendiente' || $sub_estado === 'En Progreso') {
                    // El sector_display_name ya está capitalizado en ghd_get_sectores
                    $sector_display_name = ucfirst(str_replace(['rol_', 'estado_'], '', $role_key)); 
                    if (isset($carga_por_sector_count[$sector_display_name])) {
                        $carga_por_sector_count[$sector_display_name]++;
                    }
                    // Si no está en $carga_por_sector_count, significa que no es un sector de ghd_get_sectores
                    // o no fue inicializado correctamente, pero ya lo inicializamos.
                }
            }
        }
        wp_reset_postdata();
    }

    // Formatear datos para los gráficos
    $reports_data['pedidos_por_estado']['labels'] = array_keys($estados_generales_count);
    $reports_data['pedidos_por_estado']['data'] = array_values($estados_generales_count);

    // Asignar colores a la carga por sector (si no están definidos)
    $sector_colors = ['#4A7C59', '#B34A49', '#F59E0B', '#6B7280', '#3E3E3E', '#93c5fd', '#f472b6']; // Ejemplo de colores
    $color_index = 0;
    foreach ($carga_por_sector_count as $sector => $count) {
        $reports_data['carga_por_sector']['labels'][] = $sector;
        $reports_data['carga_por_sector']['data'][] = $count;
        $reports_data['carga_por_sector']['backgroundColors'][] = $sector_colors[$color_index % count($sector_colors)];
        $color_index++;
    }


    return $reports_data;
}


// --- 4. LÓGICA DE LOGIN/LOGOUT (REVISADA FINALMENTE) ---

/**
 * Redirige los inicios de sesión fallidos a la página de login personalizada con un parámetro de error.
 */
add_action('wp_login_failed', 'ghd_login_fail_redirect');
function ghd_login_fail_redirect($username) {
    // Obtener la URL de tu página de login personalizada de forma robusta
    $login_page_query = get_posts([
        'post_type'  => 'page',
        'fields'     => 'ids',
        'nopaging'   => true,
        'meta_key'   => '_wp_page_template',
        'meta_value' => 'template-login.php'
    ]);
    $custom_login_url = !empty($login_page_query) ? get_permalink($login_page_query[0]) : home_url('/iniciar-sesion/');
    
    // Si la solicitud no es AJAX/CRON y el referrer no es ya una página de login de WP
    if (!defined('DOING_AJAX') && !defined('DOING_CRON') && !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'wp-login.php') === false && strpos($_SERVER['HTTP_REFERER'], 'wp-admin') === false ) {
        wp_redirect($custom_login_url . '?login=failed'); // Redirigir con parámetro de error
        exit();
    }
    // Si viene de una página de login de WP, la función ghd_redirect_wp_login_to_custom_page se encargará.
}

/**
 * Oculta la Admin Bar en el Frontend para TODOS los usuarios.
 */
add_action('after_setup_theme', 'ghd_hide_admin_bar');
function ghd_hide_admin_bar() {
    if (!is_admin()) {
        show_admin_bar(false);
    }
}

/**
 * --- NUEVO: LÓGICA AJAX PARA ACTUALIZAR PRIORIDAD DE PEDIDO ---
 * Permite al administrador actualizar la prioridad de un pedido directamente desde el selector.
 */
add_action('wp_ajax_ghd_update_priority', 'ghd_update_priority_callback');
function ghd_update_priority_callback() {
    // --- CLAVE: Verificación de Nonce de seguridad ---
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ghd-ajax-nonce' ) ) {
        wp_send_json_error( ['message' => 'Nonce de seguridad inválido o faltante.'] );
        wp_die();
    }
    // --- FIN CLAVE ---

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos para actualizar la prioridad.']);
        wp_die();
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $new_priority = isset($_POST['priority']) ? sanitize_text_field($_POST['priority']) : '';

    if (!$order_id) {
        wp_send_json_error(['message' => 'ID de pedido no válido.']);
        wp_die();
    }
    if (empty($new_priority) || $new_priority === 'Seleccionar Prioridad') {
        wp_send_json_success(['message' => 'Prioridad no seleccionada, no se ha guardado.']); // No es un error, solo no se guarda.
        wp_die();
    }

    update_field('prioridad_pedido', $new_priority, $order_id);

    wp_insert_post([
        'post_title' => 'Prioridad actualizada para ' . get_the_title($order_id),
        'post_type' => 'ghd_historial',
        'meta_input' => ['_orden_produccion_id' => $order_id, '_nueva_prioridad' => $new_priority] // Guardar solo la nueva prioridad para el historial
    ]);
    
    wp_send_json_success(['message' => 'Prioridad actualizada con éxito a ' . $new_priority . '.']);
    wp_die();
}
/////////////////////////////////////////////////////fin de ghd_update_priority() //////////////////////////////////////////////////

add_action('wp_ajax_ghd_update_task_status', function() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('read')) {
        wp_send_json_error(['message' => 'No tienes permisos.']);
        wp_die();
    }

    $id = intval($_POST['order_id']);
    $field = sanitize_key($_POST['field']);
    $value = sanitize_text_field($_POST['value']);
    $assignee_id = isset($_POST['assignee_id']) ? intval($_POST['assignee_id']) : 0;

    if (!$id || !$field || !$value) {
        wp_send_json_error(['message' => 'Faltan datos para actualizar la tarea.']);
        wp_die();
    }
    
    // Si la acción es completar, el modal se encarga. Esta función solo es para 'Iniciar Tarea'.
    if ($value === 'Completado') {
        wp_send_json_error(['message' => 'La finalización se gestiona desde el modal de registro.']);
        wp_die();
    }

    // Actualizar el estado del campo (ej. a 'En Progreso')
    update_field($field, $value, $id);

    // Si se está iniciando la tarea y se ha asignado un operario, guardar la asignación.
    if ($value === 'En Progreso' && $assignee_id > 0) {
        $assignee_field = str_replace('estado_', 'asignado_a_', $field);
        update_field($assignee_field, $assignee_id, $id);
        
        $assignee_obj = get_userdata($assignee_id);
        $assignee_name = $assignee_obj ? $assignee_obj->display_name : 'ID ' . $assignee_id;
        $historial_title = ucfirst(str_replace(['estado_', '_'], ' ', $field)) . ' -> ' . $value . ' (Asignado a ' . $assignee_name . ')';
    } else {
        $historial_title = ucfirst(str_replace(['estado_', '_'], ' ', $field)) . ' -> ' . $value;
    }

    wp_insert_post([
        'post_title' => $historial_title,
        'post_type' => 'ghd_historial',
        'meta_input' => ['_orden_produccion_id' => $id]
    ]);
    
    // Éxito. Solo devolvemos un mensaje y los KPIs. El JS se encargará de refrescar.
    $sector_kpi_data = ghd_calculate_sector_kpis($field);
    wp_send_json_success(['message' => 'Estado actualizado.', 'kpi_data' => $sector_kpi_data]);
    wp_die();
}); // fin ghd_update_task_status //

// --- MANEJADOR AJAX PARA ARCHIVAR PEDIDOS (AHORA LLAMADO POR EL ADMIN PRINCIPAL) ---
add_action('wp_ajax_ghd_archive_order', 'ghd_archive_order_callback');
function ghd_archive_order_callback() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');

    // --- CORRECCIÓN: Permitir acceso a Admin Y a Control Final ---
    if (!current_user_can('manage_options') && !current_user_can('control_final_macarena')) {
        wp_send_json_error(['message' => 'No tienes permisos para archivar pedidos.']);
        wp_die();
    }    

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error(['message' => 'ID de pedido no válido.']);
    }

    // Actualizar los campos a archivado
    update_field('estado_administrativo', 'Archivado', $order_id);
    update_field('estado_pedido', 'Completado y Archivado', $order_id);
    update_field('fecha_de_archivo_pedido', current_time('mysql'), $order_id); // <-- NUEVO: Guardar fecha y hora exactas
    wp_insert_post([ 
        'post_title' => 'Pedido Cerrado y Archivado', 
        'post_type' => 'ghd_historial', 
        'meta_input' => ['_orden_produccion_id' => $order_id, '_fecha_archivo' => current_time('mysql')] 
    ]);

    // --- RECALCULAR Y DEVOLVER KPIs para el panel del Admin principal ---
    // Pedidos pendientes de cierre para el Admin (nuevo estado)
    $kpi_args = [
        'post_type' => 'orden_produccion', 'posts_per_page' => -1,
        'meta_query' => [['key' => 'estado_pedido', 'value' => 'Pendiente de Cierre Admin', 'compare' => '=']]
    ];
    $kpi_query = new WP_Query($kpi_args);
    
    $kpi_data = [
        'total_pedidos_cierre' => $kpi_query->post_count, // KPIs específicos para esta sección del Admin
        'total_prioridad_alta_cierre' => 0,
        'tiempo_promedio_str_cierre' => '0.0h',
        'completadas_hoy_cierre' => 0 
    ];
    
    $total_tiempo_espera = 0;
    $ahora = current_time('U');
    if ($kpi_query->have_posts()) {
        foreach ($kpi_query->posts as $pedido) {
            if (get_field('prioridad_pedido', $pedido->ID) === 'Alta') { $kpi_data['total_prioridad_alta_cierre']++; }
            // Calcular el tiempo de espera desde que Logística lo marcó como completado
            $logistica_completada_time = get_post_meta($pedido->ID, 'historial_logistica_completada_timestamp', true); // Asumiendo que guardamos un timestamp al completar Logística
            if ($logistica_completada_time) {
                $total_tiempo_espera += $ahora - $logistica_completada_time;
            } else {
                 $total_tiempo_espera += $ahora - get_the_modified_time('U', $pedido->ID); // Fallback
            }
        }
    }
    if ($kpi_data['total_pedidos_cierre'] > 0) {
        $promedio_horas = ($total_tiempo_espera / $kpi_data['total_pedidos_cierre']) / 3600;
        $kpi_data['tiempo_promedio_str_cierre'] = number_format($promedio_horas, 1) . 'h';
    }

    // --- Lógica para calcular 'Completadas Hoy' (archivadas hoy por el Admin) ---
    $today_start = strtotime('today', current_time('timestamp', true)); // Inicio de hoy en timestamp GMT
    $today_end   = strtotime('tomorrow - 1 second', current_time('timestamp', true)); // Fin de hoy en timestamp GMT

    $completadas_hoy_args = [
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => 'estado_pedido',
                'value'   => 'Completado y Archivado',
                'compare' => '=',
            ],
        ],
        'date_query' => [ 
            'after'     => date('Y-m-d H:i:s', $today_start),
            'before'    => date('Y-m-d H:i:s', $today_end),
            'inclusive' => true,
            'column'    => 'post_modified_gmt', // Usar la columna de modificación en GMT
        ],
    ];
    $completadas_hoy_query = new WP_Query($completadas_hoy_args);
    $kpi_data['completadas_hoy_cierre'] = $completadas_hoy_query->post_count;

    wp_send_json_success(['message' => 'Pedido archivado con éxito.', 'kpi_data' => $kpi_data]);
    wp_die();
}

/**
 * AJAX Handler para refrescar las tareas y KPIs de un sector específico.
 * V3 - Corregido para obtener la lista de operarios del sector correcto.
 */
add_action('wp_ajax_ghd_refresh_sector_tasks', 'ghd_refresh_sector_tasks_callback');
function ghd_refresh_sector_tasks_callback() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('read')) {
        wp_send_json_error(['message' => 'No tienes permisos.']);
        wp_die();
    }

    $campo_estado = isset($_POST['campo_estado']) ? sanitize_key($_POST['campo_estado']) : '';
    if (empty($campo_estado)) {
        wp_send_json_error(['message' => 'Campo de estado no proporcionado.']);
        wp_die();
    }

    // --- CORRECCIÓN CLAVE: Determinar el sector a partir del campo_estado recibido ---
    $base_sector_key = str_replace('estado_', '', $campo_estado);
    // --- FIN CORRECCIÓN ---

        $current_user = wp_get_current_user();
    $user_roles = (array) $current_user->roles;
    $is_leader_actual_role = false; // Bandera para el rol de líder real del usuario
    foreach($user_roles as $role) {
        if (strpos($role, 'lider_') !== false) {
            $is_leader_actual_role = true;
            break;
        }
    }

    // Para el contexto de renderizado de la tarjeta, el administrador también debe verse como un "líder"
    // para poder asignar tareas y ver el selector.
    $is_leader_for_rendering = $is_leader_actual_role || current_user_can('ghd_view_all_sector_tasks'); // <-- CLAVE: Permitir al admin asignar

    $operarios_del_sector = [];
    // Obtener operarios si es un líder real O un administrador con la capacidad
    if ($is_leader_for_rendering && !empty($base_sector_key)) {
        $operarios_del_sector = get_users([
            'role__in' => ['lider_' . $base_sector_key, 'operario_' . $base_sector_key], 
            'orderby'  => 'display_name',
            'order'    => 'ASC'
        ]);
    }

    // PASAR LA BANDERA CORRECTA PARA EL RENDERIZADO AL TEMPLATE PART
    // Aseguramos que $is_leader que se pasa a task-card.php considere al administrador
    $is_leader = $is_leader_for_rendering; // Usamos esta variable en $task_card_args

    $sector_kpi_data = ghd_calculate_sector_kpis($campo_estado);

    $pedidos_query_args = [
        'post_type'      => 'orden_produccion', 
        'posts_per_page' => -1, 
        'meta_query'     => [['key' => $campo_estado, 'value' => ['Pendiente', 'En Progreso'], 'compare' => 'IN']]
    ];
    
    // Si el usuario actual tiene la capacidad de ver todas las tareas del sector (ej. Administrador),
    // o si es el líder del sector, no aplicamos el filtro de asignación personal.
    // Solo si NO es líder Y NO es Admin con permiso de ver todo, filtramos por asignación.
    if (!current_user_can('ghd_view_all_sector_tasks') && !$is_leader) {
        $asignado_a_field = str_replace('estado_', 'asignado_a_', $campo_estado);
        $pedidos_query_args['meta_query'][] = ['key' => $asignado_a_field, 'value' => $current_user->ID, 'compare' => '='];
    }
    
    $pedidos_query = new WP_Query($pedidos_query_args);
    
    ob_start();

    if ($pedidos_query->have_posts()) : 
        while ($pedidos_query->have_posts()) : $pedidos_query->the_post();
            $current_order_id = get_the_ID();
            $prioridad_pedido = get_field('prioridad_pedido', $current_order_id);
            $asignado_a_field_name = str_replace('estado_', 'asignado_a_', $campo_estado);
            $asignado_a_id = get_field($asignado_a_field_name, $current_order_id);
            $asignado_a_user = $asignado_a_id ? get_userdata($asignado_a_id) : null;

            $task_card_args = [
                'post_id'           => $current_order_id,
                'titulo'            => get_the_title(),
                'prioridad_class'   => 'prioridad-' . strtolower($prioridad_pedido ?: 'baja'),
                'prioridad'         => $prioridad_pedido,
                'nombre_cliente'    => get_field('nombre_cliente', $current_order_id),
                'nombre_producto'   => get_field('nombre_producto', $current_order_id),
                'permalink'         => get_permalink(),
                'campo_estado'      => $campo_estado,
                'estado_actual'     => get_field($campo_estado, $current_order_id),
                'is_leader'         => $is_leader,
                'operarios_sector'  => $operarios_del_sector,
                'asignado_a_id'     => $asignado_a_id,
                'asignado_a_name'   => $asignado_a_user ? $asignado_a_user->display_name : 'Sin asignar',
                'logged_in_user_id' => $current_user->ID,
            ];
            
            get_template_part('template-parts/task-card', null, $task_card_args);
        endwhile;
    else: ?>
        <p class="no-tasks-message">No tienes tareas pendientes.</p>
    <?php endif; wp_reset_postdata(); 
    
    $tasks_html = ob_get_clean();

    wp_send_json_success([
        'tasks_html' => $tasks_html,
        'kpi_data' => $sector_kpi_data
    ]);
    wp_die();
}// fin ghd_refresh_sector_tasks
////////////// //////////////////////////////////////////////////////
/**
 * AJAX Handler para refrescar la sección de Pedidos Pendientes de Cierre y sus KPIs del Admin principal.
 */
add_action('wp_ajax_ghd_refresh_admin_closure_section', 'ghd_refresh_admin_closure_section_callback');
function ghd_refresh_admin_closure_section_callback() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
     // --- CORRECCIÓN: Permitir acceso a Admin Y a Control Final ---
    if (!current_user_can('manage_options') && !current_user_can('control_final_macarena')) {
        wp_send_json_error(['message' => 'No tienes permisos.']);
        wp_die();
    }

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

    // $remito_page_id = get_posts([
    //     'post_type'  => 'page',
    //     'fields'     => 'ids',
    //     'nopaging'   => true,
    //     'meta_key'   => '_wp_page_template',
    //     'meta_value' => 'template-remito.php'
    // ]);
    // $remito_base_url = !empty($remito_page_id) ? get_permalink($remito_page_id[0]) : home_url();
    

    // --- CORRECCIÓN: Usar get_page_by_path para obtener la URL de la página de remito ---
    $remito_page = get_page_by_path('remito'); // ASEGÚRATE DE QUE EL SLUG DE TU PÁGINA SEA 'remito'
    $remito_base_url = $remito_page ? get_permalink($remito_page->ID) : home_url();

    if ($pedidos_cierre_query->have_posts()) :
        while ($pedidos_cierre_query->have_posts()) : $pedidos_cierre_query->the_post();
            $order_id = get_the_ID();
            $remito_url = esc_url( add_query_arg( 'order_id', $order_id, $remito_base_url ) );
        ?>
            <tr id="order-row-closure-<?php echo $order_id; ?>">
                <td><a href="<?php the_permalink(); ?>" style="color: var(--color-rojo); font-weight: 600;"><?php the_title(); ?></a></td>
                <td><?php echo esc_html(get_field('nombre_cliente', $order_id)); ?></td>
                <td><?php echo esc_html(get_field('nombre_producto', $order_id)); ?></td>
                <td><?php echo get_the_date('d/m/Y', $order_id); ?></td>
                <td>
                    <a href="<?php echo $remito_url; ?>" target="_blank" class="ghd-btn ghd-btn-secondary ghd-btn-small generate-remito-btn" data-order-id="<?php echo $order_id; ?>">
                        <i class="fa-solid fa-file-invoice"></i> Generar Remito
                    </a>
                    <button class="ghd-btn ghd-btn-success archive-order-btn" data-order-id="<?php echo $order_id; ?>">
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

/**
 * Registra la nueva plantilla de página para Pedidos Archivados.
 *
 * @param array $templates Un array de plantillas de página.
 * @return array Un array modificado de plantillas de página.
 */
add_filter( 'theme_page_templates', 'ghd_register_archived_orders_template' );
function ghd_register_archived_orders_template( $templates ) {
    $templates['template-pedidos-archivados.php'] = 'GHD - Pedidos Archivados';
    return $templates;
}


/**
 * --- NUEVO ENDPOINT AJAX: Registrar Detalles de Tarea y Marcar como Completada ---
 * Este endpoint maneja la finalización de una tarea de sector, incluyendo datos adicionales.
 */
add_action('wp_ajax_ghd_register_task_details_and_complete', 'ghd_register_task_details_and_complete_callback');
function ghd_register_task_details_and_complete_callback() {
    
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('read')) {
        wp_send_json_error(['message' => 'No tienes permisos para completar tareas.']);
        wp_die();
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $field_estado_sector = isset($_POST['field']) ? sanitize_key($_POST['field']) : '';
    $observaciones_tarea_completa = isset($_POST['observaciones_tarea_completa']) ? sanitize_textarea_field($_POST['observaciones_tarea_completa']) : '';

    if (!$order_id || empty($field_estado_sector)) {
        wp_send_json_error(['message' => 'Datos de tarea incompletos.']);
        wp_die();
    }

    $current_user_id = get_current_user_id();
    
    // Guardar los datos de la tarea que se está completando
    update_field($field_estado_sector, 'Completado', $order_id);
    update_field(str_replace('estado_', 'fecha_completado_', $field_estado_sector), current_time('mysql'), $order_id);
    update_field(str_replace('estado_', 'completado_por_', $field_estado_sector), $current_user_id, $order_id);
    update_field(str_replace('estado_', '', $field_estado_sector) . '_observaciones_tarea_completa', $observaciones_tarea_completa, $order_id);

    if (isset($_FILES['foto_tarea']) && !empty($_FILES['foto_tarea']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        $attachment_id = media_handle_upload('foto_tarea', $order_id);
        if (!is_wp_error($attachment_id)) {
            update_field(str_replace('estado_', '', $field_estado_sector) . '_foto_principal_tarea', $attachment_id, $order_id);
        }
    }
    
    wp_insert_post([
        'post_title' => ucfirst(str_replace('estado_', '', $field_estado_sector)) . ' completado por ' . get_the_author_meta('display_name', $current_user_id),
        'post_type' => 'ghd_historial',
        'meta_input' => ['_orden_produccion_id' => $order_id]
    ]);
    // --- NUEVO: Lógica para guardar datos específicos del sector de Embalaje ---
    if ($field_estado_sector === 'estado_embalaje') {
        $operario_embalaje_id = isset($_POST['operario_embalaje_id']) ? intval($_POST['operario_embalaje_id']) : 0;
        $modelo_embalado_id   = isset($_POST['modelo_embalado_id']) ? intval($_POST['modelo_embalado_id']) : 0;
        $cantidad_embalada    = isset($_POST['cantidad_embalada']) ? intval($_POST['cantidad_embalada']) : 0;

        if ($operario_embalaje_id === 0 || $modelo_embalado_id === 0 || $cantidad_embalada === 0) {
            wp_send_json_error(['message' => 'Faltan datos obligatorios para el registro de puntos de embalaje.']);
            wp_die();
        }

        // 1. Obtener los puntos del modelo seleccionado
        $modelo_puntos_obj = null;
        $embalaje_models = ghd_get_embalaje_models_for_select(); // Reutilizamos la función de ayuda
        foreach ($embalaje_models as $model) {
            if ($model->id === $modelo_embalado_id) {
                $modelo_puntos_obj = $model;
                break;
            }
        }

        if (!$modelo_puntos_obj) {
            wp_send_json_error(['message' => 'Modelo de embalaje no válido.']);
            wp_die();
        }

        $puntos_por_modelo = $modelo_puntos_obj->points;
        $puntos_totales_tarea = $puntos_por_modelo * $cantidad_embalada;

        // 2. Guardar estos datos específicos en campos ACF de la orden_produccion
        update_field('embalaje_operario_id', $operario_embalaje_id, $order_id);
        update_field('embalaje_modelo_id', $modelo_embalado_id, $order_id);
        update_field('embalaje_cantidad', $cantidad_embalada, $order_id);
        update_field('embalaje_puntos_tarea', $puntos_totales_tarea, $order_id);
        
        // 3. Sumar los puntos al perfil del operario (User Meta)
        $current_total_points = (int) get_user_meta($operario_embalaje_id, 'ghd_total_puntos_embalaje', true);
        $new_total_points = $current_total_points + $puntos_totales_tarea;
        update_user_meta($operario_embalaje_id, 'ghd_total_puntos_embalaje', $new_total_points);

        // Opcional: Registrar los puntos en el historial del post también
        wp_insert_post([
            'post_title' => 'Puntos Embalaje: ' . $modelo_puntos_obj->title . ' x' . $cantidad_embalada . ' = ' . $puntos_totales_tarea . ' puntos para Operario ID ' . $operario_embalaje_id,
            'post_type' => 'ghd_historial',
            'meta_input' => [
                '_orden_produccion_id' => $order_id,
                '_tipo_registro' => 'puntos_embalaje',
                '_operario_id' => $operario_embalaje_id,
                '_modelo_id' => $modelo_embalado_id,
                '_cantidad' => $cantidad_embalada,
                '_puntos_sumados' => $puntos_totales_tarea,
            ]
        ]);
    }
// --- FIN Lógica Embalaje ---

    // --- LÓGICA DE TRANSICIONES DE FLUJO (ESTRICTAMENTE SECUENCIAL) ---
    
    switch ($field_estado_sector) {
        case 'estado_carpinteria':
            update_field('estado_corte', 'Pendiente', $order_id);
            wp_insert_post(['post_title' => 'Fase Carpintería completa -> A Corte', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $order_id]]);
            break;
        
        case 'estado_corte':
            update_field('estado_costura', 'Pendiente', $order_id);
            update_field('estado_pedido', 'En Costura', $order_id);
            wp_insert_post(['post_title' => 'Fase Corte completa -> A Costura', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $order_id]]);
            break;

        case 'estado_costura':
            update_field('estado_tapiceria', 'Pendiente', $order_id);
            update_field('estado_pedido', 'En Tapicería/Embalaje', $order_id); // Cambiamos el estado general
            wp_insert_post(['post_title' => 'Fase Costura completa -> A Tapicería', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $order_id]]);
            break;
        
        case 'estado_tapiceria':
            update_field('estado_embalaje', 'Pendiente', $order_id);
            wp_insert_post(['post_title' => 'Fase Tapicería completa -> A Embalaje', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $order_id]]);
            break;

        case 'estado_embalaje':
            update_field('estado_logistica', 'Pendiente', $order_id);
            update_field('estado_pedido', 'Listo para Entrega', $order_id);
            wp_insert_post(['post_title' => 'Fase Embalaje completa -> A Logística', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $order_id]]);
            break;
        
        case 'estado_logistica':
            update_field('estado_pedido', 'Pendiente de Cierre Admin', $order_id);
            update_field('estado_administrativo', 'Listo para Archivar', $order_id);
            wp_insert_post(['post_title' => 'Entrega Completada -> Pendiente de Cierre Admin', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $order_id]]);
            break;
    }

    $sector_kpi_data = ghd_calculate_sector_kpis($field_estado_sector);
    
    wp_send_json_success(['message' => 'Tarea completada y detalles registrados.', 'kpi_data' => $sector_kpi_data]);
    wp_die();
}
///////////////////////////////////////////////////// fin ghd_register_task_details_and_complete() //////////////////////////////////////////////////


/**
 * --- NUEVO: LÓGICA AJAX PARA ACTUALIZAR VENDEDORA ASIGNADA ---
 * Permite al administrador asignar una vendedora a un pedido directamente desde el selector.
 */
add_action('wp_ajax_ghd_update_vendedora', 'ghd_update_vendedora_callback');
function ghd_update_vendedora_callback() {
    // Verificación de Nonce y permisos
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ghd-ajax-nonce' ) ) {
        wp_send_json_error( ['message' => 'Nonce de seguridad inválido o faltante.'] );
        wp_die();
    }
    if (!current_user_can('manage_options')) { // Solo Melany (admin) puede asignar vendedoras
        wp_send_json_error(['message' => 'No tienes permisos para asignar vendedoras.']);
        wp_die();
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $vendedora_id = isset($_POST['vendedora_id']) ? intval($_POST['vendedora_id']) : 0;

    if (!$order_id) {
        wp_send_json_error(['message' => 'ID de pedido no válido.']);
        wp_die();
    }
    if ($vendedora_id === 0) {
        // Si se selecciona "Asignar Vendedora" (valor 0), podríamos guardar null o un valor por defecto.
        // Aquí decidimos guardar 0 y que el frontend muestre "N/A"
        update_field('vendedora_asignada', 0, $order_id); 
        wp_send_json_success(['message' => 'Vendedora desasignada.']);
        wp_die();
    }

    // Verificar si el vendedora_id corresponde a un usuario con rol de vendedora/gerente_ventas
    $user_obj = get_userdata($vendedora_id);
    if (!$user_obj || (!in_array('vendedora', (array)$user_obj->roles) && !in_array('gerente_ventas', (array)$user_obj->roles))) {
        wp_send_json_error(['message' => 'ID de vendedora no válido o rol incorrecto.']);
        wp_die();
    }

    update_field('vendedora_asignada', $vendedora_id, $order_id);

    wp_insert_post([
        'post_title' => 'Vendedora asignada a ' . $user_obj->display_name . ' para pedido ' . get_the_title($order_id),
        'post_type' => 'ghd_historial',
        'meta_input' => ['_orden_produccion_id' => $order_id, '_vendedora_id' => $vendedora_id, '_asignado_por_user_id' => get_current_user_id()]
    ]);
    
    wp_send_json_success(['message' => 'Vendedora asignada con éxito a ' . $user_obj->display_name . '.']);
    wp_die();
}

// --- NUEVO: LÓGICA AJAX PARA INICIAR PRODUCCIÓN ---
add_action('wp_ajax_ghd_start_production', 'ghd_start_production_callback');
function ghd_start_production_callback() {
    // 1. Verificación de Nonce para seguridad
    check_ajax_referer('ghd-ajax-nonce', 'nonce');

    // 2. Verificación de permisos: solo administradores pueden iniciar producción
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos para iniciar la producción.']);
        wp_die();
    }

    // 3. Obtener y sanitizar el ID del pedido
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error(['message' => 'ID de pedido no válido.']);
        wp_die();
    }

    // 4. Verificar el estado actual del pedido para asegurar que es "Pendiente de Asignación"
    if (get_field('estado_pedido', $order_id) !== 'Pendiente de Asignación') {
        wp_send_json_error(['message' => 'El pedido ya no está en estado "Pendiente de Asignación".']);
        wp_die();
    }

    // 5. Actualizar los campos ACF para iniciar la producción
    update_field('estado_pedido', 'En Producción', $order_id);
    update_field('estado_carpinteria', 'Pendiente', $order_id);
    
    // Opcional: Guardar un timestamp de cuándo se inició la producción para métricas
    update_post_meta($order_id, 'historial_produccion_iniciada_timestamp', current_time('timestamp', true));

    // 6. Registrar la acción en el historial del pedido
    wp_insert_post([
        'post_title' => 'Producción Iniciada por ' . wp_get_current_user()->display_name,
        'post_type' => 'ghd_historial',
        'meta_input' => ['_orden_produccion_id' => $order_id, '_iniciada_por_user_id' => get_current_user_id()]
    ]);
    
    // 7. Preparar la respuesta JSON: devolver el HTML y KPIs actualizados para la sección de "Pedidos en Producción"
    $production_data = ghd_get_pedidos_en_produccion_data();
    
    wp_send_json_success([
        'message' => 'Producción iniciada con éxito.',
        'production_tasks_html' => $production_data['tasks_html'], // HTML actualizado para la tabla de producción
        'production_kpi_data' => $production_data['kpi_data']     // KPIs actualizados
    ]);
    wp_die();
} // fin ghd_start_production_callback()

/**
 * AJAX Handler para asignar una tarea a un miembro del sector.
 */
add_action('wp_ajax_ghd_assign_task_to_member', 'ghd_assign_task_to_member_callback');
function ghd_assign_task_to_member_callback() {
    // Verificación de Nonce y permisos
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ghd-ajax-nonce' ) ) {
        wp_send_json_error( ['message' => 'Nonce de seguridad inválido o faltante.'] );
        wp_die();
    }
    // Permiso: solo líderes o administradores pueden asignar tareas
    if (!current_user_can('assign_task_carpinteria') && !current_user_can('assign_task_corte') && /* ... y así para todos los roles de líder ... */ !current_user_can('manage_options')) {
         wp_send_json_error(['message' => 'No tienes permisos para asignar tareas.']);
         wp_die();
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $field_prefix = isset($_POST['field_prefix']) ? sanitize_key($_POST['field_prefix']) : ''; // ej. 'asignado_a_carpinteria'
    $assignee_id = isset($_POST['assignee_id']) ? intval($_POST['assignee_id']) : 0;

    if (!$order_id || empty($field_prefix)) {
        wp_send_json_error(['message' => 'Datos de asignación incompletos.']);
        wp_die();
    }

    // Guardar el ID del operario asignado en el campo ACF correspondiente
    update_field($field_prefix, $assignee_id, $order_id);

    // Registrar en el historial de GHD
    $assignee_name = 'Sin asignar';
    if ($assignee_id) {
        $user = get_userdata($assignee_id);
        if ($user) {
            $assignee_name = $user->display_name;
        }
    }
    wp_insert_post([
        'post_title' => 'Tarea asignada: ' . ucfirst(str_replace('asignado_a_', '', $field_prefix)) . ' -> ' . $assignee_name,
        'post_type' => 'ghd_historial',
        'meta_input' => ['_orden_produccion_id' => $order_id, '_' . $field_prefix => $assignee_id]
    ]);

    // Enviar respuesta de éxito
    wp_send_json_success(['message' => 'Tarea asignada correctamente.']);
    wp_die();
}/// fin ghd_assign_task_to_member_callback()

////// /////////////////////////////////////////////////////
// --- NUEVO: AJAX Handler para Fletero: Marcar Entrega como "Recogido" ---
add_action('wp_ajax_ghd_fletero_mark_recogido', 'ghd_fletero_mark_recogido_callback');
function ghd_fletero_mark_recogido_callback() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');

    // Solo fleteros, líderes de logística o administradores pueden usar esto
    if (!current_user_can('ghd_manage_own_delivery') && !current_user_can('lider_logistica') && !current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos para marcar entregas como recogidas.']);
        wp_die();
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error(['message' => 'ID de pedido no válido.']);
        wp_die();
    }

    // Opcional: Verificar que el pedido esté asignado al fletero actual si no es admin/líder
    if (!current_user_can('lider_logistica') && !current_user_can('manage_options')) {
        $assigned_fletero_id = (int) get_field('logistica_fletero_id', $order_id);
        if ($assigned_fletero_id !== get_current_user_id()) {
            wp_send_json_error(['message' => 'Este pedido no está asignado a ti.']);
            wp_die();
        }
    }

    // Actualizar el estado de logística del pedido
    update_field('estado_logistica', 'Recogido', $order_id);
    update_field('fecha_recogido', current_time('mysql'), $order_id); // Guardar fecha de recogido
    
    // Registrar en el historial
    wp_insert_post([
        'post_title' => 'Pedido Recogido por Fletero ID ' . get_current_user_id(),
        'post_type' => 'ghd_historial',
        'meta_input' => ['_orden_produccion_id' => $order_id, '_logistica_estado' => 'Recogido']
    ]);

    wp_send_json_success(['message' => 'Pedido marcado como recogido.']);
    wp_die();
}

// --- NUEVO: AJAX Handler para Fletero: Marcar Entrega como "Entregado" y Subir Comprobante ---
add_action('wp_ajax_ghd_fletero_complete_delivery', 'ghd_fletero_complete_delivery_callback');
function ghd_fletero_complete_delivery_callback() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');

    // Solo fleteros, líderes de logística o administradores pueden usar esto
    if (!current_user_can('ghd_manage_own_delivery') && !current_user_can('lider_logistica') && !current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos para completar entregas.']);
        wp_die();
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $firma_cliente = isset($_POST['firma_cliente']) ? sanitize_textarea_field($_POST['firma_cliente']) : '';

    if (!$order_id) {
        wp_send_json_error(['message' => 'ID de pedido no válido.']);
        wp_die();
    }

    // Opcional: Verificar que el pedido esté asignado al fletero actual
    if (!current_user_can('lider_logistica') && !current_user_can('manage_options')) {
        $assigned_fletero_id = (int) get_field('logistica_fletero_id', $order_id);
        if ($assigned_fletero_id !== get_current_user_id()) {
            wp_send_json_error(['message' => 'Este pedido no está asignado a ti.']);
            wp_die();
        }
    }

    // Actualizar el estado de logística del pedido a 'Completado'
    update_field('estado_logistica', 'Completado', $order_id);
    update_field('fecha_entregado', current_time('mysql'), $order_id); // Guardar fecha de entrega
    
    // Guardar la firma del cliente (si se proporcionó)
    if (!empty($firma_cliente)) {
        update_field('logistica_firma_cliente', $firma_cliente, $order_id); // <-- Este campo ACF ya debió crearse
    }

    // Manejar la subida de la foto de comprobante
    $attachment_id = 0;
    if (isset($_FILES['foto_comprobante']) && !empty($_FILES['foto_comprobante']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('foto_comprobante', $order_id);
        if (!is_wp_error($attachment_id)) {
            update_field('logistica_foto_comprobante', $attachment_id, $order_id); // <-- Este campo ACF ya debió crearse
        } else {
            error_log('Error al subir foto de comprobante para pedido ' . $order_id . ': ' . $attachment_id->get_error_message());
            // No hacemos wp_send_json_error aquí, para que la entrega se marque como completada aunque falle la foto
        }
    }
    
    // Registrar en el historial
    $historial_title = 'Entrega Completada por Fletero ID ' . get_current_user_id();
    if ($attachment_id > 0) $historial_title .= ' (con foto)';
    if (!empty($firma_cliente)) $historial_title .= ' (con firma)';

    wp_insert_post([
        'post_title' => $historial_title,
        'post_type' => 'ghd_historial',
        'meta_input' => ['_orden_produccion_id' => $order_id, '_logistica_estado' => 'Completado']
    ]);

    // Finalmente, cambiar el estado general del pedido para que Macarena lo vea
    update_field('estado_pedido', 'Pendiente de Cierre Admin', $order_id);
    update_field('estado_administrativo', 'Listo para Archivar', $order_id);

    wp_send_json_success(['message' => 'Entrega marcada como completada.']);
    wp_die();
}// fin ghd_fletero_complete_delivery_callback()

////// /////////////////////////////////////////////////////
// --- NUEVO: AJAX Handler para refrescar las entregas asignadas al fletero ---
add_action('wp_ajax_ghd_refresh_fletero_tasks', 'ghd_refresh_fletero_tasks_callback');
function ghd_refresh_fletero_tasks_callback() {
    error_log('ghd_refresh_fletero_tasks_callback: Inicio de la función.'); // DEBUG
    check_ajax_referer('ghd-ajax-nonce', 'nonce');

    if (!current_user_can('operario_logistica') && !current_user_can('lider_logistica') && !current_user_can('manage_options')) {
        error_log('ghd_refresh_fletero_tasks_callback: Permisos insuficientes para el usuario ' . get_current_user_id()); // DEBUG
        wp_send_json_error(['message' => 'No tienes permisos para ver entregas.']);
        wp_die();
    }

    error_log('ghd_refresh_fletero_tasks_callback: Permisos OK. Generando HTML.'); // DEBUG

    ob_start();
    $current_user_id = get_current_user_id();
    
    // Consulta para obtener las órdenes de producción asignadas al fletero actual (duplicar lógica de template-fletero.php)
    $args_entregas_fletero = array(
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'estado_logistica',
                'value'   => ['Pendiente', 'En Progreso', 'Recogido'], 
                'compare' => 'IN',
            ),
            array(
                'key'     => 'logistica_fletero_id',
                'value'   => $current_user_id,
                'compare' => '=',
            ),
        ),
        'orderby' => 'date',
        'order'   => 'ASC',
    );

    $entregas_fletero_query = new WP_Query($args_entregas_fletero);

    if ($entregas_fletero_query->have_posts()) :
        while ($entregas_fletero_query->have_posts()) : $entregas_fletero_query->the_post();
            $order_id = get_the_ID();
            $codigo_pedido = get_the_title();
            $nombre_cliente = get_field('nombre_cliente', $order_id);
            $direccion_entrega = get_field('direccion_de_entrega', $order_id);
            $estado_logistica = get_field('estado_logistica', $order_id);
            $nombre_producto = get_field('nombre_producto', $order_id);
            $cliente_telefono = get_field('cliente_telefono', $order_id);

            $action_button_html = '';
            if ($estado_logistica === 'Pendiente' || $estado_logistica === 'En Progreso') {
                $action_button_html = '<button class="ghd-btn ghd-btn-primary ghd-btn-small fletero-action-btn" data-order-id="' . esc_attr($order_id) . '" data-new-status="Recogido">Marcar como Recogido</button>';
            } elseif ($estado_logistica === 'Recogido') {
                $action_button_html = '<button class="ghd-btn ghd-btn-success ghd-btn-small fletero-action-btn open-upload-delivery-proof-modal" data-order-id="' . esc_attr($order_id) . '">Marcar Entregado y Subir Comprobante</button>';
            }
    ?>
        <div class="ghd-order-card fletero-card" id="fletero-order-<?php echo $order_id; ?>">
            <div class="order-card-main">
                <div class="order-card-header">
                    <h3><?php echo esc_html($codigo_pedido); ?></h3>
                    <span class="ghd-tag status-<?php echo strtolower(str_replace(' ', '-', $estado_logistica)); ?>"><?php echo esc_html($estado_logistica); ?></span>
                </div>
                <div class="order-card-body">
                    <p><strong>Cliente:</strong> <?php echo esc_html($nombre_cliente); ?></p>
                    <?php if ($nombre_producto) : ?><p><strong>Producto:</strong> <?php echo esc_html($nombre_producto); ?></p><?php endif; ?>
                    <p><strong>Dirección:</strong> <?php echo nl2br(esc_html($direccion_entrega)); ?></p>
                    <?php if ($cliente_telefono) : ?><p><strong>Teléfono:</strong> <a href="tel:<?php echo esc_attr($cliente_telefono); ?>"><?php echo esc_html($cliente_telefono); ?></a></p><?php endif; ?>
                </div>
            </div>
            <div class="order-card-actions">
                <?php echo $action_button_html; ?>
                <a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary ghd-btn-small">Ver Detalles</a>
            </div>
        </div>

        <!-- Modal para subir comprobante de entrega (DUPLICADO PARA AJAX) -->
        <div id="upload-delivery-proof-modal-<?php echo $order_id; ?>" class="ghd-modal">
            <div class="ghd-modal-content">
                <span class="close-button" data-modal-id="upload-delivery-proof-modal-<?php echo $order_id; ?>">&times;</span>
                <h3>Completar Entrega: <?php echo esc_html($codigo_pedido); ?></h3>
                <form class="complete-delivery-form" data-order-id="<?php echo $order_id; ?>" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="foto_comprobante_<?php echo $order_id; ?>">Foto de Comprobante (Opcional):</label>
                        <input type="file" id="foto_comprobante_<?php echo $order_id; ?>" name="foto_comprobante" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="firma_cliente_<?php echo $order_id; ?>">Firma del Cliente (Opcional):</label>
                        <textarea id="firma_cliente_<?php echo $order_id; ?>" name="firma_cliente" rows="3" placeholder="Ingresar el nombre del cliente que firma o descripción de la firma..."></textarea>
                    </div>
                    <button type="submit" class="ghd-btn ghd-btn-success" style="margin-top: 20px;"><i class="fa-solid fa-check"></i> Marcar como Entregado</button>
                </form>
            </div>
        </div>

    <?php
        endwhile;
    else : 
        error_log('ghd_refresh_fletero_tasks_callback: No hay entregas para el usuario ' . $current_user_id); // DEBUG
    ?>
        <p class="no-tasks-message" style="text-align: center; padding: 20px;">No tienes entregas asignadas actualmente.</p>
    <?php endif; wp_reset_postdata(); 
    
    $fletero_tasks_html = ob_get_clean();
    error_log('ghd_refresh_fletero_tasks_callback: HTML generado. Enviando éxito.'); // DEBUG
    // --- NUEVO: Enviar la respuesta JSON con el formato esperado por el frontend ---
    wp_send_json_success([
        'tasks_html' => $fletero_tasks_html,
        'message' => 'Entregas actualizadas.' // Mensaje de éxito si lo quieres mantener
    ]);
    // --- FIN NUEVO ---
    wp_die();
}// fin ghd_refresh_fletero_tasks_callback()
///////////////////////////////////////////////////////////// 


/**
 * Ayuda a determinar el rol del usuario, el sector asociado, si es líder, y los operarios del sector.
 * @param WP_User $user El objeto del usuario de WordPress.
 * @return array Información del rol, sector, estado, operarios y si es líder.
 */
function ghd_get_user_role_and_sector_info( $user ) {
    $user_roles = (array) $user->roles;
    $user_role = !empty($user_roles) ? $user_roles[0] : '';
    
    $role_map = ghd_get_mapa_roles_a_campos(); // Obtiene el mapeo de rol a campo ACF
    
    $sector_name = '';
    $campo_estado = '';
    $is_leader = false;
    $operarios_sector = [];
    $base_sector_key = '';

    // Determinar información del sector
    if ( strpos($user_role, 'lider_') !== false ) {
        $base_sector_key = str_replace('lider_', '', $user_role);
        $is_leader = true;
    } elseif ( strpos($user_role, 'operario_') !== false ) {
        $base_sector_key = str_replace('operario_', '', $user_role);
        $is_leader = false;
    } elseif ($user_role === 'control_final_macarena') {
        $base_sector_key = 'control_final';
        $is_leader = true; // Macarena actúa como líder en su panel
    } elseif ($user_role === 'vendedora') {
        $base_sector_key = 'ventas';
        $is_leader = false;
    } elseif ($user_role === 'gerente_ventas') {
        $base_sector_key = 'gerente_ventas';
        $is_leader = true; // Gerente de ventas puede ser considerado líder de su área
    }

    // Mapeo de claves a nombres legibles para el título y la lógica
    $sector_display_map = [ 
        'carpinteria' => 'Carpintería', 'corte' => 'Corte', 'costura' => 'Costura', 
        'tapiceria' => 'Tapicería', 'embalaje' => 'Embalaje', 'logistica' => 'Logística',
        'control_final' => 'Control Final de Pedidos', 
        'ventas' => 'Mis Ventas', 
        'gerente_ventas' => 'Gerencia de Ventas', 
    ];
    $sector_name = $sector_display_map[$base_sector_key] ?? ucfirst(str_replace('_', ' ', $base_sector_key));

    // Obtener el campo de estado ACF si existe un mapeo
    if ($base_sector_key && array_key_exists($user_role, $role_map)) {
        $campo_estado = $role_map[$user_role];
    } elseif ($user_role === 'control_final_macarena') {
        $campo_estado = 'estado_administrativo'; // Para Macarena
    }
    
    // Obtener operarios del sector si el usuario es líder y se determinó una base de sector
    if ($is_leader && !empty($base_sector_key) && $base_sector_key !== 'ventas' && $base_sector_key !== 'gerente_ventas' && $base_sector_key !== 'control_final') {
        $operarios_sector = get_users([
            'role__in' => ['lider_' . $base_sector_key, 'operario_' . $base_sector_key], 
            'orderby'  => 'display_name',
            'order'    => 'ASC'
        ]);
    } elseif ($user_role === 'control_final_macarena') {
        // Macarena necesita ver a todos los que puedan archivar, no operarios de un sector específico
         $operarios_sector = []; // O podrías traer roles de admin/editor si es necesario
    } else {
        $operarios_sector = []; // Por defecto, lista vacía
    }

    return [
        'role' => $user_role,
        'sector_name' => $sector_name,
        'campo_estado' => $campo_estado,
        'is_leader' => $is_leader,
        'operarios_sector' => $operarios_sector
    ];
}// fin ghd_get_user_role_and_sector_info()

// --- NUEVO: Función de ayuda para obtener Modelos de Puntos para selectores ---
/**
 * Obtiene todos los CPT 'ghd_modelo_puntos' para usarlos en un selector.
 * @return array Un array de objetos con 'id', 'title', 'points'.
 */
function ghd_get_embalaje_models_for_select() {
    $models_query = new WP_Query([
        'post_type'      => 'ghd_modelo_puntos',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'fields'         => 'ids' // Solo obtener IDs para optimizar
    ]);

    $models_data = [];
    if ($models_query->have_posts()) {
        foreach ($models_query->posts as $model_id) {
            $model_title = get_the_title($model_id);
            $model_points = (int) get_field('puntos_del_modelo', $model_id); // Obtener los puntos ACF
            $models_data[] = (object) [ // Devolver como objeto para fácil acceso
                'id'    => $model_id,
                'title' => $model_title,
                'points'=> $model_points
            ];
        }
    }
    wp_reset_postdata();
    return $models_data;
}
// --- FIN Función de Ayuda Modelos de Puntos ---

/**
 * AJAX Handler para filtrar los pedidos en el panel del administrador.
 * Maneja la búsqueda por texto, el estado del pedido y la prioridad.
 */
add_action('wp_ajax_ghd_filter_orders', 'ghd_filter_orders_callback');
function ghd_filter_orders_callback() {
    // 1. Verificación de seguridad
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos para realizar esta acción.']);
        wp_die();
    }

    // 2. Obtener y sanitizar los datos de entrada
    $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $priority = isset($_POST['priority']) ? sanitize_text_field($_POST['priority']) : '';

    // 3. Construir los argumentos para WP_Query
    $args = [
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
        'post_status'    => 'publish', // O los estados que necesites
    ];

    // Añadir búsqueda por término si existe
    if (!empty($search_term)) {
        $args['s'] = $search_term;
    }

    // Construir la meta_query para los filtros de estado y prioridad
    $meta_query = ['relation' => 'AND']; // Ambas condiciones deben cumplirse

    if (!empty($status)) {
        $meta_query[] = [
            'key'     => 'estado_pedido',
            'value'   => $status,
            'compare' => '=',
        ];
    }

    if (!empty($priority)) {
        $meta_query[] = [
            'key'     => 'prioridad_pedido',
            'value'   => $priority,
            'compare' => '=',
        ];
    }
    
    // Solo añadir meta_query si tiene más de una condición (además de 'relation')
    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }

    // 4. Ejecutar la consulta y generar el HTML
    $pedidos_query = new WP_Query($args);

    ob_start();

    if ($pedidos_query->have_posts()) {
        while ($pedidos_query->have_posts()) {
            $pedidos_query->the_post();
            
            // Reutilizamos la lógica de `order-row-admin.php` para mantener la consistencia.
            // Primero, preparamos los datos que necesita la plantilla.
            $current_order_id = get_the_ID();
            
            $task_card_args = [
                'post_id'         => $current_order_id,
                'titulo'          => get_the_title(),
                'nombre_cliente'  => get_field('nombre_cliente', $current_order_id),
                'nombre_producto' => get_field('nombre_producto', $current_order_id),
                'estado'          => get_field('estado_pedido', $current_order_id),
                'prioridad'       => get_field('prioridad_pedido', $current_order_id),
                'sector_actual'   => 'N/A', // Puedes calcular esto si lo necesitas
                'fecha_del_pedido'=> get_the_date('d/m/Y', $current_order_id),
                // Añade aquí más variables si `order-row-admin.php` las necesita
            ];

            // Pasamos los datos a la plantilla para que renderice la fila
            get_template_part('template-parts/order-row-admin', null, $task_card_args);
        }
    } else {
        echo '<tr><td colspan="9" style="text-align:center;">No se encontraron pedidos con los filtros seleccionados.</td></tr>';
    }

    wp_reset_postdata();

    $html = ob_get_clean();

    // 5. Enviar la respuesta JSON
    wp_send_json_success(['html' => $html]);
    wp_die();
}


/// // // // // // // // // // // // // / 
/**
 * AJAX Handler para que el control administrativo (Macarena) actualice los datos de cierre del pedido.
 */
add_action('wp_ajax_ghd_admin_final_update', 'ghd_admin_final_update_callback');
function ghd_admin_final_update_callback() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('control_final_macarena') && !current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos para esta acción.']);
        wp_die();
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error(['message' => 'ID de pedido no válido.']);
        wp_die();
    }

    // Actualizar campos de texto y selección
    if (isset($_POST['estado_pago'])) {
        update_field('estado_pago', sanitize_text_field($_POST['estado_pago']), $order_id);
    }
    if (isset($_POST['notas_administrativas'])) {
        update_field('notas_administrativas', sanitize_textarea_field($_POST['notas_administrativas']), $order_id);
    }

    // Manejar subida de archivo de imagen
    if (isset($_FILES['foto_remito_firmado']) && !empty($_FILES['foto_remito_firmado']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('foto_remito_firmado', $order_id);
        if (!is_wp_error($attachment_id)) {
            update_field('foto_remito_firmado', $attachment_id, $order_id);
        } else {
            // Si la subida falla, no es un error fatal, pero se puede notificar
            wp_send_json_error(['message' => 'Datos guardados, pero la foto del remito no se pudo subir: ' . $attachment_id->get_error_message()]);
            wp_die();
        }
    }

    wp_insert_post([
        'post_title' => 'Control Administrativo actualizado por ' . wp_get_current_user()->display_name,
        'post_type'  => 'ghd_historial',
        'meta_input' => ['_orden_produccion_id' => $order_id]
    ]);
    
    wp_send_json_success(['message' => 'Datos administrativos guardados con éxito.']);
    wp_die();
} // fin ghd_admin_final_update_callback()

// --- LÓGICA DE LOGIN, LOGOUT Y REDIRECCIONES (VERSIÓN FINAL UNIFICADA) ---

/**
 * Redirige a los usuarios desde /wp-login.php a la página de login personalizada.
 */
add_action('init', 'ghd_redirect_default_login_page');
function ghd_redirect_default_login_page() {
    // Usamos $GLOBALS['pagenow'] que es más fiable que $_SERVER['SCRIPT_NAME']
    if ($GLOBALS['pagenow'] === 'wp-login.php' && !is_user_logged_in() && !isset($_GET['action'])) {
        $login_page = get_page_by_path('iniciar-sesion');
        if ($login_page) {
            wp_redirect(get_permalink($login_page->ID));
            exit;
        }
    }
}

/**
 * Redirige a los usuarios a su panel correspondiente DESPUÉS de un inicio de sesión exitoso.
 */
add_filter('login_redirect', 'ghd_custom_login_redirect', 10, 3);
function ghd_custom_login_redirect($redirect_to, $requested_redirect_to, $user) {
    if (is_wp_error($user)) {
        return $redirect_to; // Si hay error, devuelve al login
    }

    $user_roles = (array) $user->roles;
    
    // PRIORIDAD DE REDIRECCIÓN (de más específico a general, si un usuario tiene múltiples roles)

    // 1. Administradores van al backend de WordPress
    if (in_array('administrator', $user_roles)) {
        return admin_url();
    }

    // 2. Fleteros van a su panel de entregas
    if (in_array('operario_logistica', $user_roles)) {
        $fletero_page = get_page_by_path('panel-de-fletero'); // <-- ¡ASEGÚRATE DE QUE ESTE SLUG SEA EL REAL!
        if ($fletero_page) {
            return get_permalink($fletero_page->ID);
        }
        // Fallback si la página del fletero no existe
        return home_url(); 
    }

    // 3. Vendedoras y Gerentes de Ventas van a su panel de ventas
    if (in_array('vendedora', $user_roles) || in_array('gerente_ventas', $user_roles)) {
        $sales_page = get_page_by_path('panel-de-ventas'); // <-- ¡ASEGÚRATE DE QUE ESTE SLUG SEA EL REAL!
        if ($sales_page) {
            return get_permalink($sales_page->ID);
        }
        // Fallback si la página de ventas no existe
        return home_url();
    }

    // 4. Líderes de Producción van a su panel general de tareas
    $is_production_leader = false;
    foreach ($user_roles as $role) {
        if (strpos($role, 'lider_') !== false) {
            $is_production_leader = true;
            break;
        }
    }
    if ($is_production_leader) {
        $sector_page = get_page_by_path('mis-tareas'); // <-- ¡ASEGÚRATE DE QUE ESTE SLUG SEA EL REAL!
        if ($sector_page) {
            return get_permalink($sector_page->ID);
        }
        // Fallback si la página de tareas de sector no existe
        return home_url();
    }

    // 5. Control Final (Macarena) va a su panel de control
    if (in_array('control_final_macarena', $user_roles)) {
        $control_page = get_page_by_path('panel-de-control'); // <-- ¡ASEGÚRATE DE QUE ESTE SLUG SEA EL REAL!
        if ($control_page) {
            return get_permalink($control_page->ID);
        }
        // Fallback si la página de control no existe
        return home_url();
    }

    // 6. Fallback final para cualquier otro rol no cubierto (ej. suscriptor)
    return home_url();
} // fin ghd_custom_login_redirect()

/**
 * Redirige al usuario a la página de login personalizada DESPUÉS de cerrar sesión.
 */
add_filter('logout_redirect', 'ghd_custom_logout_redirect', 10, 2);
function ghd_custom_logout_redirect($logout_url, $redirect) {
    $login_page = get_page_by_path('iniciar-sesion');
    if ($login_page) {
        return get_permalink($login_page->ID);
    }
    return home_url(); // Fallback
}

/**
 * Previene el acceso directo al backend de WordPress (/wp-admin/) para usuarios que no son administradores.
 */
add_action('admin_init', 'ghd_prevent_backend_access');
function ghd_prevent_backend_access() {
    // La condición is_admin() asegura que esto solo se ejecute en páginas del backend.
    if (is_admin() && !current_user_can('manage_options') && !(defined('DOING_AJAX') && DOING_AJAX)) {
        wp_redirect(home_url()); // Redirigir a la página de inicio si intentan acceder al backend
        exit;
    }
}

/**
 * Sincroniza el estado del pedido cuando se actualiza el estado administrativo.
 * Si el estado administrativo se pone en "Pendiente", el estado general del pedido
 * se actualiza a "Pendiente de Cierre Admin" para que aparezca en el panel de Macarena.
 */
add_action('acf/save_post', 'ghd_sync_admin_status_on_save', 20);
function ghd_sync_admin_status_on_save($post_id) {
    // Solo ejecutar para nuestro tipo de post
    if (get_post_type($post_id) !== 'orden_produccion') {
        return;
    }

    // Verificar si el campo 'estado_administrativo' fue enviado en el guardado
    if (isset($_POST['acf']['field_68b1a71b7c0b1'])) { // Reemplaza 'field_xxxxxxxxxxxxxx' con la KEY del campo 'estado_administrativo'
        
        $estado_admin = get_field('estado_administrativo', $post_id);

        if ($estado_admin === 'Pendiente') {
            // Para evitar un bucle infinito al guardar, removemos temporalmente la acción
            remove_action('acf/save_post', 'ghd_sync_admin_status_on_save', 20);

            // Actualizamos el campo principal 'estado_pedido'
            update_field('estado_pedido', 'Pendiente de Cierre Admin', $post_id);

            // Volvemos a añadir la acción para futuras ediciones
            add_action('acf/save_post', 'ghd_sync_admin_status_on_save', 20);
        }
    }
}

/**
 * AJAX Handler para refrescar la tabla de Pedidos Archivados.
 */
add_action('wp_ajax_ghd_refresh_archived_orders', 'ghd_refresh_archived_orders_callback');
function ghd_refresh_archived_orders_callback() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('manage_options') && !current_user_can('control_final_macarena')) {
        wp_send_json_error(['message' => 'No tienes permisos.']);
        wp_die();
    }

    ob_start();

    $args_archivados = array(
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
        'meta_query'     => [['key' => 'estado_pedido', 'value' => 'Completado y Archivado']],
        'orderby'        => 'modified',
        'order'          => 'DESC',
    );
    $pedidos_archivados_query = new WP_Query($args_archivados);

    if ($pedidos_archivados_query->have_posts()) :
        while ($pedidos_archivados_query->have_posts()) : $pedidos_archivados_query->the_post();
            $order_id = get_the_ID();
    ?>
            <tr id="order-row-archived-<?php echo $order_id; ?>">
                <td><a href="<?php the_permalink(); ?>" style="color: var(--color-rojo); font-weight: 600;"><?php the_title(); ?></a></td>
                <td><?php echo esc_html(get_field('nombre_cliente', $order_id)); ?></td>
                <td><?php echo esc_html(get_field('nombre_producto', $order_id)); ?></td>
                <td><?php echo get_the_modified_date('d/m/Y H:i', $order_id); ?></td>
                <td>
                    <a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary ghd-btn-small">
                        <i class="fa-solid fa-eye"></i> Ver Detalles
                    </a>
                </td>
            </tr>
    <?php
        endwhile;
    else: 
    ?>
        <tr><td colspan="5" style="text-align:center;">No hay pedidos archivados.</td></tr>
    <?php
    endif;
    wp_reset_postdata(); 

    $html = ob_get_clean();

    wp_send_json_success(['table_html' => $html]);
    wp_die();
} // fin ghd_refresh_archived_orders_callback()

/**
 * AJAX Handler para crear un nuevo pedido desde el popup.
 * V4 - Corregido para un guardado robusto del estado inicial y debugging.
 */
add_action('wp_ajax_ghd_crear_nuevo_pedido', 'ghd_crear_nuevo_pedido_callback');
function ghd_crear_nuevo_pedido_callback() {
    error_log('ghd_crear_nuevo_pedido_callback: Inicio de la función.');

    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        error_log('ghd_crear_nuevo_pedido_callback: Permisos insuficientes.');
        wp_send_json_error(['message' => 'No tienes permisos para crear pedidos.']);
        wp_die();
    }

    $nombre_cliente = sanitize_text_field($_POST['nombre_cliente'] ?? '');
    $nombre_producto = sanitize_text_field($_POST['nombre_producto'] ?? '');
    $cliente_email = sanitize_email($_POST['cliente_email'] ?? '');
    $color_producto = sanitize_text_field($_POST['color_del_producto'] ?? '');
    $direccion_entrega = sanitize_textarea_field($_POST['direccion_de_entrega'] ?? '');

    if (empty($nombre_cliente) || empty($nombre_producto)) {
        error_log('ghd_crear_nuevo_pedido_callback: Cliente o producto vacíos.');
        wp_send_json_error(['message' => 'El nombre del cliente y del producto son obligatorios.']);
        wp_die();
    }

    $post_data = [
        'post_type'   => 'orden_produccion',
        'post_title'  => 'Pedido para ' . $nombre_cliente,
        'post_status' => 'publish',
    ];
    $new_post_id = wp_insert_post($post_data, true);

    if (is_wp_error($new_post_id)) {
        error_log('ghd_crear_nuevo_pedido_callback: Error al insertar el post: ' . $new_post_id->get_error_message());
        wp_send_json_error(['message' => $new_post_id->get_error_message()]);
        wp_die();
    }
    error_log('ghd_crear_nuevo_pedido_callback: Post creado con ID ' . $new_post_id);

    $nuevo_codigo = 'PED-' . date('Y') . '-' . str_pad($new_post_id, 3, '0', STR_PAD_LEFT);
    wp_update_post(['ID' => $new_post_id, 'post_title' => $nuevo_codigo]);
    error_log('ghd_crear_nuevo_pedido_callback: Título y código actualizados: ' . $nuevo_codigo);

    // --- CORRECCIÓN CLAVE: Guardar TODOS los campos ACF de forma robusta ---
    // Usamos add_row o update_field. Si no existen, update_field los crea.
    $fields_to_update = [
        'codigo_de_pedido'       => $nuevo_codigo,
        'nombre_cliente'         => $nombre_cliente,
        'cliente_email'          => $cliente_email,
        'nombre_producto'        => $nombre_producto,
        'color_del_producto'     => $color_producto,
        'direccion_de_entrega'   => $direccion_entrega,
        'estado_pedido'          => 'Pendiente de Asignación', // El estado crucial
        'estado_carpinteria'     => 'No Asignado', // Inicializar los estados de producción
        'estado_corte'           => 'No Asignado',
        'estado_costura'         => 'No Asignado',
        'estado_tapiceria'       => 'No Asignado',
        'estado_embalaje'        => 'No Asignado',
        'estado_logistica'       => 'No Asignado',
        'estado_administrativo'  => 'No Asignado',
        'prioridad_pedido'       => 'Baja', // Establecer una prioridad por defecto
    ];

    foreach ($fields_to_update as $field_name => $value) {
        $update_success = update_field($field_name, $value, $new_post_id);
        if (!$update_success) {
            error_log("ghd_crear_nuevo_pedido_callback: FALLO al guardar ACF '{$field_name}' con valor '{$value}'.");
        } else {
            error_log("ghd_crear_nuevo_pedido_callback: ÉXITO al guardar ACF '{$field_name}' con valor '{$value}'.");
        }
    }
    // --- FIN CORRECCIÓN CLAVE ---

    // Generar el HTML de la fila de la tabla para devolverlo
    ob_start();
    
    $vendedoras_users = get_users(['role__in' => ['vendedora', 'gerente_ventas'], 'orderby' => 'display_name']);
    ?>
    <tr id="order-row-<?php echo $new_post_id; ?>">
        <td><a href="<?php echo get_permalink($new_post_id); ?>" style="color: var(--color-rojo); font-weight: 600;"><?php echo esc_html($nuevo_codigo); ?></a></td>
        <td><?php echo esc_html($nombre_cliente); ?></td>
        <td><?php echo esc_html($nombre_producto); ?></td>
        <td>
            <select class="ghd-vendedora-selector" data-order-id="<?php echo $new_post_id; ?>">
                <option value="0">Asignar Vendedora</option>
                <?php foreach ($vendedoras_users as $vendedora) : ?>
                    <option value="<?php echo esc_attr($vendedora->ID); ?>"><?php echo esc_html($vendedora->display_name); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <select class="ghd-priority-selector" data-order-id="<?php echo $new_post_id; ?>">
                <option value="" selected>Seleccionar Prioridad</option> <!-- Corregido valor por defecto -->
                <option value="Alta">Alta</option>
                <option value="Media">Media</option>
                <option value="Baja">Baja</option>
            </select>
        </td>
        <td>
            <button class="ghd-btn ghd-btn-primary start-production-btn" data-order-id="<?php echo $new_post_id; ?>" disabled>
                Iniciar Producción
            </button>
        </td>
    </tr>
    <?php
    $new_row_html = ob_get_clean();

    error_log('ghd_crear_nuevo_pedido_callback: Función finalizada con éxito. Devolviendo HTML.');
    wp_send_json_success([
        'message' => '¡Pedido ' . $nuevo_codigo . ' creado con éxito!',
        'new_row_html' => $new_row_html
    ]);
    wp_die();
}// fin ghd_crear_nuevo_pedido_callback()

// --- Función de ayuda para eliminar tildes (acentos) ---
if ( ! function_exists( 'remove_accents' ) ) {
    function remove_accents( $string ) {
        if ( ! preg_match( '/[\x80-\xff]/', $string ) ) {
            return $string;
        }
        $chars = array(
            // Decompositions for Latin-1 Supplement
            chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
            chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
            chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
            chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
            chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
            chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
            chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
            chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
            chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
            chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
            chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
            chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
            chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
            chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
            chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
            chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
            chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
            chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
            chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
            chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
            chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
            chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
            chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
            chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
            chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
            chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
            chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
            chr(195).chr(191) => 'y',
            // Decompositions for Latin Extended-A
            chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
            chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
            chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
            chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
            chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
            chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
            chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
            chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
            chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
            chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
            chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
            chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
            chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
            chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
            chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
            chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
            chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
            chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
            chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
            chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
            chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
            chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
            chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
            chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
            chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
            chr(196).chr(178) => 'J', chr(196).chr(179) => 'j',
            chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
            chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
            chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
            chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
            chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
            chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
            chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
            chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
            chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
            chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
            chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
            chr(197).chr(138) => 'n', chr(197).chr(139) => 'O',
            chr(197).chr(140) => 'o', chr(197).chr(141) => 'O',
            chr(197).chr(142) => 'o', chr(197).chr(143) => 'O',
            chr(197).chr(144) => 'o', chr(197).chr(145) => 'R',
            chr(197).chr(146) => 'r', chr(197).chr(147) => 'R',
            chr(197).chr(148) => 'r', chr(197).chr(149) => 'R',
            chr(197).chr(150) => 'r', chr(197).chr(151) => 'S',
            chr(197).chr(152) => 's', chr(197).chr(153) => 'S',
            chr(197).chr(154) => 's', chr(197).chr(155) => 'S',
            chr(197).chr(156) => 's', chr(197).chr(157) => 'S',
            chr(197).chr(158) => 's', chr(197).chr(159) => 'T',
            chr(197).chr(160) => 't', chr(197).chr(161) => 'T',
            chr(197).chr(162) => 't', chr(197).chr(163) => 'T',
            chr(197).chr(164) => 't', chr(197).chr(165) => 'U',
            chr(197).chr(166) => 'u', chr(197).chr(167) => 'U',
            chr(197).chr(168) => 'u', chr(197).chr(169) => 'U',
            chr(197).chr(170) => 'u', chr(197).chr(171) => 'U',
            chr(197).chr(172) => 'u', chr(197).chr(173) => 'U',
            chr(197).chr(174) => 'u', chr(197).chr(175) => 'U',
            chr(197).chr(176) => 'u', chr(197).chr(177) => 'W',
            chr(197).chr(178) => 'w', chr(197).chr(179) => 'Y',
            chr(197).chr(180) => 'y', chr(197).chr(181) => 'Y',
            chr(197).chr(182) => 'Z', chr(197).chr(183) => 'z',
            chr(197).chr(184) => 'Z', chr(197).chr(185) => 'z',
            chr(197).chr(186) => 'Z', chr(197).chr(187) => 'z',
            chr(197).chr(191) => 's'
        );
        $string = strtr($string, $chars);
        return $string;
    }
} // fin remove_accents()

// --- NUEVO: Deshabilitar campos ACF específicos en el backend (solo lectura visual) ---
add_action('acf/input/admin_enqueue_scripts', 'ghd_disable_acf_fields_for_readonly');
function ghd_disable_acf_fields_for_readonly() {
    // Lista de nombres de campos ACF que deseas deshabilitar para edición
    // Asegúrate de usar los 'field_name' (slug) de tus campos.
    $readonly_fields = [
        'fecha_recogido',
        'fecha_entregado',
        'logistica_firma_cliente',
        'logistica_foto_comprobante',
        'embalaje_operario_id',
        'embalaje_modelo_id',
        'embalaje_cantidad',
        'embalaje_puntos_tarea',
        // Añade aquí cualquier otro campo que quieras que sea de solo lectura
    ];

    if (empty($readonly_fields)) {
        return;
    }

    // Script JavaScript para deshabilitar los campos
    $script = '
    jQuery(document).ready(function($) {
        var readonly_fields = ' . json_encode($readonly_fields) . ';
        
        readonly_fields.forEach(function(field_name) {
            // Encuentra el campo por su nombre y deshabilita el input/select/textarea
            // Esto apunta a los elementos input/select/textarea dentro de los wraps de ACF
            var $field_input = $(\'.acf-field[data-name="\' + field_name + \'"] input, .acf-field[data-name="\' + field_name + \'"] select, .acf-field[data-name="\' + field_name + \'"] textarea\');
            if ($field_input.length) {
                $field_input.prop("disabled", true);
                // Opcional: añadir una clase para estilos visuales de "solo lectura"
                $field_input.closest(".acf-input").addClass("ghd-readonly-acf-field");
            }
        });
    });
    ';

    wp_add_inline_script('acf-input', $script);
}// fin ghd_disable_acf_fields_for_readonly()
// --- FIN Deshabilitar campos ACF ---

// --- NUEVO: Mostrar puntos de embalaje en el perfil de usuario (Backend) ---

// Añadir campos extra a la página de perfil de usuario
add_action( 'show_user_profile', 'ghd_show_embalaje_points_on_profile' );
add_action( 'edit_user_profile', 'ghd_show_embalaje_points_on_profile' );
function ghd_show_embalaje_points_on_profile( $user ) {
    // Solo mostrar para operarios de embalaje, líderes de embalaje, o administradores
    if ( ! current_user_can('lider_embalaje') && ! in_array('operario_embalaje', (array)$user->roles) && ! current_user_can('manage_options') ) {
        return;
    }
    ?>
    <h3><?php _e('Puntos de Embalaje (GHD)', 'textdomain'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="ghd_total_puntos_embalaje"><?php _e('Total de Puntos de Embalaje', 'textdomain'); ?></label></th>
            <td>
                <input type="text" name="ghd_total_puntos_embalaje_display" id="ghd_total_puntos_embalaje_display" value="<?php echo esc_attr( get_user_meta( $user->ID, 'ghd_total_puntos_embalaje', true ) ?: '0' ); ?>" class="regular-text" readonly="readonly" />
                <p class="description"><?php _e('Puntos acumulados por tareas de embalaje. Solo lectura.', 'textdomain'); ?></p>
            </td>
        </tr>
        <!-- Aquí podrías añadir la meta diaria de 25 puntos como referencia -->
        <tr>
            <th><label><?php _e('Meta Diaria', 'textdomain'); ?></label></th>
            <td>
                <p>25 puntos</p>
                <p class="description"><?php _e('Meta diaria establecida para operarios de embalaje.', 'textdomain'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

// Para asegurar que el campo deshabilitado no se guarde si alguien lo manipula (solo se actualiza vía AJAX)
add_action( 'personal_options_update', 'ghd_save_embalaje_points_on_profile' );
add_action( 'edit_user_profile_update', 'ghd_save_embalaje_points_on_profile' );
function ghd_save_embalaje_points_on_profile( $user_id ) {
    // No hacer nada aquí, ya que los puntos se actualizan exclusivamente vía AJAX del sistema.
    // Solo se aseguran los permisos si es que se manipula algo desde aquí.
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }
    // Si quisieras permitir que un admin lo edite manualmente, descomentar:
    // if ( current_user_can( 'manage_options' ) && isset( $_POST['ghd_total_puntos_embalaje_display'] ) ) {
    //     update_user_meta( $user_id, 'ghd_total_puntos_embalaje', sanitize_text_field( $_POST['ghd_total_puntos_embalaje_display'] ) );
    // }
}

// --- FIN Puntos de Embalaje en Perfil ---