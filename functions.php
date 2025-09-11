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
    }
}
/////// Fin registro roles personalizados /////////// Fin registro roles personalizados //////

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
            // --- NUEVOS CAMPOS DE ASIGNACIÓN/COMPLETADO Y VENDEDORA ---
            $vendedora_asignada_id = get_field('vendedora_asignada', $order_id);
            $vendedora_obj = $vendedora_asignada_id ? get_userdata($vendedora_asignada_id) : null;
            $vendedora_name = $vendedora_obj ? $vendedora_obj->display_name : 'N/A';
            // --- FIN NUEVOS ---
            ?>
            <tr id="order-row-prod-<?php echo $order_id; ?>">
                <td><a href="<?php the_permalink(); ?>" style="color: var(--color-rojo); font-weight: 600;"><?php the_title(); ?></a></td>
                <td><?php echo esc_html(get_field('nombre_cliente', $order_id)); ?></td>
                <td><?php echo esc_html($vendedora_name); ?></td> <!-- NUEVA COLUMNA para vendedora -->
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
                                        </div>
                </td>
                <td>
                    <div class="assigned-completed-info">
                        <?php 
                        foreach ($mapa_roles_a_campos as $role_key => $field_key) {
                            $assignee_field = str_replace('estado_', 'asignado_a_', $field_key); // ej. asignado_a_carpinteria
                            $completed_by_field = str_replace('estado_', 'completado_por_', $field_key); // ej. completado_por_carpinteria

                            $assignee_id = get_field($assignee_field, $order_id);
                            $completed_by_id = get_field($completed_by_field, $order_id);

                            $assignee_obj = $assignee_id ? get_userdata($assignee_id) : null;
                            $completed_by_obj = $completed_by_id ? get_userdata($completed_by_id) : null;

                            $sector_label = ucfirst(str_replace('estado_', '', str_replace('rol_', '', $role_key)));
                            
                            // Solo mostrar si hay una asignación o un completado
                            if ($assignee_obj || $completed_by_obj) {
                                echo '<p><strong>' . esc_html($sector_label) . ':</strong></p>';
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
                </td>
                <td><a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary ghd-btn-small">Ver</a></td>
            </tr>
            <?php
        endwhile;
    else : ?>
        <tr><td colspan="10" style="text-align:center;">No hay pedidos actualmente en producción.</td></tr>
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
 * Redirige los accesos a wp-login.php y /wp-admin a la página de login personalizada.
 * Excepto para administradores logueados.
 * Permite que el procesamiento de logout se complete.
 */
add_action('init', 'ghd_redirect_wp_login_to_custom_page');
function ghd_redirect_wp_login_to_custom_page() {
    // No redirigir si es una petición AJAX o CRON, o si se está procesando un logout.
    if (defined('DOING_AJAX') || defined('DOING_CRON') || (isset($_GET['action']) && $_GET['action'] === 'logout')) {
        return;
    }

    $login_page_query = get_posts([
        'post_type'  => 'page',
        'fields'     => 'ids',
        'nopaging'   => true,
        'meta_key'   => '_wp_page_template',
        'meta_value' => 'template-login.php'
    ]);
    $custom_login_url = !empty($login_page_query) ? get_permalink($login_page_query[0]) : wp_login_url();

    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $is_wp_login = strpos($current_url, 'wp-login.php') !== false;
    $is_wp_admin = strpos($current_url, 'wp-admin') !== false;
    $is_custom_login_page = strpos($current_url, untrailingslashit($custom_login_url)) !== false;

    // Si el usuario ya está logueado:
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        // Si es un administrador, permitimos que acceda a /wp-admin
        if (in_array('administrator', (array) $user->roles)) {
            return; // No redirigir al administrador logueado
        }
        
        // Para cualquier otro rol logueado (no-admin), si está en /wp-admin o wp-login.php, redirigir a su dashboard de sector.
        if ($is_wp_admin || $is_wp_login) {
            $sector_pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-sector-dashboard.php']);
            $sector_dashboard_url = !empty($sector_pages) ? get_permalink($sector_pages[0]) : home_url();
            
            $user_roles = $user->roles;
            $user_role = !empty($user_roles) ? $user_roles[0] : '';
            $mapa_roles = ghd_get_mapa_roles_a_campos();
            if (array_key_exists($user_role, $mapa_roles)) {
                // Ya no añadimos el parámetro ?sector aquí. La plantilla del sector debe detectarlo por el rol.
                wp_redirect($sector_dashboard_url); 
                exit();
            } else {
                wp_redirect($sector_dashboard_url);
                exit();
            }
            // if (array_key_exists($user_role, $mapa_roles)) {
            //     $sector_name = ucfirst(str_replace(['rol_', '_'], ' ', $user_role));
            //     $clean_sector_name = strtolower(str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $sector_name));
            //     $final_redirect_url = add_query_arg('sector', urlencode($clean_sector_name), $sector_dashboard_url);
            //     wp_redirect($final_redirect_url);
            //     exit();
            // } else {
            //     wp_redirect($sector_dashboard_url);
            //     exit();
            // }
        }
        return; // Si está logueado y no está en /wp-admin/wp-login, no hacer nada.
    }
    
    // Si el usuario NO está logueado y está en wp-login.php o /wp-admin (y no es ya la página de login personalizada)
    if (!is_user_logged_in() && ($is_wp_login || $is_wp_admin) && !$is_custom_login_page) {
        wp_redirect( $custom_login_url );
        exit();
    }
}
//////////////////////// FIN DEL LOGIN / LOGOUT //////////////////////////////////////
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
        
        // --- NUEVO: Capturar y guardar la vendedora asignada al iniciar producción ---
        $vendedora_asignada_id = isset($_POST['vendedora_id']) ? intval($_POST['vendedora_id']) : 0;
        if ($vendedora_asignada_id === 0) { // Si el frontend no envió una vendedora válida
            wp_send_json_error(['message' => 'Por favor, selecciona una vendedora para el pedido antes de iniciar la producción.']);
        }
        // Actualizar la vendedora en el campo de ACF si es diferente o no estaba guardada
        if (get_field('vendedora_asignada', $id) !== $vendedora_asignada_id) {
            update_field('vendedora_asignada', $vendedora_asignada_id, $id);
            $vendedora_obj = get_userdata($vendedora_asignada_id);
            $vendedora_name = $vendedora_obj ? $vendedora_obj->display_name : 'ID Desconocido';
            wp_insert_post(['post_title' => 'Vendedora ' . $vendedora_name . ' asignada al iniciar producción.', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id, '_vendedora_id' => $vendedora_asignada_id]]);
        }
        // --- FIN NUEVO ---

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
    if (!current_user_can('read')) wp_send_json_error(['message' => 'No tienes permisos.']);

    $id = intval($_POST['order_id']);
    $field = sanitize_key($_POST['field']); // campo_estado (ej. estado_corte)
    $value = sanitize_text_field($_POST['value']); // nuevo valor (ej. En Progreso, Completado)
    
    // Si el valor es 'Completado', esta función ya no lo maneja directamente.
    // El frontend llamará a ghd_register_task_details_and_complete para eso.
    if ($value === 'Completado') {
        wp_send_json_error(['message' => 'El estado "Completado" debe ser gestionado a través del registro detallado de tarea.']);
        wp_die();
    }

    // Actualizar el estado del campo
    update_field($field, $value, $id);
    wp_insert_post(['post_title' => ucfirst(str_replace(['estado_', '_'], ' ', $field)) . ' -> ' . $value, 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
    
    // Recalcular KPIs del sector DESPUÉS de actualizar el campo
    $sector_kpi_data = ghd_calculate_sector_kpis($field);
    
    // --- Lógica de Transiciones de Flujo (Si el estado actual pasa a En Progreso) ---
    // Esto se mantiene para asegurar que los estados generales avancen
    if ($value === 'En Progreso') {
        // Regla 1 (MODIFICADA): Corte -> Costura (Costura solo espera a Corte)
        if ($field === 'estado_corte' && get_field('estado_corte', $id) == 'Completado' && get_field('estado_costura', $id) == 'No Asignado') {
            update_field('estado_costura', 'Pendiente', $id); 
            update_field('estado_pedido', 'En Costura', $id);
            wp_insert_post(['post_title' => 'Fase Corte completa -> A Costura', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $id]]);
        }
        // ... (otras reglas de transición que no dependen de 'Completado' irían aquí, pero la mayoría sí) ...
    }


    // Si no se completó, devolvemos el HTML de la tarjeta actualizada y los KPIs.
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
    wp_die();
}); ////// fin ghd_update_task_status()

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

