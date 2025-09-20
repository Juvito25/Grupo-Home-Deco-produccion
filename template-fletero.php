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

// Obtener el ID del usuario logueado
$current_user = wp_get_current_user();
?>

<div class="ghd-app-wrapper is-mobile-optimized"> 
    
    <aside class="ghd-sidebar">
        <div class="ghd-sidebar-header">
            <h3>Mi Puesto</h3>
            <button id="mobile-menu-close" class="ghd-btn-icon"><i class="fa-solid fa-times"></i></button>
        </div>
        <nav class="ghd-sidebar-nav">
            <ul>
                <li class="nav-item">
                    <a href="<?php echo esc_url(home_url('/panel-de-fletero/')); ?>" class="nav-link is-active">
                        <i class="fa-solid fa-truck"></i> Mis Entregas
                    </a>
                </li>
            </ul>
        </nav>
        <div class="ghd-sidebar-footer">
            <a href="<?php echo wp_logout_url(home_url('/iniciar-sesion/')); ?>" class="nav-link logout-link">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar Sesión
            </a>
        </div>
    </aside>

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
                    array( 
                        'key'     => 'estado_logistica_fletero', // ¡Correcto! Busca el nuevo campo de estado del fletero
                        'value'   => ['Pendiente', 'Recogido'], // Solo tareas activas del fletero
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
                    $estado_fletero_actual = get_field('estado_logistica_fletero', $order_id); // <-- ¡CRÍTICO! Obtener el campo CORRECTO
                    $nombre_producto = get_field('nombre_producto', $order_id);
                    $cliente_telefono = get_field('cliente_telefono', $order_id);

                    $action_button_html = '';
                    if ($estado_fletero_actual === 'Pendiente') { // <-- Usar $estado_fletero_actual
                        $action_button_html = '<button class="ghd-btn ghd-btn-primary ghd-btn-small fletero-action-btn" data-order-id="' . esc_attr($order_id) . '" data-new-status="Recogido"><i class="fa-solid fa-hand-holding-box"></i> Marcar como Recogido</button>';
                    } elseif ($estado_fletero_actual === 'Recogido') { // <-- Usar $estado_fletero_actual
                        $action_button_html = '<button class="ghd-btn ghd-btn-success ghd-btn-small fletero-action-btn open-upload-delivery-proof-modal" data-order-id="' . esc_attr($order_id) . '"><i class="fa-solid fa-camera"></i> Entregado + Comprobante</button>';
                    }
                    $fletero_tag_class = strtolower(str_replace([' ', '/'], ['-', ''], $estado_fletero_actual)); // <-- Usar $estado_fletero_actual
            ?>
                <div class="ghd-order-card fletero-card" id="fletero-order-<?php echo $order_id; ?>">
                    <div class="order-card-main">
                        <div class="order-card-header">
                            <h3><i class="fa-solid fa-truck-fast"></i> <?php echo esc_html($codigo_pedido); ?></h3>
                            <span class="ghd-tag status-<?php echo esc_attr($fletero_tag_class); ?>"><?php echo esc_html($estado_fletero_actual); ?></span>
                        </div>
                        <div class="order-card-body">
                            <p><i class="fa-solid fa-user"></i> <strong>Cliente:</strong> <?php echo esc_html($nombre_cliente); ?></p>
                            <?php if ($nombre_producto) : ?><p><i class="fa-solid fa-chair"></i> <strong>Producto:</strong> <?php echo esc_html($nombre_producto); ?></p><?php endif; ?>
                            <p><i class="fa-solid fa-location-dot"></i> <strong>Dirección:</strong> <?php echo nl2br(esc_html($direccion_entrega)); ?></p>
                            <?php if ($cliente_telefono) : ?><p><strong>Teléfono:</strong> <a href="tel:<?php echo esc_attr($cliente_telefono); ?>" class="phone-link"><?php echo esc_html($cliente_telefono); ?></a></p><?php endif; ?>
                        </div>
                    </div>
                    <div class="order-card-actions">
                        <?php echo $action_button_html; ?>
                        <a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary ghd-btn-small"><i class="fa-solid fa-info-circle"></i> Ver Detalles</a>
                    </div>
                </div>

                <!-- Modal para subir comprobante de entrega -->
                <div id="upload-delivery-proof-modal-<?php echo $order_id; ?>" class="ghd-modal">
                    <div class="ghd-modal-content">
                        <span class="close-button" data-modal-id="upload-delivery-proof-modal-<?php echo $order_id; ?>">&times;</span>
                        <h3>Completar Entrega: <?php echo esc_html($codigo_pedido); ?></h3>
                        <form class="complete-delivery-form" data-order-id="<?php echo $order_id; ?>" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="foto_comprobante_<?php echo $order_id; ?>"><i class="fa-solid fa-image"></i> Foto de Comprobante (Opcional):</label>
                                <input type="file" id="foto_comprobante_<?php echo $order_id; ?>" name="foto_comprobante" accept="image/*">
                            </div>
                            <div class="form-group">
                                <label for="firma_cliente_<?php echo $order_id; ?>"><i class="fa-solid fa-signature"></i> Firma del Cliente (Opcional):</label>
                                <textarea id="firma_cliente_<?php echo $order_id; ?>" name="firma_cliente" rows="3" placeholder="Ingresar el nombre del cliente que firma o descripción de la firma..."></textarea>
                            </div>
                            <button type="submit" class="ghd-btn ghd-btn-success" style="margin-top: 20px;"><i class="fa-solid fa-check-circle"></i> Marcar como Entregado</button>
                        </form>
                    </div>
                </div>

            <?php
                endwhile;
            else : 
                error_log('GHD: No hay entregas para el usuario ' . $current_user_id . ' en template-fletero.php.');
            ?>
                <p class="no-tasks-message" style="text-align: center; padding: 20px;">No tienes entregas asignadas actualmente.</p>
            <?php endif; wp_reset_postdata(); ?>
        </div>

    </main>
</div>

<?php get_footer(); ?>