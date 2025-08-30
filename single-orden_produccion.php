<?php
/**
 * The template for displaying all single posts of the 'orden_produccion' CPT.
 */

get_header(); ?>

<div class="ghd-app-wrapper">
    <?php 
    // Decidir qué sidebar mostrar, si el de admin o el de sector
    if (current_user_can('manage_options')) {
        get_template_part('template-parts/sidebar-admin');
    } else {
        get_template_part('template-parts/sidebar-sector');
    }
    ?>

    <main class="ghd-main-content">
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <h2>Detalles del Pedido: <?php the_title(); ?></h2>
                
                <?php
                // Botón "Volver" inteligente
                $referer_url = wp_get_referer();
                // Si viene de un panel conocido, intentar volver a él
                if (strpos($referer_url, 'template-admin-dashboard.php') !== false || strpos($referer_url, 'template-sector-dashboard.php') !== false || strpos($referer_url, 'template-pedidos-archivados.php') !== false) { // Añadido para pedidos archivados
                    $back_url = $referer_url;
                } else {
                    // Fallback si no se detecta el referer o es desconocido
                    if (current_user_can('manage_options')) {
                        // Admin, volver al panel de admin
                        $admin_dashboard_page = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-admin-dashboard.php']);
                        $back_url = !empty($admin_dashboard_page) ? get_permalink($admin_dashboard_page[0]) : home_url();
                    } else {
                        // Usuario de sector, volver a su panel de sector
                        $sector_dashboard_page = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => '_wp_page_template', 'meta_value' => 'template-sector-dashboard.php']);
                        $back_url = !empty($sector_dashboard_page) ? get_permalink($sector_dashboard_page[0]) : home_url();
                        
                        // Añadir el parámetro de sector si es un usuario de sector y viene del sidebar
                        $current_user_roles = wp_get_current_user()->roles;
                        $current_user_role = !empty($current_user_roles) ? $current_user_roles[0] : '';
                        $mapa_roles = ghd_get_mapa_roles_a_campos(); 
                        if (array_key_exists($current_user_role, $mapa_roles)) {
                            $sector_name = ucfirst(str_replace(['rol_', '_'], ' ', $current_user_role));
                            $clean_sector_name = strtolower(str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $clean_sector_name));
                            $back_url = add_query_arg('sector', urlencode($clean_sector_name), $back_url);
                        }
                    }
                }
                ?>
                <a href="<?php echo esc_url($back_url); ?>" class="ghd-btn ghd-btn-secondary"><i class="fa-solid fa-arrow-left"></i> Volver</a>
            </div>
            <div class="header-actions">
                <!-- Otros botones de acción si los hubiera en detalles -->
            </div>
        </header>

        <div class="ghd-main-content-body">
            <?php while ( have_posts() ) : the_post(); 
                $current_post_id = get_the_ID();
            ?>

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

                    <!-- NUEVA ESTRUCTURA PARA "INFORMACIÓN DEL PRODUCTO" -->
                    <div class="details-main ghd-card product-details-section">
                        <?php 
                        $product_image = get_field('imagen_del_producto', $current_post_id);
                        $image_url = '';
                        $image_alt = '';

                        if ($product_image) {
                            $image_url = is_array($product_image) ? $product_image['url'] : $product_image;
                            $image_alt = is_array($product_image) && !empty($product_image['alt']) ? $product_image['alt'] : get_the_title($current_post_id) . ' - ' . get_field('nombre_producto', $current_post_id);
                        }
                        // --- Nuevos campos de Material, Color, Observaciones ---
                        $material_producto = get_field('material_del_producto', $current_post_id); // Asumo este nombre de campo ACF
                        $color_producto = get_field('color_del_producto', $current_post_id);     // Asumo este nombre de campo ACF
                        $observaciones_personalizacion = get_field('observaciones_personalizacion', $current_post_id); // Asumo este nombre de campo ACF
                        ?>
                        
                        <?php if ($product_image) : ?>
                            <div class="product-image-wrapper">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>">
                            </div>
                        <?php endif; ?>

                        <div class="product-info-wrapper">
                            <h3 class="card-section-title">Información del Producto</h3>
                            <div class="product-main-info">
                                <p><strong>Producto:</strong> <?php echo esc_html(get_field('nombre_producto', $current_post_id)); ?></p>
                                <?php if ($material_producto) : ?><p><strong>Material:</strong> <?php echo esc_html($material_producto); ?></p><?php endif; ?>
                                <?php if ($color_producto) : ?>
                                    <p>
                                        <strong>Color:</strong> 
                                        <span class="color-swatch" style="background-color: <?php echo esc_attr($color_producto); ?>; margin-left: 5px;"></span>
                                        <?php echo esc_html($color_producto); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <?php 
                            $especificaciones = get_field('especificaciones_producto', $current_post_id);
                            if ($especificaciones) : ?>
                                <div class="product-specifications">
                                    <p><strong>Especificaciones:</strong></p>
                                    <p><?php echo esc_html($especificaciones); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($observaciones_personalizacion) : ?>
                                <div class="product-specifications" style="margin-top: 1rem;">
                                    <p><strong>Observaciones de Personalización:</strong></p>
                                    <p><?php echo nl2br(esc_html($observaciones_personalizacion)); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- FIN DE LA NUEVA ESTRUCTURA -->


                    <div class="details-sidebar ghd-card">
                        <h3 class="card-section-title">Sub-estados de Producción</h3>
                        <ul class="sub-status-list">
                            <?php foreach (ghd_get_mapa_roles_a_campos() as $role_key => $field_key) : ?>
                                <li>
                                    <strong><?php echo ucfirst(str_replace(['estado_', '_'], ' ', $field_key)); ?>:</strong> 
                                    <span><?php echo esc_html(get_field($field_key, $current_post_id)); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

            <?php endwhile; // End of the loop. ?>
        </div>
    </main>
</div>

<?php get_footer(); ?>