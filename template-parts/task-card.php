<?php
/**
 * Template Part para mostrar una tarjeta de tarea en el panel de sector.
 * Recibe sus datos a través de la variable $args.
 */
if (!isset($args) || !is_array($args)) { return; }

$post_id         = esc_attr($args['post_id']);
$titulo          = esc_html($args['titulo']);
$prioridad_class = esc_attr($args['prioridad_class']);
$prioridad       = esc_html($args['prioridad']);
$nombre_cliente  = esc_html($args['nombre_cliente']);
$nombre_producto = esc_html($args['nombre_producto']);
$permalink       = esc_url($args['permalink']);
$campo_estado    = esc_attr($args['campo_estado']); // estado_carpinteria, estado_corte, etc.
$estado_actual   = esc_html($args['estado_actual']); // Pendiente, En Progreso, Completado, etc.

$button_text = '';
$button_value = '';
$button_class = '';

if ($estado_actual === 'Pendiente') {
    $button_text = 'Iniciar Tarea';
    $button_value = 'En Progreso';
    $button_class = 'ghd-btn-primary';
} elseif ($estado_actual === 'En Progreso') {
    $button_text = 'Marcar Completa';
    $button_value = 'Completado';
    $button_class = 'ghd-btn-success'; // Un color diferente para "Completar"
} 
// Si el estado es 'Completado' para este sector, no se muestra el botón de acción,
// ya que la consulta de pedidos en template-sector-dashboard.php solo trae 'Pendiente' y 'En Progreso'.
// Si por alguna razón un pedido 'Completado' llegara aquí, simplemente no mostraría el botón.

?>
<div class="ghd-order-card" id="order-<?php echo $post_id; ?>">
    <div class="order-card-main">
        <div class="order-card-header">
            <h3><?php echo $titulo; ?></h3>
            <?php if ($prioridad) : ?>
                <span class="ghd-tag <?php echo $prioridad_class; ?>"><?php echo $prioridad; ?></span>
            <?php endif; ?>
        </div>
        <div class="order-card-body">
            <p><strong>Cliente:</strong> <?php echo $nombre_cliente; ?></p>
            <p><strong>Producto:</strong> <?php echo $nombre_producto; ?></p>
        </div>
    </div>
    <div class="order-card-actions">
        <?php if ($button_text) : // Solo muestra el botón si se ha definido su texto ?>
            <button class="ghd-btn <?php echo $button_class; ?> action-button" 
                    data-order-id="<?php echo $post_id; ?>" 
                    data-field="<?php echo $campo_estado; ?>" 
                    data-value="<?php echo $button_value; ?>">
                <?php echo $button_text; ?>
            </button>
        <?php endif; ?>
        <a href="<?php echo $permalink; ?>" class="ghd-btn ghd-btn-secondary">Ver Detalles</a>
    </div>
</div>