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
    $mapa_roles = ghd_get_mapa_roles_a_campos();
    $campo_estado = $mapa_roles[$user_role] ?? '';
    // Si el rol es 'rol_administrativo' y no es admin viendo, no tendrá un campo_estado para tareas activas aquí.
    if ($user_role === 'rol_administrativo') {
        $sector_name = 'Cierre de Pedidos'; // Nombre para el panel del antiguo rol_administrativo
    } else {
        $sector_name = ucfirst(str_replace(['rol_', '_'], ' ', $user_role));
    }
} 
// Caso 3: Admin sin especificar sector (redirigir, aunque el login_redirect ya lo debería manejar)
else {
    wp_redirect(home_url('/panel-de-control/'));
    exit;
}

<<<<<<< HEAD
// --- AÑADIR CLASE AL BODY SI ES EL PANEL ADMINISTRATIVO ---
if ($user_role === 'rol_administrativo' && !$is_admin_viewing) { // Aseguramos que sea el admin viendo su propio panel, no un admin viendo otro sector
    add_filter('body_class', function($classes) {
        $classes[] = 'is-admin-sector-panel';
        return $classes;
    });
}

get_header(); // get_header() debe estar después de add_filter('body_class')

// --- 2. CONSULTA Y KPIs (Estable) ---
$pedidos_args = ['post_type' => 'orden_produccion', 'posts_per_page' => -1, 'meta_query' => [['key' => $campo_estado, 'value' => ['Pendiente', 'En Progreso'], 'compare' => 'IN']], 'orderby'  => ['prioridad_pedido' => 'ASC', 'date' => 'ASC'], 'meta_key' => 'prioridad_pedido' ];
$pedidos_query = new WP_Query($pedidos_args);
$total_pedidos = $pedidos_query->post_count;
$total_prioridad_alta = 0; $total_tiempo_espera = 0; $ahora = current_time('U');
if ($pedidos_query->have_posts()) {
    foreach ($pedidos_query->posts as $pedido) {
        if (get_field('prioridad_pedido', $pedido->ID) === 'Alta') { $total_prioridad_alta++; }
        $total_tiempo_espera += $ahora - get_the_modified_time('U', $pedido->ID);
    }
}
$tiempo_promedio_str = '0.0h';
if ($total_pedidos > 0) {
    $promedio_horas = ($total_tiempo_espera / $total_pedidos) / 3600;
    $tiempo_promedio_str = number_format($promedio_horas, 1) . 'h';
}

// Calcular 'Completadas Hoy' para la carga inicial del dashboard
$completadas_hoy = 0;
// Obtener el inicio y fin del día actual en GMT para la consulta
$today_start = strtotime('today', current_time('timestamp', true)); // Inicio de hoy en timestamp GMT
$today_end   = strtotime('tomorrow - 1 second', current_time('timestamp', true)); // Fin de hoy en timestamp GMT

$completadas_hoy_args = [
    'post_type'      => 'orden_produccion',
    'posts_per_page' => -1,
    'meta_query'     => [
        [
            'key'     => 'estado_administrativo',
            'value'   => 'Archivado',
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
$completadas_hoy = $completadas_hoy_query->post_count;

=======
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

// --- CONSULTA PARA LAS TARJETAS DE TAREA (AHORA UNIFICADA) ---
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
>>>>>>> 2dac4e9 (Feat: completado del flujo de trabajo)

?>

<div class="ghd-app-wrapper">
    <?php 
    if ($is_admin_viewing) { get_template_part('template-parts/sidebar-admin'); } 
    else { get_template_part('template-parts/sidebar-sector'); } 
    ?>

    <main class="ghd-main-content">
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
                
                // Ahora, todos los roles que usan este template (excepto el admin viendo otros)
                // usarán la misma tarjeta de tarea.
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