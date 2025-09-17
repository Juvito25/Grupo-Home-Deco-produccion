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
?>

<div class="ghd-app-wrapper">
    
    <?php 
    // Por ahora, usamos el sidebar de administrador. Podemos crear uno específico más adelante si es necesario.
    // O si las vendedoras no necesitan sidebar, simplemente se remueve esta línea.
    get_template_part('template-parts/sidebar-admin'); 
    ?>

    <main class="ghd-main-content">
        
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <h2>Panel de Vendedoras</h2>
            </div> 
            <div class="header-actions">
                <!-- Aquí podríamos añadir futuros botones de acción si fueran necesarios para el Gerente de Ventas, pero por ahora es de solo lectura -->
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
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="ghd-ventas-table-body">
                        <?php
                        $current_user = wp_get_current_user();
                        $es_vendedora = in_array('vendedora', (array)$current_user->roles);
                        $es_gerente_ventas = in_array('gerente_ventas', (array)$current_user->roles);
                        $es_admin = current_user_can('manage_options');

                        $args_pedidos_ventas = array(
                            'post_type'      => 'orden_produccion',
                            'posts_per_page' => -1,
                            'post_status'    => 'publish', // O el estado que uses para pedidos activos
                            'meta_query'     => array(
                                'relation' => 'AND',
                                array( // Excluir pedidos archivados
                                    'key'     => 'estado_pedido',
                                    'value'   => 'Completado y Archivado',
                                    'compare' => '!=',
                                ),
                            ),
                            'orderby' => 'modified', // Ordenar por fecha de última modificación
                            'order'   => 'DESC',
                        );

                        // Si es vendedora, solo debe ver sus pedidos asignados
                        if ($es_vendedora && !$es_gerente_ventas && !$es_admin) {
                            $args_pedidos_ventas['meta_query'][] = array(
                                'key'     => 'vendedora_asignada',
                                'value'   => $current_user->ID,
                                'compare' => '=',
                            );
                        }

                        $pedidos_ventas_query = new WP_Query($args_pedidos_ventas);
                        $sectores_produccion = ghd_get_sectores(); // Obtener los sectores de functions.php

                        if ($pedidos_ventas_query->have_posts()) :
                            while ($pedidos_ventas_query->have_posts()) : $pedidos_ventas_query->the_post();
                                $order_id = get_the_ID();
                                $codigo_pedido = get_the_title(); // El título es el código
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
                                    <?php if ($vendedora_id > 0) : // Si hay una vendedora asignada
                                        $vendedora_colors = ghd_get_vendedora_color($vendedora_id);
                                    ?>
                                        <span class="ghd-badge" style="background-color: <?php echo esc_attr($vendedora_colors['bg_color']); ?>; color: <?php echo esc_attr($vendedora_colors['text_color']); ?>;">
                                            <?php echo esc_html($vendedora_nombre); ?>
                                        </span>
                                    <?php else : // Si no está asignada ?>
                                        <span class="ghd-badge status-gray">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="ghd-badge status-<?php echo strtolower(str_replace(' ', '-', $estado_general)); ?>">
                                        <?php echo esc_html($estado_general); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="production-substatus-badges">
                                        <?php
                                        foreach ($sectores_produccion as $sector_key => $sector_display_name) {
                                            $sub_estado = get_field('estado_' . $sector_key, $order_id);
                                            if ($sub_estado && $sub_estado !== 'No Asignado') {
                                                $badge_class = 'status-gray';
                                                if ($sub_estado === 'Completado') $badge_class = 'status-green';
                                                elseif ($sub_estado === 'En Progreso') $badge_class = 'status-yellow';
                                                elseif ($sub_estado === 'Pendiente') $badge_class = 'status-blue';
                                                echo '<span class="ghd-badge ' . esc_attr($badge_class) . '">' . esc_html($sector_display_name) . ': ' . esc_html($sub_estado) . '</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary ghd-btn-small">Ver Detalles</a>
                                </td>
                            </tr>
                        <?php
                            endwhile;
                        else : ?>
                            <tr><td colspan="7" style="text-align:center;">No hay pedidos para mostrar.</td></tr>
                        <?php endif; wp_reset_postdata(); ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

<?php get_footer(); ?>