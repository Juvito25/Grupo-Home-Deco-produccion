<?php
/**
 * Template Part para mostrar una tarjeta de tarea en el panel de sector.
 * Recibe sus datos a través de la variable $args.
 */
if (!isset($args) || !is_array($args)) { return; }
?>
<div class="ghd-task-card" id="order-<?php echo esc_attr($args['post_id']); ?>">
    <div class="card-header">
        <h3><?php echo esc_html($args['titulo']); ?></h3>
        <span class="ghd-tag <?php echo esc_attr($args['prioridad_class']); ?>"><?php echo esc_html($args['prioridad']); ?></span>
    </div>
    <div class="card-body">
        <div class="card-info-group">
            <span class="info-label">Cliente:</span>
            <span class="info-value"><?php echo esc_html($args['nombre_cliente']); ?></span>
        </div>
        <div class="card-info-group">
            <span class="info-label">Producto:</span>
            <span class="info-value"><?php echo esc_html($args['nombre_producto']); ?></span>
        </div>
    </div>
    <div class="card-footer">
        <a href="<?php echo esc_url($args['permalink']); ?>" class="ghd-btn ghd-btn-secondary">Ver Detalles</a>
        <button class="ghd-btn ghd-btn-primary move-to-next-sector-btn" data-order-id="<?php echo esc_attr($args['post_id']); ?>" data-nonce="<?php echo esc_attr($args['nonce']); ?>">
            Mover a Siguiente Sector
        </button>
    </div>
</div>