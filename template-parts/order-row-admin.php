<?php
/**
 * Template Part para mostrar una fila de pedido en la tabla del administrador.
 * Este archivo es un componente "tonto": recibe todos sus datos a través de la variable $args
 * que se le pasa desde el archivo `template-admin-dashboard.php`.
 * No contiene lógica de obtención de datos.
 */

// Si la variable $args no existe, no hacemos nada para evitar errores.
if (!isset($args) || !is_array($args)) {
    return;
}
?>

<tr>
    <td><input type="checkbox"></td>
    <td><strong><?php echo esc_html($args['titulo']); ?></strong></td>
    <td><?php echo esc_html($args['nombre_cliente']); ?></td>
    <td><?php echo esc_html($args['nombre_producto']); ?></td>
    <td><span class="ghd-tag <?php echo esc_attr($args['estado_class']); ?>"><?php echo esc_html($args['estado']); ?></span></td>
    <td><span class="ghd-tag <?php echo esc_attr($args['prioridad_class']); ?>"><?php echo esc_html($args['prioridad']); ?></span></td>
    <td><?php echo esc_html($args['sector_actual']); ?></td>
    <td><?php echo esc_html($args['fecha_del_pedido']); ?></td>
    <td class="actions-cell">
        <div class="actions-dropdown">
            <button class="ghd-btn-icon actions-toggle" data-order-id="<?php echo esc_attr($args['post_id']); ?>">
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
                        if ($sector === $args['sector_actual']) {
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