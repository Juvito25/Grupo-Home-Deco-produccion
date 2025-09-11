<?php
/*
Template Name: Panel de Tareas (Sector)
*/

if (!is_user_logged_in()) { auth_redirect(); }

$current_user = wp_get_current_user();
$is_admin_viewing = false;
$user_roles = $current_user->roles;
$user_role = !empty($user_roles) ? $user_roles[0] : '';

// Caso 1: Administrador de WordPress visitando un panel de sector
if (current_user_can('manage_options') && isset($_GET['sector'])) {
    $sector_name = sanitize_text_field(urldecode($_GET['sector']));
    $is_admin_viewing = true;
    $clean_sector_name = strtolower(str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $sector_name));
    $campo_estado = 'estado_' . $clean_sector_name;
} 
// Caso 2: Trabajador de producción viendo su propio panel
elseif (!current_user_can('manage_options')) {
    $mapa_roles = ghd_get_mapa_roles_a_campos(); // Mapea rol_X a estado_X (ej: lider_carpinteria -> estado_carpinteria)
    $user_roles = (array) $current_user->roles; // Asegurarse de que $user->roles es un array
    $user_role = !empty($user_roles) ? $user_roles[0] : ''; // Obtener el rol principal del usuario logueado

    $campo_estado = $mapa_roles[$user_role] ?? ''; // <--- ESTA LÍNEA ES CLAVE Y DEBE RECUPERAR EL CAMPO CORRECTO

    // Determinar el nombre legible del sector para el título H2 y para el sidebar
    $sector_name = ''; 
    $base_sector_key = ''; // ej: 'carpinteria' de 'lider_carpinteria'

    if (strpos($user_role, 'lider_') !== false) {
        $base_sector_key = str_replace('lider_', '', $user_role);
    } elseif (strpos($user_role, 'operario_') !== false) {
        $base_sector_key = str_replace('operario_', '', $user_role);
    } elseif ($user_role === 'control_final_macarena') {
        $base_sector_key = 'control_final'; // Clave para Macarena
    } elseif ($user_role === 'vendedora') {
        $base_sector_key = 'ventas'; // Clave para Vendedoras
    } elseif ($user_role === 'gerente_ventas') {
        $base_sector_key = 'gerente_ventas'; // Clave para Gerente de Ventas
    }
    
    $sector_display_map = [ // Mapeo de claves a nombres de sector legibles
        'carpinteria' => 'Carpintería', 'corte' => 'Corte', 'costura' => 'Costura', 
        'tapiceria' => 'Tapicería', 'embalaje' => 'Embalaje', 'logistica' => 'Logística',
        'control_final' => 'Control Final de Pedidos', // Nombre específico para Macarena
        'ventas' => 'Mis Ventas', // Nombre específico para Vendedoras
        'gerente_ventas' => 'Gerencia de Ventas', // Nombre específico para Gerente de Ventas
    ];
    $sector_name = $sector_display_map[$base_sector_key] ?? ucfirst(str_replace('_', ' ', $base_sector_key)); // Título del H2

    // --- Lógica para operarios_del_sector (para líderes) ---
    $is_leader = (strpos($user_role, 'lider_') !== false);
    $operarios_del_sector = [];
    // Solo obtener operarios del sector si el usuario actual es un líder de producción
    if ($is_leader && !empty($base_sector_key)) {
        $operarios_del_sector = get_users([
            'role__in' => ['lider_' . $base_sector_key, 'operario_' . $base_sector_key], 
            'orderby'  => 'display_name',
            'order'    => 'ASC'
        ]);
    }
    // --- FIN Lógica para operarios_del_sector ---
}/////// fin de elseif

// Caso 3: Admin sin especificar sector (redirigir, aunque el login_redirect ya lo debería manejar)
else {
    wp_redirect(home_url('/panel-de-control/'));
    exit;
}

// Añadir una clase al body para identificar este panel en JS
add_filter('body_class', function($classes) {
    $classes[] = 'is-sector-dashboard-panel';
    return $classes;
});

get_header();
get_header();

// --- CALCULAR KPIs DEL SECTOR (AHORA USANDO LA FUNCIÓN REUTILIZABLE) ---
// Solo calculamos KPIs si hay un campo_estado definido para este rol/sector
$sector_kpi_data = [];
if (!empty($campo_estado)) {
    $sector_kpi_data = ghd_calculate_sector_kpis($campo_estado);
} else {
    // Si no hay campo_estado (ej. rol_administrativo sin tareas activas aquí), inicializar a cero
    $sector_kpi_data = [
        'total_pedidos'        => 0,
        'total_prioridad_alta' => 0,
        'tiempo_promedio_str'  => '0.0h',
        'completadas_hoy'      => 0,
    ];
}

