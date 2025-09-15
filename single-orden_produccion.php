<?php
/**
 * The template for displaying all single posts of the 'orden_produccion' CPT.
 * V2 - Añadida sección de Control Administrativo para Macarena.
 */

get_header();

$current_user_can_control = current_user_can('control_final_macarena') || current_user_can('manage_options');
?>

<div class="ghd-app-wrapper">
    <?php 
    if (current_user_can('manage_options')) {
        get_template_part('template-parts/sidebar-admin');
    } else {
        get_template_part('template-parts/sidebar-sector');
    }
    ?>

    <main class="ghd-main-content">
        <?php while ( have_posts() ) : the_post(); 
            $current_post_id = get_the_ID();
        ?>
        <header class="ghd-main-header">
            <div class="header-title-wrapper header-with-back">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <h2>Detalles del Pedido: <?php the_title(); ?></h2>
                <a href="javascript:history.back()" class="ghd-btn ghd-btn-secondary"><i class="fa-solid fa-arrow-left"></i> Volver</a>
            </div>
        </header>

        <div class="ghd-main-content-body">
            
            <!-- NUEVA SECCIÓN DE CONTROL ADMINISTRATIVO PARA MACARENA -->
            <?php if ($current_user_can_control && get_field('estado_pedido', $current_post_id) === 'Pendiente de Cierre Admin') : ?>
                <div class="ghd-card admin-control-card">
                    <h3 class="card-section-title">Control Administrativo y Cierre</h3>
                    <form id="admin-control-form" data-order-id="<?php echo $current_post_id; ?>">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="estado_pago">Estado del Pago</label>
                                <select name="estado_pago" id="estado_pago">
                                    <?php $current_pago = get_field('estado_pago', $current_post_id); ?>
                                    <option value="Pendiente" <?php selected($current_pago, 'Pendiente'); ?>>Pendiente</option>
                                    <option value="Pagado" <?php selected($current_pago, 'Pagado'); ?>>Pagado</option>
                                    <option value="Pago Parcial" <?php selected($current_pago, 'Pago Parcial'); ?>>Pago Parcial</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="foto_remito_firmado">Foto del Remito Firmado</label>
                                <input type="file" name="foto_remito_firmado" id="foto_remito_firmado" accept="image/*">
                                <?php 
                                $remito_id = get_field('foto_remito_firmado', $current_post_id);
                                if ($remito_id) {
                                    echo '<a href="' . wp_get_attachment_url($remito_id) . '" target="_blank" style="font-size: 0.9em; display: block; margin-top: 5px;">Ver remito actual</a>';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="notas_administrativas">Notas Administrativas</label>
                            <textarea name="notas_administrativas" id="notas_administrativas" rows="3"><?php echo esc_textarea(get_field('notas_administrativas', $current_post_id)); ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="ghd-btn ghd-btn-primary"><i class="fa-solid fa-save"></i> Guardar Cambios</button>
                            <button type="button" class="ghd-btn ghd-btn-success archive-order-btn" data-order-id="<?php echo $current_post_id; ?>"><i class="fa-solid fa-archive"></i> Archivar Pedido</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
            <!-- FIN DE LA SECCIÓN DE CONTROL ADMINISTRATIVO -->

            <div class="ghd-details-grid">
                <div class="details-main ghd-card">
                    <h3 class="card-section-title">Información del Cliente</h3>
                    <p><strong>Nombre:</strong> <?php echo esc_html(get_field('nombre_cliente', $current_post_id)); ?></p>
                    <p><strong>Email:</strong> <?php echo esc_html(get_field('cliente_email', $current_post_id)); ?></p>
                    <p><strong>Teléfono:</strong> <?php echo esc_html(get_field('cliente_telefono', $current_post_id)); ?></p>
                </div>

                <div class="details-sidebar ghd-card">
                    <h3 class="card-section-title">Estado General</h3>
                    <p><strong>Estado:</strong> <?php echo esc_html(get_field('estado_pedido', $current_post_id)); ?></p>
                    <p><strong>Prioridad:</strong> <?php echo esc_html(get_field('prioridad_pedido', $current_post_id)); ?></p>
                </div>

                <div class="details-main ghd-card product-details-section">
                    <?php 
                    $product_image_id = get_field('imagen_del_producto', $current_post_id);
                    ?>
                    <?php if ($product_image_id) : ?>
                        <div class="product-image-wrapper">
                            <?php echo wp_get_attachment_image($product_image_id, 'medium_large'); ?>
                        </div>
                    <?php endif; ?>

                    <div class="product-info-wrapper">
                        <h3 class="card-section-title">Información del Producto</h3>
                        <div class="product-main-info">
                            <p><strong>Producto:</strong> <?php echo esc_html(get_field('nombre_producto', $current_post_id)); ?></p>
                            <?php if ($material = get_field('material_del_producto', $current_post_id)) : ?><p><strong>Material:</strong> <?php echo esc_html($material); ?></p><?php endif; ?>
                            <?php if ($color = get_field('color_del_producto', $current_post_id)) : ?>
                                <p><strong>Color:</strong> <span class="color-swatch" style="background-color: <?php echo esc_attr($color); ?>;"></span> <?php echo esc_html($color); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if ($observaciones = get_field('observaciones_personalizacion', $current_post_id)) : ?>
                            <div class="product-specifications" style="margin-top: 1rem;">
                                <p><strong>Observaciones:</strong></p>
                                <p><?php echo nl2br(esc_html($observaciones)); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="details-sidebar ghd-card">
                    <h3 class="card-section-title">Sub-estados de Producción</h3>
                    <ul class="sub-status-list">
                        <?php 
                        $sectores = ['carpinteria', 'corte', 'costura', 'tapiceria', 'embalaje', 'logistica'];
                        foreach ($sectores as $sector) : ?>
                            <li>
                                <strong><?php echo ucfirst($sector); ?>:</strong> 
                                <span><?php echo esc_html(get_field('estado_' . $sector, $current_post_id) ?: 'No Asignado'); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

        </div>
        <?php endwhile; ?>
    </main>
</div>

<?php get_footer(); ?>