/**
 * --- NUEVO ENDPOINT AJAX: Registrar Detalles de Tarea y Marcar como Completada ---
 * Este endpoint maneja la finalización de una tarea de sector, incluyendo datos adicionales.
 */
add_action('wp_ajax_ghd_register_task_details_and_complete', 'ghd_register_task_details_and_complete_callback');
function ghd_register_task_details_and_complete_callback() {
    // Verificación de Nonce y permisos
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ghd-ajax-nonce' ) ) {
        wp_send_json_error( ['message' => 'Nonce de seguridad inválido o faltante.'] );
        wp_die();
    }
    if (!current_user_can('read')) { // Permiso básico para cualquier operario/líder
        wp_send_json_error(['message' => 'No tienes permisos para completar tareas.']);
        wp_die();
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $field_estado_sector = isset($_POST['field']) ? sanitize_key($_POST['field']) : ''; // ej. 'estado_carpinteria'
    $modelo_principal_hecho = isset($_POST['modelo_principal_hecho']) ? sanitize_text_field($_POST['modelo_principal_hecho']) : '';
    $cantidad_principal_hecha = isset($_POST['cantidad_principal_hecha']) ? intval($_POST['cantidad_principal_hecha']) : 0;
    $observaciones_tarea_completa = isset($_POST['observaciones_tarea_completa']) ? sanitize_textarea_field($_POST['observaciones_tarea_completa']) : '';
    // La foto se manejará como un archivo subido, no directamente en $_POST

    if (!$order_id || empty($field_estado_sector)) {
        wp_send_json_error(['message' => 'Datos de tarea incompletos.']);
        wp_die();
    }

    // --- GUARDAR DETALLES DE LA TAREA Y MARCAR COMO COMPLETADA ---
    $current_user_id = get_current_user_id();
    $completed_by_field_name = str_replace('estado_', 'completado_por_', $field_estado_sector); // ej. 'completado_por_carpinteria'
    $modelo_hecho_field_name = str_replace('estado_', '', $field_estado_sector) . '_modelo_principal_hecho'; // ej. 'carpinteria_modelo_principal_hecho'
    $cantidad_hecha_field_name = str_replace('estado_', '', $field_estado_sector) . '_cantidad_principal_hecha'; // ej. 'carpinteria_cantidad_principal_hecha'
    $observaciones_field_name = str_replace('estado_', '', $field_estado_sector) . '_observaciones_tarea_completa'; // ej. 'carpinteria_observaciones_tarea_completa'

    // Actualizar campo de estado
    update_field($field_estado_sector, 'Completado', $order_id);
    // Registrar quién completó la tarea
    if ($current_user_id) {
        update_field($completed_by_field_name, $current_user_id, $order_id);
    }
    // Guardar detalles adicionales
    update_field($modelo_hecho_field_name, $modelo_principal_hecho, $order_id);
    update_field($cantidad_hecha_field_name, $cantidad_principal_hecha, $order_id);
    update_field($observaciones_field_name, $observaciones_tarea_completa, $order_id);

    // --- Manejo de la foto subida (si existe) ---
    $foto_tarea_field_name = str_replace('estado_', '', $field_estado_sector) . '_foto_principal_tarea'; // ej. 'carpinteria_foto_principal_tarea'
    if (isset($_FILES['foto_tarea']) && !empty($_FILES['foto_tarea']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('foto_tarea', $order_id); // El order_id es el parent post
        if (!is_wp_error($attachment_id)) {
            $image_url = wp_get_attachment_url($attachment_id);
            update_field($foto_tarea_field_name, $image_url, $order_id);
        } else {
            error_log("GHD Task Complete Error: Fallo al subir la foto para el pedido #{$order_id} en {$field_estado_sector}: " . $attachment_id->get_error_message());
            // No es un error fatal, pero se registra. La tarea se marca completa.
        }
    }
    
    // Registrar en historial
    wp_insert_post(['post_title' => ucfirst(str_replace('estado_', '', $field_estado_sector)) . ' completado por ' . get_the_author_meta('display_name', $current_user_id), 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $order_id, '_completed_by_user_id' => $current_user_id, '_details' => json_encode(['modelo' => $modelo_principal_hecho, 'cantidad' => $cantidad_principal_hecha])]]);

    // --- Lógica de Transiciones de Flujo (basada en 'Completado') ---
    // Regla 1 (MODIFICADA): Corte -> Costura
    if ($field_estado_sector === 'estado_corte' && get_field('estado_costura', $order_id) == 'No Asignado') {
        update_field('estado_costura', 'Pendiente', $order_id); 
        update_field('estado_pedido', 'En Costura', $order_id);
        wp_insert_post(['post_title' => 'Fase Corte completa -> A Costura', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $order_id]]);
    }
    // Regla 2 (RECONFIRMADA): Costura Y Carpintería -> Tapicería y Embalaje
    if ($field_estado_sector === 'estado_costura' && get_field('estado_carpinteria', $order_id) == 'Completado' && get_field('estado_tapiceria', $order_id) == 'No Asignado') {
        update_field('estado_tapiceria', 'Pendiente', $order_id); 
        update_field('estado_embalaje', 'Pendiente', $order_id);
        update_field('estado_pedido', 'En Tapicería/Embalaje', $order_id);
        wp_insert_post(['post_title' => 'Fase Costura completa (y Carpintería) -> A Tapicería/Embalaje', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $order_id]]);
    }
    // Regla 3: Tapicería y Embalaje -> Logística
    if ($field_estado_sector === 'estado_embalaje' && get_field('estado_tapiceria', $order_id) == 'Completado' && get_field('estado_logistica', $order_id) == 'No Asignado') {
        update_field('estado_logistica', 'Pendiente', $order_id); 
        update_field('estado_pedido', 'Listo para Entrega', $order_id);
        wp_insert_post(['post_title' => 'Fase Tapicería/Embalaje completa -> A Logística', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $order_id]]);
    }
    // Regla 4: Logística -> Pendiente de Cierre Admin
    if ($field_estado_sector === 'estado_logistica' && get_field('estado_pedido', $order_id) !== 'Pendiente de Cierre Admin') {
        update_field('estado_pedido', 'Pendiente de Cierre Admin', $order_id);
        update_field('estado_administrativo', 'Listo para Archivar', $order_id); // Campo para Macarena
        wp_insert_post(['post_title' => 'Entrega Completada -> Pendiente de Cierre Admin', 'post_type' => 'ghd_historial', 'meta_input' => ['_orden_produccion_id' => $order_id]]);
    }


    // Recalcular KPIs del sector DESPUÉS de actualizar el campo
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