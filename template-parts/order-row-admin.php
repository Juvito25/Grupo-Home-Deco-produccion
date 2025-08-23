<?php
/**
 * Template Part para mostrar una fila de pedido en la tabla del administrador.
 */

// Obtenemos todos los datos del pedido actual dentro del bucle
$estado        = get_field('estado_pedido');
$prioridad     = get_field('prioridad_pedido');
$sector_actual = get_field('sector_actual');

// Lógica para asignar clases de color a la etiqueta de prioridad
$prioridad_class = 'tag-green'; // Baja por defecto
if ($prioridad == 'Alta') {
    $prioridad_class = 'tag-red';
} elseif ($prioridad == 'Media') {
    $prioridad_class = 'tag-yellow';
}

// Lógica para asignar clases de color a la etiqueta de estado
$estado_class = 'tag-gray'; // Pendiente por defecto
if (in_array($estado, ghd_get_sectores_produccion())) { // Usamos la función global de sectores
    $estado_class = 'tag-blue';
} elseif ($estado == 'Completado') {
    $estado_class = 'tag-green';
}
?>

<tr>
    <td><input type="checkbox"></td>
    <td><strong><?php echo esc_html(get_the_title()); ?></strong></td>
    <td><?php echo esc_html(get_field('nombre_cliente')); ?></td>
    <td><span class="ghd-tag <?php echo $estado_class; ?>"><?php echo esc_html($estado); ?></span></td>
    <td><span class="ghd-tag <?php echo $prioridad_class; ?>"><?php echo esc_html($prioridad); ?></span></td>
    <td><?php echo esc_html($sector_actual); ?></td>
    <td><?php echo esc_html(get_field('fecha_pedido')); ?></td>
    <td class="actions-cell">
        <div class="actions-dropdown">
            <button class="ghd-btn-icon actions-toggle" data-order-id="<?php echo get_the_ID(); ?>">
                <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
            <div class="actions-menu">
                <div class="actions-menu-group">
                    <span class="actions-menu-title">Cambiar Prioridad</span>
                    <a href="#" class="action-link" data-action="change_priority" data-value="Alta">Alta</a>
                    <a href="#" class="action-link" data-action="change_priority" data-value="Media">Media</a>
                    <a href="#" class="action-link" data-action="change_priority" data-value="Baja">Baja</a>
                </div>
                <div class="actions-menu-group">
                    <span class="actions-menu-title">Asignar Sector</span>
                    <?php
                    $sectores = ghd_get_sectores_produccion();
                    foreach ($sectores as $sector) {
                        if ($sector === $sector_actual) {
                            echo '<span class="action-link is-current">' . esc_html($sector) . ' (Actual)</span>';
                        } else {
                            echo '<a href="#" class="action-link" data-action="change_sector" data-value="' . esc_attr($sector) . '">' . esc_html($sector) . '</a>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </td>
</tr>