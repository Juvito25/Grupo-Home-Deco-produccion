<?php
/**
 * functions.php - Versión 3.3 - Corrección de Redirección de Login (Final)
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

// --- 3. FUNCIONES DE AYUDA ---
function ghd_get_sectores() { 
    return ['Carpintería', 'Corte', 'Costura', 'Tapicería', 'Embalaje', 'Logística']; 
}
function ghd_get_mapa_roles_a_campos() {
    return [
        'rol_carpinteria' => 'estado_carpinteria', 
        'rol_corte' => 'estado_corte', 
        'rol_costura' => 'estado_costura', 
        'rol_tapiceria' => 'estado_tapiceria', 
        'rol_embalaje' => 'estado_embalaje', 
        'rol_logistica' => 'estado_logistica',
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

/**
 * Función para obtener los datos de pedidos en producción y sus KPIs.
 * Reutilizable para la carga inicial y las respuestas AJAX.
 * @return array Un array con 'tasks_html' (HTML) y 'kpi_data'.
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
        'tiempo_promedio_str_produccion'  => '0.0h',
        'completadas_hoy_produccion'      => 0,
    ];

    $total_tiempo_produccion = 0;
    $ahora = current_time('U');
    $mapa_roles_a_campos = ghd_get_mapa_roles_a_campos();

    ob_start();
    if ($pedidos_query->have_posts()) :
        while ($pedidos_query->have_posts()) : $pedidos_query->the_post();
            $order_id = get_the_ID();
            $prioridad = get_field('prioridad_pedido', $order_id);
            if ($prioridad === 'Alta') {
                $kpi_data['total_prioridad_alta_produccion']++;
            }

            $produccion_iniciada_time = get_post_meta($order_id, 'historial_produccion_iniciada_timestamp', true);

            if ($produccion_iniciada_time) {
                $total_tiempo_produccion += $ahora - $produccion_iniciada_time;
            } else {
                $total_tiempo_produccion += $ahora - get_the_modified_time('U', $order_id);
            }

            // --- CAMPOS ADICIONALES RECUPERADOS ---
            $material_producto = get_field('material_del_producto', $order_id); 
            $color_producto = get_field('color_del_producto', $order_id);    
            $observaciones_personalizacion = get_field('observaciones_personalizacion', $order_id); 
            ?>
            <tr id="order-row-prod-<?php echo $order_id; ?>">
                <td><a href="<?php the_permalink(); ?>" style="color: var(--color-rojo); font-weight: 600;"><?php the_title(); ?></a></td>
                <td><?php echo esc_html(get_field('nombre_cliente', $order_id)); ?></td>
                <td><?php echo esc_html(get_field('nombre_producto', $order_id)); ?></td>
                <td><?php echo esc_html($material_producto); ?></td>
                <td>
                    <?php if ($color_producto) : ?>
                        <span class="color-swatch" style="background-color: <?php echo esc_attr($color_producto); ?>;"></span>
                        <?php echo esc_html($color_producto); ?>
                    <?php else : ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td class="production-observations"><?php echo nl2br(esc_html($observaciones_personalizacion)); ?></td>
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
                            // Solo mostrar si tiene un estado relevante
                            if ($sub_estado !== 'No Asignado') {
                            ?>
                            <span class="ghd-badge <?php echo esc_attr($badge_class); ?>">
                                <?php echo ucfirst(str_replace('estado_', '', str_replace('rol_', '', $role_key))); ?>: <?php echo esc_html($sub_estado); ?>
                            </span>
                            <?php
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
        <tr><td colspan="9" style="text-align:center;">No hay pedidos actualmente en producción.</td></tr>
    <?php endif;
    wp_reset_postdata();
    $production_tasks_html = ob_get_clean();

    if ($kpi_data['total_pedidos_produccion'] > 0) {
        $promedio_horas = ($total_tiempo_produccion / $kpi_data['total_pedidos_produccion']) / 3600;
        $kpi_data['tiempo_promedio_str_produccion'] = number_format($promedio_horas, 1) . 'h';
    }

    $today_start = strtotime('today', current_time('timestamp', true));
    $today_end   = strtotime('tomorrow - 1 second', current_time('timestamp', true));

    $completed_production_today_args = [
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => 'estado_pedido',
                'value'   => 'Pendiente de Cierre Admin', 
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


// --- 4. LÓGICA DE LOGIN/LOGOUT (CORREGIDA FINALMENTE) ---

/**
 * Redirige a los usuarios a sus paneles específicos DESPUÉS de un inicio de sesión exitoso.
 * Los administradores de WP no son redirigidos por esta función si van a /wp-admin.
 */