$total_pedidos = $sector_kpi_data['total_pedidos'];
$total_prioridad_alta = $sector_kpi_data['total_prioridad_alta'];
$completadas_hoy = $sector_kpi_data['completadas_hoy'];
$tiempo_promedio_str = $sector_kpi_data['tiempo_promedio_str'];

// --- CONSULTA PARA LAS TARJETAS DE TAREA ---
$pedidos_query_args = [
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

// --- NUEVO: Lógica de filtrado para operarios y líderes ---
if (!current_user_can('manage_options')) { // Si NO es Admin Principal
    if ($user_role === 'control_final_macarena') {
        // Macarena ve solo los pedidos pendientes de cierre administrativo
        $pedidos_query_args['meta_query'] = [
            ['key' => 'estado_pedido', 'value' => 'Pendiente de Cierre Admin', 'compare' => '='],
        ];
    } elseif (!$is_leader) { // Si es un operario (no un líder)
        // Un operario solo ve las tareas asignadas a su ID
        $asignado_a_field = str_replace('estado_', 'asignado_a_', $campo_estado); // ej. 'asignado_a_carpinteria'
        $pedidos_query_args['meta_query'][] = ['key' => $asignado_a_field, 'value' => $current_user->ID, 'compare' => '='];
    }
    // Si es un líder, la consulta base ya le trae todas las tareas del sector (Pendiente/En Progreso)
}
// --- FIN NUEVO ---

$pedidos_query = new WP_Query($pedidos_query_args);
?>

<div class="ghd-app-wrapper">
    <?php 
    if ($is_admin_viewing) { get_template_part('template-parts/sidebar-admin'); } 
    else { get_template_part('template-parts/sidebar-sector'); } 
    ?>

    <main class="ghd-main-content" data-campo-estado="<?php echo esc_attr($campo_estado); ?>">
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <h2>Tareas de <?php echo esc_html($sector_name); ?></h2>
            </div>
            <div class="header-actions">
                <button id="ghd-refresh-tasks" class="ghd-btn ghd-btn-secondary"><i class="fa-solid fa-sync"></i> <span>Refrescar</span></button>
            </div>
        </header>

        <div class="ghd-kpi-grid">
            <div class="ghd-kpi-card"><div class="kpi-icon icon-blue"><i class="fa-solid fa-list-check"></i></div><div class="kpi-info"><span id="kpi-activas" class="kpi-value"><?php echo $total_pedidos; ?></span><span class="kpi-label">Activas</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="kpi-info"><span id="kpi-prioridad-alta" class="kpi-value"><?php echo $total_prioridad_alta; ?></span><span class="kpi-label">Prioridad Alta</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-green"><i class="fa-solid fa-check"></i></div><div class="kpi-info"><span id="kpi-completadas-hoy" class="kpi-value"><?php echo $completadas_hoy; ?></span><span class="kpi-label">Completadas Hoy</span></div></div>
            <div class="ghd-kpi-card"><div class="kpi-icon icon-yellow"><i class="fa-solid fa-clock"></i></div><div class="kpi-info"><span id="kpi-tiempo-promedio" class="kpi-value"><?php echo esc_html($tiempo_promedio_str); ?></span><span class="kpi-label">Tiempo Promedio</span></div></div>
        </div>
        <div class="ghd-sector-tasks-list">
            <?php if ($pedidos_query->have_posts()) : while ($pedidos_query->have_posts()) : $pedidos_query->the_post();
                
                $current_order_id = get_the_ID();
                $current_status = get_field($campo_estado, $current_order_id);
                $prioridad_pedido = get_field('prioridad_pedido', $current_order_id);
                $prioridad_class = '';
                if ($prioridad_pedido === 'Alta') {
                    $prioridad_class = 'prioridad-alta';
                } elseif ($prioridad_pedido === 'Media') {
                    $prioridad_class = 'prioridad-media';
                } else { // Baja o no especificada
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

            endwhile; else: ?>
                <p class="no-tasks-message">No tienes tareas pendientes.</p>
            <?php endif; wp_reset_postdata(); ?>
        </div>        
    </main>
</div>
<?php get_footer(); ?>