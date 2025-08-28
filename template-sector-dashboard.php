<?php /* Template Name: GHD - Panel de Sector (V3 Estable) */
if (!is_user_logged_in()) { auth_redirect(); } get_header();
$user = wp_get_current_user(); $role = $user->roles[0] ?? '';
$mapa = ghd_get_mapa_roles_a_campos(); $campo_estado = $mapa[$role] ?? '';
$sector_name = ucfirst(str_replace(['rol_', '_'], ' ', $role));
?>
<div class="ghd-app-wrapper">
    <?php get_template_part('template-parts/sidebar-sector'); ?>
    <main class="ghd-main-content">
        <header class="ghd-main-header"><h2>Tareas de <?php echo esc_html($sector_name); ?></h2></header>
        <div class="ghd-sector-tasks-list">
            <?php if($campo_estado):
                $query = new WP_Query(['post_type' => 'orden_produccion', 'posts_per_page' => -1, 'meta_query' => [['key' => $campo_estado, 'value' => 'Pendiente']]]);
                if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post();
            ?>
            <div class="ghd-order-card">
                <div class="order-card-main">
                    <div class="order-card-header"><h3><?php the_title(); ?></h3></div>
                    <div class="order-card-body"><p><?php echo esc_html(get_field('nombre_producto')); ?></p></div>
                </div>
                <div class="order-card-actions">
                    <button class="ghd-btn action-button" data-order-id="<?php echo get_the_ID(); ?>" data-field="<?php echo esc_attr($campo_estado); ?>" data-value="En Progreso">Iniciar</button>
                    <button class="ghd-btn ghd-btn-primary action-button" data-order-id="<?php echo get_the_ID(); ?>" data-field="<?php echo esc_attr($campo_estado); ?>" data-value="Completado">Completar</button>
                </div>
            </div>
            <?php endwhile; else: echo '<p>No hay tareas pendientes.</p>'; endif; wp_reset_postdata(); endif; ?>
        </div>
    </main>
</div>
<?php get_footer(); ?>