add_filter('login_redirect', 'ghd_custom_login_redirect', 10, 3);
function ghd_custom_login_redirect($redirect_to, $request, $user) {
    // Si el usuario es un administrador de WP (y no va a /wp-admin), ir al dashboard admin
    if (isset($user->roles) && is_array($user->roles) && in_array('administrator', (array) $user->roles)) {
        // Redirigir al dashboard de admin principal
        $admin_pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-admin-dashboard.php']);
        return !empty($admin_pages) ? get_permalink($admin_pages[0]) : admin_url(); // Fallback: /wp-admin para admin
    } 
    // Para cualquier otro rol logueado, redirigir a su dashboard de sector
    elseif (isset($user->roles) && is_array($user->roles)) { // Confirma que tiene roles
        $sector_pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-sector-dashboard.php']);
        $sector_dashboard_url = !empty($sector_pages) ? get_permalink($sector_pages[0]) : home_url(); // Fallback: home
        
        // Añadir el parámetro de sector si el usuario tiene un rol de sector
        $user_roles = $user->roles;
        $user_role = !empty($user_roles) ? $user_roles[0] : ''; // Tomar el primer rol
        $mapa_roles = ghd_get_mapa_roles_a_campos();
        if (array_key_exists($user_role, $mapa_roles)) {
            $sector_name = ucfirst(str_replace(['rol_', '_'], ' ', $user_role));
            $clean_sector_name = strtolower(str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $sector_name));
            return add_query_arg('sector', urlencode($clean_sector_name), $sector_dashboard_url);
        } else {
            return $sector_dashboard_url; // Si no es un rol de sector mapeado, va al dashboard base
        }
    }
    
    return $redirect_to; // Por defecto, devolver la redirección de WordPress si no aplica nada
}

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
        'meta_value' => 'template-login.php' // Asumo que tu plantilla de login se llama template-login.php
    ]);
    $custom_login_url = !empty($login_page_query) ? get_permalink($login_page_query[0]) : home_url('/iniciar-sesion/');
    
    // Si viene de una URL que no es wp-login ni wp-admin, redirigir al custom login con error
    // Esto asegura que el mensaje de error se muestre en tu formulario personalizado
    if (!empty($_SERVER['HTTP_REFERER']) && !strpos($_SERVER['HTTP_REFERER'], 'wp-login') && !strpos($_SERVER['HTTP_REFERER'], 'wp-admin')) {
        wp_redirect($custom_login_url . '?login=failed');
        exit();
    }
    // Si no hay referrer o ya viene de wp-login/wp-admin, el redirect_wp_login_to_custom_page se encargará.
}

/**
 * Oculta la Admin Bar en el Frontend para TODOS los usuarios.
 */
add_action('after_setup_theme', 'ghd_hide_admin_bar');
function ghd_hide_admin_bar() {
    // Si NO estamos en el área de administración de WordPress, ocultar la barra.
    if (!is_admin()) {
        show_admin_bar(false);
    }
}

/**
 * Redirige los accesos a wp-login.php y /wp-admin a la página de login personalizada.
 * Excepto para administradores logueados que acceden a /wp-admin.
 * También permite el procesamiento de logout sin redirecciones conflictivas.
 */
