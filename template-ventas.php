<?php
/**
 * Template Name: GHD - Panel de Vendedoras
 * Descripción: Panel de solo lectura para que las vendedoras consulten el estado de los pedidos.
 */

// Redirección de seguridad: Asegurar que solo usuarios con roles específicos accedan
if ( ! is_user_logged_in() || ( ! current_user_can('vendedora') && ! current_user_can('gerente_ventas') && ! current_user_can('manage_options') ) ) {
    auth_redirect(); // Redirige a la página de login si no está logueado o no tiene permisos
}

get_header(); 

// --- NUEVO: Definir las variables de rol aquí, antes de usarlas en el HTML ---
$current_user = wp_get_current_user();
$es_vendedora = in_array('vendedora', (array)$current_user->roles);
$es_gerente_ventas = in_array('gerente_ventas', (array)$current_user->roles);
$es_admin = current_user_can('manage_options');
// --- FIN NUEVO ---
?>

<div class="ghd-app-wrapper">
    
    <?php 
    // --- NUEVO: Incluir el sidebar condicionalmente ---
    if ($es_admin) {
        get_template_part('template-parts/sidebar-admin'); // Si es Admin, mostrar el sidebar completo del Admin
    } else {
        get_template_part('template-parts/sidebar-sales'); // Para Gerente de Ventas y Vendedoras, mostrar el sidebar de ventas
    }
    // --- FIN NUEVO ---
    ?>
    <main class="ghd-main-content">
        
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <h2>Panel de Vendedoras</h2>
            </div> 
            <div class="header-actions">
                <!-- Aquí podríamos añadir futuros botones de acción si fueran necesarios para el Gerente de Ventas, visible solo si ($es_gerente_ventas || $es_admin) -->
            </div>
        </header>

        <div class="ghd-card ghd-table-wrapper">
            <table class="ghd-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Producto</th>
                        <th>Vendedora</th>
                        <th>Estado General</th>
                        <th>Estado Producción</th>
                        <?php if ($es_gerente_ventas || $es_admin) : // Ahora estas variables YA están definidas ?>
                            <th>Comisión</th>
                        <?php endif; ?>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="ghd-ventas-table-body">
                    <?php
                    // --- NUEVO: Definición y ejecución de WP_Query aquí, antes de usarla en el loop ---
                    $args_pedidos_ventas = array(
                        'post_type'      => 'orden_produccion',
                        'posts_per_page' => -1,
                        'post_status'    => 'publish', // Asegúrate de que los archivados sigan siendo 'publish'
                        // No es necesario un meta_query para el estado de archivo aquí,
                        // ya que queremos ver TODOS los pedidos para comisiones.
                        // Si quisieras excluir solo los CANCELADOS (que no tendrían comisión), añadirías esa condición.
                        // Por ahora, mostrar todos.
                        'orderby' => 'modified', 
                        'order'   => 'DESC',
                    );

                    // Si es vendedora, solo debe ver sus pedidos asignados (esta lógica se mantiene)
                    if ($es_vendedora && !$es_gerente_ventas && !$es_admin) {
                        $args_pedidos_ventas['meta_query']['relation'] = 'AND'; // Asegurar AND si hay otra condición
                        $args_pedidos_ventas['meta_query'][] = array(
                            'key'     => 'vendedora_asignada',
                            'value'   => $current_user->ID,
                            'compare' => '=',
                        );
                    }
                    $pedidos_ventas_query = new WP_Query($args_pedidos_ventas); // <-- Ejecución de la WP_Query aquí
                    $sectores_produccion = ghd_get_sectores(); // Obtener los sectores de functions.php
                    // --- FIN NUEVO ---


                    if ($pedidos_ventas_query->have_posts()) :
                        while ($pedidos_ventas_query->have_posts()) : $pedidos_ventas_query->the_post();
                            $order_id = get_the_ID();
                            $codigo_pedido = get_the_title();
                            $nombre_cliente = get_field('nombre_cliente', $order_id);
                            $nombre_producto = get_field('nombre_producto', $order_id);
                            $estado_general = get_field('estado_pedido', $order_id);
                            $vendedora_id = get_field('vendedora_asignada', $order_id);
                            $vendedora_obj = $vendedora_id ? get_userdata($vendedora_id) : null;
                            $vendedora_nombre = $vendedora_obj ? $vendedora_obj->display_name : 'Sin asignar';
                    ?>
                        <tr>
                            <td><a href="<?php the_permalink(); ?>" style="color: var(--color-rojo); font-weight: 600;"><?php echo esc_html($codigo_pedido); ?></a></td>
                            <td><?php echo esc_html($nombre_cliente); ?></td>
                            <td><?php echo esc_html($nombre_producto); ?></td>
                            <td>
                                <?php if ($vendedora_id > 0) :
                                    $vendedora_colors = ghd_get_vendedora_color($vendedora_id); ?>
                                    <span class="ghd-badge" style="background-color: <?php echo esc_attr($vendedora_colors['bg_color']); ?>; color: <?php echo esc_attr($vendedora_colors['text_color']); ?>;">
                                        <?php echo esc_html($vendedora_nombre); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="ghd-badge status-gray">Sin asignar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $estado_general_slug = strtolower(str_replace([' ', '/'], ['-', ''], $estado_general)); // Limpiar el slug para la clase CSS
                                $badge_class_general = 'status-gray'; // Clase por defecto

                                // Define las clases según el estado general
                                if ($estado_general === 'Pendiente de Asignación') {
                                    $badge_class_general = 'status-pendiente';
                                } elseif ($estado_general === 'En Producción' || $estado_general === 'En Costura' || $estado_general === 'En Tapicería/Embalaje') {
                                    $badge_class_general = 'status-en-progreso'; // Unificamos los estados de producción en un color
                                } elseif ($estado_general === 'Listo para Entrega') {
                                    $badge_class_general = 'status-listo-entrega'; // Nuevo estado visual
                                } elseif ($estado_general === 'Pendiente de Cierre Admin') {
                                    $badge_class_general = 'status-pendientedecierreadmin';
                                } elseif ($estado_general === 'Completado y Archivado') {
                                    $badge_class_general = 'status-completado-archivado'; // Nuevo estado visual
                                }
                                // Puedes añadir más condiciones para otros estados generales si los tienes (ej. "Cancelado")
                                ?>
                                <span style="color: var(--color-principal);" class="ghd-badge <?php echo esc_attr($badge_class_general); ?>">
                                    <?php echo esc_html($estado_general); ?>
                                </span>
                            </td>
                            <td>
                                <div class="production-substatus-badges">
                                    <?php foreach ($sectores_produccion as $sector_key => $sector_display_name) {
                                        $sub_estado = get_field('estado_' . $sector_key, $order_id);
                                        if ($sub_estado && $sub_estado !== 'No Asignado') {
                                            $badge_class = 'status-gray';
                                            if ($sub_estado === 'Completado') $badge_class = 'status-green';
                                            elseif ($sub_estado === 'En Progreso') $badge_class = 'status-yellow';
                                            elseif ($sub_estado === 'Pendiente') $badge_class = 'status-blue';
                                            echo '<span class="ghd-badge ' . esc_attr($badge_class) . '">' . esc_html($sector_display_name) . ': ' . esc_html($sub_estado) . '</span>';
                                        }
                                    } ?>
                                </div>
                            </td>
                            <?php if ($es_gerente_ventas || $es_admin) : ?>
                                <?php $comision = get_field('comision_calculada', $order_id); ?>
                                <td><?php echo '$' . number_format($comision, 2, ',', '.'); ?></td>
                            <?php endif; ?>
                            <td>
                                <a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary ghd-btn-small">Ver Detalles</a>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else : ?>
                        <tr><td colspan="<?php echo ($es_gerente_ventas || $es_admin) ? '8' : '7'; ?>" style="text-align:center;">No hay pedidos para mostrar.</td></tr>
                    <?php endif; wp_reset_postdata(); ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<?php get_footer(); ?>