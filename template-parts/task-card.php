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
                 <!-- Botón de acción principal: Iniciar Tarea / Marcar Completa -->
        <?php 
            // Lógica para determinar si el usuario actual puede realizar la acción en esta tarea.
            $can_action_task = false;
            if ($is_leader) { // Los líderes pueden actuar en cualquier tarea de su sector
                $can_action_task = true;
            } elseif ($asignado_a_id === $logged_in_user_id && $asignado_a_id !== 0) { // Operario, si la tarea está asignada a él
                $can_action_task = true;
            }

            if ($can_action_task) :
                // PRIORIDAD DE LOS BOTONES:
                // 1. Iniciar Tarea (si está Pendiente)
                // 2. Completar Tarea (si está En Progreso y es Embalaje, que tiene modal)
                // 3. Completar Tarea (si está En Progreso y NO es Embalaje, como Logística Líder)

                // --- 1. Botón "Iniciar Tarea" (Pendiente -> En Progreso) ---
                if ($estado_actual === 'Pendiente') : ?>
                    <button class="ghd-btn ghd-btn-primary action-button"
                            data-order-id="<?php echo $post_id; ?>"
                            data-field="<?php echo $campo_estado; ?>"
                            data-value="En Progreso"> <!-- Envía el valor 'En Progreso' -->
                        <i class="fa-solid fa-play"></i> Iniciar Tarea
                    </button>
                <?php 
                // --- 2. Botón "Completar Tarea" para Embalaje (SIEMPRE con modal) ---
                // Se muestra si el campo_estado es 'estado_embalaje' y la tarea está 'En Progreso'.
                elseif ($campo_estado === 'estado_embalaje' && $estado_actual === 'En Progreso') : 
                ?>
                    <button class="ghd-btn ghd-btn-success action-button open-complete-task-modal" 
                            data-order-id="<?php echo $post_id; ?>" 
                            data-field="<?php echo $campo_estado; ?>" 
                            data-assignee-id="<?php echo esc_attr($asignado_a_id); ?>">
                        <i class="fa-solid fa-check"></i>
                        <span>Completar Tarea</span>
                    </button>
                <?php 
                // --- 3. Botón "Completar Tarea" para CUALQUIER otro sector (SIN modal) ---
                // Se muestra si la tarea está 'En Progreso' (y no fue capturada por la condición de Embalaje).
                // Esto incluye a Carpintería, Corte, Costura, Tapicería y Logística Líder.
                elseif ($estado_actual === 'En Progreso') : ?>
                    <button class="ghd-btn ghd-btn-success action-button" 
                            data-order-id="<?php echo $post_id; ?>" 
                            data-field="<?php echo $campo_estado; ?>" 
                            data-value="Completado"> <!-- Envía el valor 'Completado' -->
                        <i class="fa-solid fa-check"></i>
                        <span>Completar Tarea</span>
                    </button>
                <?php endif; // Fin de las condiciones de botones ?>
            <?php endif; // Fin de if ($can_action_task) ?>
        <a href="<?php echo $permalink; ?>" class="ghd-btn ghd-btn-secondary ghd-btn-small">Ver Detalles</a>
    </div>
</div>

<!-- NUEVO: Modal para Registrar Detalles de Tarea (se oculta por defecto) -->
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
            // --- CAMPOS ESPECÍFICOS PARA EL SECTOR DE EMBALAJE ---
            if ($campo_estado === 'estado_embalaje') : 
                // Obtener los modelos de puntos
                $embalaje_models = ghd_get_embalaje_models_for_select();
                // Obtener operarios de embalaje (ya están en $operarios_del_sector si es líder)

                // --- NUEVO: Obtener la cantidad de unidades del producto principal del pedido ---
                $cantidad_producto_en_pedido = (int) get_field('cantidad_unidades_producto', $post_id);
                if ($cantidad_producto_en_pedido === 0) { // Asegurar que sea al menos 1 si no está definido
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
                        <option value="">Selecciona un modelo</option>
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
                // Lógica JS para actualizar los puntos estimados en el modal
                // --- CRÍTICO: Este script se ejecutará INMEDIATAMENTE al cargarse el HTML.
                // NO se envuelve en DOMContentLoaded, ya que el HTML puede cargarse vía AJAX.
                (function() { // Se envuelve en una función anónima autoejecutable para aislar el scope
                    const modal = document.getElementById('complete-task-modal-<?php echo $post_id; ?>');
                    if (modal) {
                        const modeloSelector = modal.querySelector('#modelo_embalado_<?php echo $post_id; ?>');
                        const cantidadInput = modal.querySelector('#cantidad_embalada_<?php echo $post_id; ?>');
                        const puntosEstimadosSpan = modal.querySelector('#puntos_estimados_<?php echo $post_id; ?>');

                        const updateEstimatedPoints = () => {
                            // --- DEBUG: Loguear valores para depurar ---
                            console.log('Update estimated points ejecutado para pedido ' + <?php echo $post_id; ?>);
                            console.log('Modelo Selector Value:', modeloSelector ? modeloSelector.value : 'N/A');
                            console.log('Cantidad Input Value:', cantidadInput ? cantidadInput.value : 'N/A');
                            // --- FIN DEBUG ---

                            if (!modeloSelector || !cantidadInput || modeloSelector.selectedIndex === -1 || modeloSelector.value === '') {
                                puntosEstimadosSpan.textContent = '0';
                                return;
                            }
                            const selectedOption = modeloSelector.options[modeloSelector.selectedIndex];
                            const modelPoints = parseInt(selectedOption.dataset.points || '0');
                            const quantity = parseInt(cantidadInput.value || '0');
                            const totalPoints = modelPoints * quantity;
                            puntosEstimadosSpan.textContent = totalPoints;
                        };

                        // Escuchar cambios en los selectores y el input de cantidad para actualizaciones dinámicas
                        modeloSelector.addEventListener('change', updateEstimatedPoints);
                        cantidadInput.addEventListener('input', updateEstimatedPoints);

                        // --- ¡CRÍTICO! Asegurar la inicialización de puntos cuando el modal se abre ---
                        // El evento 'ghdModalOpened' es el disparador principal.
                        modal.addEventListener('ghdModalOpened', updateEstimatedPoints); 
                        
                        // Forzar una inicialización inmediata si el modal ya tiene valores por defecto
                        // (ej. si el primer modelo está pre-seleccionado al cargar la tarjeta).
                        if (modeloSelector && modeloSelector.value !== '') { 
                           updateEstimatedPoints(); 
                        }
                    }
                })(); // <-- ¡Se autoejecuta inmediatamente!
                </script>
                <?php endif; // Fin de campos específicos para embalaje ?>
            <button type="submit" class="ghd-btn ghd-btn-success"><i class="fa-solid fa-check"></i> Completar Tarea</button>
        </form>
    </div>
</div>