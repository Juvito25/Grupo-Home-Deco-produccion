<?php
/**
 * Template Part para mostrar una tarjeta de tarea en el panel de sector.
 * V3 - Lógica de botones restaurada y funcional para todos los roles.
 */
if (!isset($args) || !is_array($args)) { return; }

$post_id         = esc_attr($args['post_id']);
$titulo          = esc_html($args['titulo']);
$prioridad_class = esc_attr($args['prioridad_class']);
$prioridad       = esc_html($args['prioridad']);
$nombre_cliente  = esc_html($args['nombre_cliente']);
$nombre_producto = esc_html($args['nombre_producto']);
$permalink       = esc_url($args['permalink']);
$campo_estado    = esc_attr($args['campo_estado']);
$estado_actual   = esc_html($args['estado_actual']);
$is_leader       = $args['is_leader'] ?? false;
$operarios_del_sector = $args['operarios_sector'] ?? [];
$asignado_a_id   = $args['asignado_a_id'] ?? 0;
$asignado_a_name = $args['asignado_a_name'] ?? 'Nadie';
$logged_in_user_id = $args['logged_in_user_id'] ?? 0;
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
            <?php if ($asignado_a_id) : ?>
                <p><strong>Asignado a:</strong> <?php echo esc_html($asignado_a_name); ?></p>
            <?php else : ?>
                <p><strong>Asignado a:</strong> <span class="ghd-text-muted">Sin asignar</span></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="order-card-actions">
        <?php if ($is_leader && !empty($operarios_del_sector)) :
            $current_assignee_id_for_select = $asignado_a_id ? $asignado_a_id : '0';
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

        <?php 
            $can_action_task = $is_leader || ($asignado_a_id === $logged_in_user_id && $asignado_a_id !== 0);

            if ($can_action_task) :
                // Lógica de botones estándar para TODOS los sectores
                if ($estado_actual === 'Pendiente') { ?>
                    <button class="ghd-btn ghd-btn-primary action-button"
                            data-order-id="<?php echo $post_id; ?>"
                            data-field="<?php echo $campo_estado; ?>"
                            data-value="En Progreso"
                            data-assignee-id="<?php echo esc_attr($logged_in_user_id); ?>">
                        <i class="fa-solid fa-play"></i> Iniciar Tarea
                    </button>
                <?php } elseif ($campo_estado === 'estado_embalaje' && $estado_actual === 'En Progreso') { ?>
                    <button class="ghd-btn ghd-btn-success action-button open-complete-task-modal" 
                            data-order-id="<?php echo $post_id; ?>" 
                            data-field="<?php echo $campo_estado; ?>" 
                            data-assignee-id="<?php echo esc_attr($asignado_a_id); ?>">
                        <i class="fa-solid fa-check"></i>
                        <span>Completar Tarea</span>
                    </button>
                <?php } elseif ($estado_actual === 'En Progreso') { ?>
                    <button class="ghd-btn ghd-btn-success action-button" 
                            data-order-id="<?php echo $post_id; ?>" 
                            data-field="<?php echo $campo_estado; ?>" 
                            data-value="Completado">
                        <i class="fa-solid fa-check"></i>
                        <span>Completar Tarea</span>
                    </button>
                <?php }
            endif; 
        ?>
        <a href="<?php echo $permalink; ?>" class="ghd-btn ghd-btn-secondary ghd-btn-small">Ver Detalles</a>
    </div>
</div>

<!-- Modal para Registrar Detalles de Tarea (se oculta por defecto) -->
<div id="complete-task-modal-<?php echo $post_id; ?>" class="ghd-modal">
    <div class="ghd-modal-content">
        <span class="close-button" data-modal-id="complete-task-modal-<?php echo $post_id; ?>">&times;</span>
        <h3>Registrar Detalles y Completar Tarea: <?php echo $titulo; ?></h3>
        <form class="complete-task-form" data-order-id="<?php echo $post_id; ?>" data-field="<?php echo $campo_estado; ?>" enctype="multipart/form-data">
            <div class="form-group">
                <label for="foto_tarea_<?php echo $post_id; ?>">Foto de la Tarea (Opcional):</label>
                <input type="file" id="foto_tarea_<?php echo $post_id; ?>" name="foto_tarea" accept="image/*">
            </div>
            <div class="form-group">
                <label for="observaciones_tarea_<?php echo $post_id; ?>">Observaciones (Opcional):</label>
                <textarea id="observaciones_tarea_<?php echo $post_id; ?>" name="observaciones_tarea_completa" rows="4"></textarea>
            </div>
            <?php 
            if ($campo_estado === 'estado_embalaje') : 
                $embalaje_models = ghd_get_embalaje_models_for_select();
                $cantidad_producto_en_pedido = (int) get_field('cantidad_unidades_producto', $post_id);
                if ($cantidad_producto_en_pedido === 0) {
                    $cantidad_producto_en_pedido = 1;
                }
            ?>
                <hr style="margin: 20px 0; border-color: #eee;">
                <h4>Detalles de Embalaje</h4>
                <div class="form-group">
                    <label for="operario_embalaje_<?php echo $post_id; ?>">Operario que Embaló:</label>
                    <select id="operario_embalaje_<?php echo $post_id; ?>" name="operario_embalaje_id" required>
                        <option value="">Selecciona un operario</option>
                        <?php if (!empty($operarios_del_sector)) : ?>
                            <?php foreach ($operarios_del_sector as $operario) : ?>
                                <option value="<?php echo esc_attr($operario->ID); ?>">
                                    <?php echo esc_html($operario->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="modelo_embalado_<?php echo $post_id; ?>">Modelo de Producto Embalado:</label>
                    <select id="modelo_embalado_<?php echo $post_id; ?>" name="modelo_embalado_id" required>
                        <option value="" data-points="0">Selecciona un modelo</option>
                        <?php if (!empty($embalaje_models)) : ?>
                            <?php foreach ($embalaje_models as $model) : ?>
                                <option value="<?php echo esc_attr($model->id); ?>" data-points="<?php echo esc_attr($model->points); ?>">
                                    <?php echo esc_html($model->title); ?> (<?php echo esc_html($model->points); ?> puntos)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div> 
                <div class="form-group">
                    <label for="cantidad_embalada_<?php echo $post_id; ?>">Cantidad Embalada:</label>
                    <input type="number" id="cantidad_embalada_<?php echo $post_id; ?>" name="cantidad_embalada" min="1" value="<?php echo esc_attr($cantidad_producto_en_pedido); ?>" required>
                </div>
                <p style="font-size: 0.9em; color: #555; margin-top: 10px;">Puntos estimados para esta tarea: <strong id="puntos_estimados_<?php echo $post_id; ?>"></strong></p>
                <script>
                if (typeof initEmbalajeModalPoints === 'function') {
                    initEmbalajeModalPoints(document.getElementById('complete-task-modal-<?php echo $post_id; ?>'), <?php echo $post_id; ?>);
                }
                </script>
            <?php endif; ?>
            <button type="submit" class="ghd-btn ghd-btn-success"><i class="fa-solid fa-check"></i> Completar Tarea</button>
        </form>
    </div>
</div>