add_action('init', 'ghd_redirect_wp_login_to_custom_page');
function ghd_redirect_wp_login_to_custom_page() {
    // Obtener la URL de tu página de login personalizada
    $login_page_query = get_posts([
        'post_type'  => 'page',
        'fields'     => 'ids',
        'nopaging'   => true,
        'meta_key'   => '_wp_page_template',
        'meta_value' => 'template-login.php'
    ]);
    $custom_login_url = !empty($login_page_query) ? get_permalink($login_page_query[0]) : wp_login_url(); // Fallback

    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $is_wp_login = strpos($current_url, 'wp-login.php') !== false;
    $is_wp_admin = strpos($current_url, 'wp-admin') !== false;
    $is_custom_login_page = strpos($current_url, untrailingslashit($custom_login_url)) !== false; 
    
    // --- CLAVE: Permitir la acción de logout sin interferencia de redirección ---
    if (isset($_GET['action']) && $_GET['action'] === 'logout') {
        return; // Permitir que WordPress procese el logout y luego el filtro 'logout_redirect' actúe
    }

    // Si el usuario ya está logueado:
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        // Si es un administrador, permitimos que acceda a /wp-admin
        if (in_array('administrator', (array) $user->roles)) {
            return; // NO redirigir al administrador logueado
        }
        
        // Para cualquier otro rol logueado (no-admin), si está intentando acceder a /wp-admin o wp-login.php,
        // lo redirigimos a su dashboard de sector.
        // Asegurarse de no redirigir AJAX o CRON requests
        if (($is_wp_admin || $is_wp_login) && !defined('DOING_AJAX') && !defined('DOING_CRON')) { 
            $sector_pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-sector-dashboard.php']);
            $sector_dashboard_url = !empty($sector_pages) ? get_permalink($sector_pages[0]) : home_url(); // Fallback: home
            
            $user_roles = $user->roles;
            $user_role = !empty($user_roles) ? $user_roles[0] : '';
            $mapa_roles = ghd_get_mapa_roles_a_campos();
            if (array_key_exists($user_role, $mapa_roles)) {
                $sector_name = ucfirst(str_replace(['rol_', '_'], ' ', $user_role));
                $clean_sector_name = strtolower(str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $sector_name));
                $final_redirect_url = add_query_arg('sector', urlencode($clean_sector_name), $sector_dashboard_url);
                wp_redirect($final_redirect_url);
                exit();
            } else {
                wp_redirect($sector_dashboard_url);
                exit();
            }
        }
        return; // Si está logueado y no está en wp-admin/wp-login, no hacer nada (ej. si está en una página del frontend).
    }
    
    // Si el usuario NO está logueado y está en wp-login.php o /wp-admin (y no es ya la página de login personalizada)
    // Asegurarse de no redirigir AJAX o CRON requests
    if (!is_user_logged_in() && ($is_wp_login || $is_wp_admin) && !$is_custom_login_page && !defined('DOING_AJAX') && !defined('DOING_CRON') ) {
        wp_redirect( $custom_login_url );
        exit();
    }
}

