<?php
/**
 * Template Name: GHD - Panel de Fletero
 * Descripción: Panel optimizado para móvil para fleteros, mostrando sus entregas asignadas.
 */

// Redirección de seguridad: Asegurar que solo fleteros o administradores accedan
if ( ! is_user_logged_in() || ( ! current_user_can('operario_logistica') && ! current_user_can('lider_logistica') && ! current_user_can('manage_options') ) ) {
    auth_redirect(); 
}

get_header(); 
?>

<div class="ghd-app-wrapper is-mobile-optimized"> 
    
    <main class="ghd-main-content">
        
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <h2>Mis Entregas Asignadas</h2>
            </div> 
            <div class="header-actions">
                <button id="ghd-refresh-fletero-tasks" class="ghd-btn ghd-btn-secondary ghd-btn-small"><i class="fa-solid fa-sync"></i> <span>Refrescar</span></button>
            </div>
        </header>

        <div class="ghd-fletero-tasks-list" id="ghd-fletero-tasks-list">
            <?php
            $current_user_id = get_current_user_id();
            
            // Consulta para obtener las órdenes de producción asignadas al fletero actual
            $args_entregas_fletero = array(
                'post_type'      => 'orden_produccion',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'meta_query'     => array(
                    'relation' => 'AND',
                    array( // Debe estar en estado de logística o entregado por fletero
                        'key'     => 'estado_logistica',
                        'value'   => ['Pendiente', 'En Progreso', 'Recogido'], // No incluir 'Entregado' para mostrar solo tareas activas
                        'compare' => 'IN',
                    ),
                    array( // Asignado a este fletero
                        'key'     => 'logistica_fletero_id', // Campo ACF que ya creaste
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
                    // Puedes añadir más campos aquí que el fletero necesite ver (ej. nombre_producto, cliente_telefono)
                    $nombre_producto = get_field('nombre_producto', $order_id);
                    $cliente_telefono = get_field('cliente_telefono', $order_id);


                    // Determinar el botón de acción actual y su estado
                    $action_button_html = '';
                    if ($estado_logistica === 'Pendiente' || $estado_logistica === 'En Progreso') {
                        $action_button_html = '<button class="ghd-btn ghd-btn-primary ghd-btn-small fletero-action-btn" data-order-id="' . esc_attr($order_id) . '" data-new-status="Recogido">Marcar como Recogido</button>';
                    } elseif ($estado_logistica === 'Recogido') {
                        $action_button_html = '<button class="ghd-btn ghd-btn-success ghd-btn-small fletero-action-btn open-upload-delivery-proof-modal" data-order-id="' . esc_attr($order_id) . '">Entregado + Comprobante</button>';
                        // O "Marcar como Entregado"
                    }
                    // Si el estado es 'Completado', no se muestra botón de acción activo, pero la tarjeta desaparecería de esta lista.
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
                            <!-- Aquí puedes añadir más detalles del pedido que el fletero necesite -->
                        </div>
                    </div>
                    <div class="order-card-actions">
                        <?php echo $action_button_html; ?>
                        <a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary ghd-btn-small">Ver Detalles</a>
                    </div>
                </div>

                <!-- Modal para subir comprobante de entrega -->
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
            else : ?>
                <p class="no-tasks-message" style="text-align: center; padding: 20px;">No tienes entregas asignadas actualmente.</p>
            <?php endif; wp_reset_postdata(); ?>
        </div>

    </main>
</div>

<?php get_footer(); ?>