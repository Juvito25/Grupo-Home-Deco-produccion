<?php
/**
 * Template Part para mostrar una tarjeta de tarea en el panel de sector.
 * Recibe sus datos a través de la variable $args.
 */
if (!isset($args) || !is_array($args)) { return; }
// --- DEBUG: Mostrar los $args que recibe task-card.php ---
// echo '<pre>DEBUG: task-card.php $args: ';
// var_dump($args);
// echo '</pre>';
// --- FIN DEBUG ---
$post_id         = esc_attr($args['post_id']);
$titulo          = esc_html($args['titulo']);
$prioridad_class = esc_attr($args['prioridad_class']);
$prioridad       = esc_html($args['prioridad']);
$nombre_cliente  = esc_html($args['nombre_cliente']);
$nombre_producto = esc_html($args['nombre_producto']);
$permalink       = esc_url($args['permalink']);
$campo_estado    = esc_attr($args['campo_estado']); // estado_carpinteria, estado_corte, etc.
$estado_actual   = esc_html($args['estado_actual']); // Pendiente, En Progreso, Completado, etc.

// --- NUEVAS VARIABLES EXTRAÍDAS DE $args ---
$is_leader = $args['is_leader'] ?? false;
$operarios_del_sector = $args['operarios_sector'] ?? []; // Lista de objetos de usuario
$asignado_a_id = $args['asignado_a_id'] ?? 0;
$asignado_a_name = $args['asignado_a_name'] ?? 'Nadie';
$logged_in_user_id = $args['logged_in_user_id'] ?? 0; // ID del usuario logueado
// --- FIN NUEVAS VARIABLES ---

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
// Si el estado es 'Completado' para este sector, no se muestra el botón de acción.

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
            <!-- NUEVO: Mostrar a quién está asignado -->
            <?php if ($asignado_a_id) : ?>
                <p><strong>Asignado a:</strong> <?php echo esc_html($asignado_a_name); ?></p>
            <?php else : ?>
                <p><strong>Asignado a:</strong> <span class="ghd-text-muted">Sin asignar</span></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="order-card-actions">
        <!-- NUEVO: Selector de Asignación (solo para líderes) -->
        <?php if ($is_leader && !empty($operarios_del_sector)) :
            // Determina el valor seleccionado para el selector
            $current_assignee_id_for_select = $asignado_a_id ? $asignado_a_id : '0'; // Valor '0' para "Sin asignar"
        ?>
            <select class="ghd-btn ghd-btn-secondary ghd-assignee-selector ghd-btn-small" data-order-id="<?php echo $post_id; ?>" data-field-prefix="<?php echo str_replace('estado_', 'asignado_a_', $campo_estado); ?>">
                <option value="0" <?php selected($current_assignee_id_for_select, '0'); ?>>Asignar Operario</option>
                <?php foreach ($operarios_del_sector as $operario) : ?>
                    <option value="<?php echo esc_attr($operario->ID); ?>" <?php selected($current_assignee_id_for_select, $operario->ID); ?>>
                        <?php echo esc_html($operario->display_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <!-- Botón de acción principal: Iniciar Tarea / Marcar Completa -->
        <?php
        $can_action_task = false;
        // Lógica para determinar si el usuario actual puede realizar la acción en esta tarea.
        if ($is_leader) {
            $can_action_task = true; // Los líderes pueden actuar en cualquier tarea de su sector
        } elseif ($asignado_a_id === $logged_in_user_id) { // Si es operario y la tarea está asignada a él
            $can_action_task = true;
        }

        if ($button_text && $can_action_task) :
            if ($estado_actual === 'En Progreso') : // Si la tarea está En Progreso, se muestra el botón para Completar y Registrar Detalles
            ?>
                <button class="ghd-btn ghd-btn-success action-button open-complete-task-modal"
                        data-order-id="<?php echo $post_id; ?>"
                        data-field="<?php echo $campo_estado; ?>"
                        data-assignee-id="<?php echo esc_attr($asignado_a_id); ?>"> <!-- Paso el ID del asignado por si fuera necesario en el modal -->
                    <i class="fa-solid fa-check"></i> <span>Marcar Completa y Registrar</span>
                </button>
            <?php else : // Si la tarea está Pendiente, se muestra el botón para Iniciar Tarea
            ?>
                <button class="ghd-btn <?php echo $button_class; ?> action-button"
                        data-order-id="<?php echo $post_id; ?>"
                        data-field="<?php echo $campo_estado; ?>"
                        data-value="<?php echo $button_value; ?>">
                    <i class="fa-solid fa-play"></i> <?php echo $button_text; ?>
                </button>
            <?php endif; ?>
        <?php endif; ?>

        <a href="<?php echo $permalink; ?>" class="ghd-btn ghd-btn-secondary ghd-btn-small">Ver Detalles</a>
    </div>
</div>

<!-- NUEVO: Modal para Registrar Detalles de Tarea (se oculta por defecto) -->
<div id="complete-task-modal-<?php echo $post_id; ?>" class="ghd-modal">
    <div class="ghd-modal-content">
        <span class="close-button" data-modal-id="complete-task-modal-<?php echo $post_id; ?>">&times;</span>
        <h3>Registrar Detalles y Completar Tarea: <?php echo $titulo; ?></h3>
        <form class="complete-task-form" data-order-id="<?php echo $post_id; ?>" data-field="<?php echo $campo_estado; ?>">
            <div class="form-group">
                <label for="modelo_item_<?php echo $post_id; ?>">Modelo de Sillón:</label>
                <input type="text" id="modelo_item_<?php echo $post_id; ?>" name="modelo_principal_hecho" required>
            </div>
            <div class="form-group">
                <label for="cantidad_item_<?php echo $post_id; ?>">Cantidad:</label>
                <input type="number" id="cantidad_item_<?php echo $post_id; ?>" name="cantidad_principal_hecha" min="1" required>
            </div>
            <div class="form-group">
                <label for="foto_tarea_<?php echo $post_id; ?>">Foto de la Tarea (Opcional):</label>
                <input type="file" id="foto_tarea_<?php echo $post_id; ?>" name="foto_tarea" accept="image/*">
            </div>
            <div class="form-group">
                <label for="observaciones_tarea_<?php echo $post_id; ?>">Observaciones (Opcional):</label>
                <textarea id="observaciones_tarea_<?php echo $post_id; ?>" name="observaciones_tarea_completa"></textarea>
            </div>
            <button type="submit" class="ghd-btn ghd-btn-success"><i class="fa-solid fa-check"></i> Completar y Guardar</button>
            <button type="button" class="ghd-btn ghd-btn-secondary close-button" data-modal-id="complete-task-modal-<?php echo $post_id; ?>">Cancelar</button>
        </form>
    </div>
</div>