// --- 5. LÓGICA AJAX ---
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
         // --- NUEVO: Obtener la prioridad seleccionada desde el frontend ---
        $selected_priority = isset($_POST['priority']) ? sanitize_text_field($_POST['priority']) : '';
        
        // Opcional: Requerir que una prioridad válida sea seleccionada antes de iniciar
        if (empty($selected_priority) || $selected_priority === 'Seleccionar Prioridad') {
            wp_send_json_error(['message' => 'Por favor, selecciona una prioridad para el pedido antes de iniciar la producción.']);
        }
        // Actualizar la prioridad en el campo de ACF si es diferente o no estaba guardada
        if (get_field('prioridad_pedido', $id) !== $selected_priority) {
            update_field('prioridad_pedido', $selected_priority, $id);
            wp_insert_post(['post_title' => 'Prioridad fijada a ' . $selected_priority . ' al iniciar producción.', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id, '_nueva_prioridad' => $selected_priority]]);
        }
        // --- FIN NUEVO BLOQUE ---

        update_field('estado_carpinteria', 'Pendiente', $id);
        update_field('estado_corte', 'Pendiente', $id);
        update_field('estado_pedido', 'En Producción', $id);
        update_post_meta($id, 'historial_produccion_iniciada_timestamp', current_time('U'));

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
        // Regla 1: Carpintería y Corte -> Costura (SIN CAMBIOS, YA ESPERA A AMBOS)
        if (get_field('estado_carpinteria', $id) == 'Completado' && get_field('estado_corte', $id) == 'Completado' && get_field('estado_costura', $id) == 'No Asignado') {
            update_field('estado_costura', 'Pendiente', $id); update_field('estado_pedido', 'En Costura', $id);
            wp_insert_post(['post_title' => 'Fase 1 completa (Carpintería y Corte) -> A Costura', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
        }
        // Regla 2: Costura Y Carpintería -> Tapicería y Embalaje (AJUSTADA)
        if (get_field('estado_costura', $id) == 'Completado' && get_field('estado_carpinteria', $id) == 'Completado' && get_field('estado_tapiceria', $id) == 'No Asignado') {
            update_field('estado_tapiceria', 'Pendiente', $id); update_field('estado_embalaje', 'Pendiente', $id);
            update_field('estado_pedido', 'En Tapicería/Embalaje', $id);
            wp_insert_post(['post_title' => 'Fase Costura completa (y Carpintería) -> A Tapicería/Embalaje', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
        }
        // Regla 3: Tapicería y Embalaje -> Logística (SIN CAMBIOS, YA ESPERA A AMBOS)
        if (get_field('estado_tapiceria', $id) == 'Completado' && get_field('estado_embalaje', $id) == 'Completado' && get_field('estado_logistica', $id) == 'No Asignado') {
            update_field('estado_logistica', 'Pendiente', $id); update_field('estado_pedido', 'Listo para Entrega', $id);
            wp_insert_post(['post_title' => 'Fase Tapicería/Embalaje completa -> A Logística', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
        }
        // Regla 4: Logística -> Pendiente de Cierre Admin (SIN CAMBIOS)
        if (get_field('estado_logistica', $id) == 'Completado' && get_field('estado_pedido', $id) !== 'Pendiente de Cierre Admin') {
            update_field('estado_pedido', 'Pendiente de Cierre Admin', $id);
            update_field('estado_administrativo', 'Listo para Archivar', $id);
            wp_insert_post(['post_title' => 'Entrega Completada -> Pendiente de Cierre Admin', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
        }

        wp_send_json_success(['message' => 'Tarea completada.', 'kpi_data' => $sector_kpi_data]);
    } else {
        ob_start();
        $prioridad_pedido = get_field('prioridad_pedido', $id);
        $prioridad_class = '';
        if ($prioridad_pedido === 'Alta') { $prioridad_class = 'prioridad-alta'; } elseif ($prioridad_pedido === 'Media') { $prioridad_class = 'prioridad-media'; } else { $prioridad_class = 'prioridad-baja'; }
        $task_card_args = [
            'post_id'         => $id, 'titulo'          => get_the_title($id),
            'prioridad_class' => $prioridad_class, 'prioridad'       => $prioridad_pedido,
            'nombre_cliente'  => get_field('nombre_cliente', $id), 'nombre_producto' => get_field('nombre_producto', $id),
            'permalink'       => get_permalink($id), 'campo_estado'    => $field, 'estado_actual'   => $value, 
        ];
        get_template_part('template-parts/task-card', null, $task_card_args);
        $html = ob_get_clean();
        wp_send_json_success(['message' => 'Estado actualizado.', 'html' => $html, 'kpi_data' => $sector_kpi_data]);
    }
    wp_die();
});


// --- MANEJADOR AJAX PARA ARCHIVAR PEDIDOS (AHORA LLAMADO POR EL ADMIN PRINCIPAL) ---
add_action('wp_ajax_ghd_archive_order', 'ghd_archive_order_callback');
function ghd_archive_order_callback() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    // Ahora, solo el Admin principal puede archivar
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos para archivar pedidos.']);
    }
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error(['message' => 'ID de pedido no válido.']);
    }

    // Actualizar los campos a archivado
    update_field('estado_administrativo', 'Archivado', $order_id); // Seguimos usando este campo para el estado final
    update_field('estado_pedido', 'Completado y Archivado', $order_id);
    wp_insert_post([ 'post_title' => 'Pedido Cerrado y Archivado', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $order_id] ]);

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

    $remito_page_id = get_posts([
        'post_type'  => 'page',
        'fields'     => 'ids',
        'nopaging'   => true,
        'meta_key'   => '_wp_page_template',
        'meta_value' => 'template-remito.php'
    ]);
    $remito_base_url = !empty($remito_page_id) ? get_permalink($remito_page_id[0]) : home_url();

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
                    <button class="ghd-btn ghd-btn-primary archive-order-btn" data-order-id="<?php echo $order_id; ?>">
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
 * AJAX Handler para refrescar la sección de Pedidos Archivados.
 */
add_action('wp_ajax_ghd_refresh_archived_orders', 'ghd_refresh_archived_orders_callback');
function ghd_refresh_archived_orders_callback() {
    check_ajax_referer('ghd-ajax-nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'No tienes permisos.']);

    ob_start();
    $args_archivados = array(
        'post_type'      => 'orden_produccion',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'estado_pedido',
                'value'   => 'Completado y Archivado',
                'compare' => '=',
            ),
        ),
        'orderby' => 'modified',
        'order'   => 'DESC',
    );
    $pedidos_archivados_query = new WP_Query($args_archivados);

    $remito_page_id = get_posts([
        'post_type'  => 'page',
        'fields'     => 'ids',
        'nopaging'   => true,
        'meta_key'   => '_wp_page_template',
        'meta_value' => 'template-remito.php'
    ]);
    $remito_base_url = !empty($remito_page_id) ? get_permalink($remito_page_id[0]) : home_url();

    if ($pedidos_archivados_query->have_posts()) :
        while ($pedidos_archivados_query->have_posts()) : $pedidos_archivados_query->the_post();
            $order_id = get_the_ID();
            $remito_url = esc_url( add_query_arg( 'order_id', $order_id, $remito_base_url ) );
        ?>
            <tr id="order-row-archived-<?php echo $order_id; ?>">
                <td><a href="<?php the_permalink(); ?>" style="color: var(--color-rojo); font-weight: 600;"><?php the_title(); ?></a></td>
                <td><?php echo esc_html(get_field('nombre_cliente', $order_id)); ?></td>
                <td><?php echo esc_html(get_field('nombre_producto', $order_id)); ?></td>
                <td><?php echo get_the_modified_date('d/m/Y', $order_id); ?></td>
                <td>
                    <a href="<?php echo $remito_url; ?>" target="_blank" class="ghd-btn ghd-btn-secondary ghd-btn-small generate-remito-btn" data-order-id="<?php echo $order_id; ?>">
                        <i class="fa-solid fa-file-invoice"></i> Remito
                    </a>
                    <a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary ghd-btn-small">
                        <i class="fa-solid fa-eye"></i> Detalles
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
    $archived_orders_html = ob_get_clean();

    wp_send_json_success([
        'message' => 'Pedidos archivados actualizados.',
        'table_html' => $archived_orders_html
    ]);
    wp_die();
}

/**
 * Redirige a la página de inicio de sesión personalizada después del logout.
 */
add_filter('logout_redirect', 'ghd_custom_logout_redirect', 10, 2);
function ghd_custom_logout_redirect($logout_redirect, $requested_redirect_to) {
    // Obtener la URL de tu página de login personalizada
    $login_page_query = get_posts([
        'post_type'  => 'page',
        'fields'     => 'ids',
        'nopaging'   => true,
        'meta_key'   => '_wp_page_template',
        'meta_value' => 'template-login.php' // Asumo que tu plantilla de login se llama template-login.php
    ]);
    $custom_login_url = !empty($login_page_query) ? get_permalink($login_page_query[0]) : wp_login_url(); // Fallback
    
    // Siempre redirigir a la página de login personalizada
    return $custom_login_url;
}