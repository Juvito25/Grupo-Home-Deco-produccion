<?php
/**
 * Template Part para mostrar una fila de pedido en la tabla del administrador.
 * Este archivo es un componente "tonto": recibe todos sus datos a través de la variable $args.
 */

if (!isset($args) || !is_array($args)) {
    return;
}

// Definir valores por defecto para evitar "Undefined array key"
$post_id          = $args['post_id'] ?? 0;
$titulo           = $args['titulo'] ?? 'N/A';
$nombre_cliente   = $args['nombre_cliente'] ?? 'N/A';
$nombre_producto  = $args['nombre_producto'] ?? 'N/A';
$estado           = $args['estado'] ?? 'N/A';
$prioridad        = $args['prioridad'] ?? 'N/A';
$sector_actual    = $args['sector_actual'] ?? 'N/A';
$fecha_del_pedido = $args['fecha_del_pedido'] ?? get_the_date('', $post_id); // Fallback

?>
<tr id="order-row-<?php echo esc_attr($post_id); ?>">
    <td><a href="<?php echo esc_url(get_permalink($post_id)); ?>" style="color: var(--color-rojo); font-weight: 600;"><?php echo esc_html($titulo); ?></a></td>
    <td><?php echo esc_html($nombre_cliente); ?></td>
    <td><?php echo esc_html($nombre_producto); ?></td>
    <td><?php echo esc_html($estado); ?></td>
    <td><?php echo esc_html($prioridad); ?></td>
    <td><?php echo esc_html($sector_actual); ?></td>
    <td><?php echo esc_html($fecha_del_pedido); ?></td>
    <td class="actions-cell">
        <!-- Botón para Ver Detalles -->
        <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="ghd-btn-icon" title="Ver Detalles del Pedido">
            <i class="fa-solid fa-eye"></i>
        </a>
        
        <!-- Botón para Generar Remito -->
        <a href="<?php echo esc_url(add_query_arg('pedido_id', $post_id, home_url('/generar-remito/'))); ?>" class="ghd-btn-icon" title="Generar Remito" target="_blank">
            <i class="fa-solid fa-file-pdf"></i>
        </a>
    </td>
</tr>