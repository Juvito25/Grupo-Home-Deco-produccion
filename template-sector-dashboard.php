<?php /* Template Name: GHD - Panel de Sector (V3 Estable y Corregido) */
if (!is_user_logged_in()) { auth_redirect(); } if (current_user_can('manage_options')) { $url = get_permalink(get_page_by_path('panel-de-control')); wp_redirect($url); exit; } get_header();
$user = wp_get_current_user(); $role = $user->roles[0] ?? ''; 
$role_to_sector_map = ['rol_carpinteria' => 'Carpintería', 'rol_costura' => 'Costura', 'rol_tapiceria' => 'Tapicería', 'rol_logistica' => 'Logística'];
$sector = $role_to_sector_map[$role] ?? '';
?>
<div class="ghd-app-wrapper">
    <?php get_template_part('template-parts/sidebar-sector'); ?>
    <main class="ghd-main-content">
        <header class="ghd-main-header">
            <div class="header-title-wrapper">
                <button id="mobile-menu-toggle" class="ghd-btn-icon"><i class="fa-solid fa-bars"></i></button>
                <h2>Tareas de <?php echo esc_html($sector); ?></h2>
            </div>
            <div class="header-actions">
                <button id="ghd-refresh-tasks" class="ghd-btn ghd-btn-secondary"><i class="fa-solid fa-sync"></i> <span>Refrescar</span></button>
            </div>
        </header>
        <div class="ghd-sector-tasks-grid">
            <?php $query = new WP_Query(['post_type' => 'orden_produccion', 'posts_per_page' => -1, 'meta_query' => [['key' => 'sector_actual', 'value' => $sector]]]);
            if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post();
                $p = get_field('prioridad_pedido'); $pc = ($p=='Alta')?'tag-red':(($p=='Media')?'tag-yellow':'tag-green');
            ?>
            <div class="ghd-task-card" id="order-<?php echo get_the_ID(); ?>">
                <div class="card-header"><h3><?php the_title(); ?></h3><span class="ghd-tag <?php echo $pc; ?>"><?php echo esc_html($p); ?></span></div>
                <div class="card-body">
                    <p><strong>Cliente:</strong> <?php echo esc_html(get_field('nombre_cliente')); ?></p>
                    <p><strong>Producto:</strong> <?php echo esc_html(get_field('nombre_producto')); ?></p>
                </div>
                <div class="card-footer">
                    <a href="<?php the_permalink(); ?>" class="ghd-btn ghd-btn-secondary">Detalles</a>
                    <!-- CORRECCIÓN CLAVE: El nonce ahora es el específico y correcto ('ghd_move_order_nonce') -->
                    <button class="ghd-btn ghd-btn-primary move-to-next-sector-btn" data-order-id="<?php echo get_the_ID(); ?>" data-nonce="<?php echo wp_create_nonce('ghd_move_order_nonce'); ?>">Mover</button>
                </div>
            </div>
            <?php endwhile; else: echo '<p>No tienes tareas asignadas.</p>'; endif; wp_reset_postdata(); ?>
        </div>
    </main>
</div>
<?php get_footer(); ?>