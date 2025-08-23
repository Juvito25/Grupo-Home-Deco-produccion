<?php get_header(); ?>

<div class="ghd-app-wrapper">
    <!-- Usamos el mismo sidebar del sector para consistencia -->
    <aside class="ghd-sidebar">
        <div class="sidebar-header">
            <h1 class="logo">Mi Puesto</h1>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <!-- ESTA ES LA LÍNEA CORRECTA Y A PRUEBA DE FALLOS -->
                <!-- ESTA ES LA LÍNEA FINAL Y A PRUEBA DE TODO -->
                <li><a href="<?php echo esc_url(ghd_get_sector_dashboard_url()); ?>"><i class="fa-solid fa-inbox"></i> <span>Volver a Mis Tareas</span></a></li>
                <!-- Puedes añadir más enlaces aquí si es necesario -->
            </ul>
        </nav>
    </aside>

    <main class="ghd-main-content">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        
            <header class="ghd-main-header">
                <h2>Detalles del Pedido: <?php the_title(); ?></h2>
            </header>

            <div class="ghd-single-order-grid">
                <div class="ghd-card">
                    <h3 class="card-section-title">Información del Pedido</h3>
                    <p><strong>Producto:</strong> <?php echo esc_html(get_field('nombre_producto')); ?></p>
                    <p><strong>Estado Actual:</strong> <?php echo esc_html(get_field('estado_pedido')); ?></p>
                    <p><strong>Prioridad:</strong> <?php echo esc_html(get_field('prioridad_pedido')); ?></p>
                    <p><strong>Fecha del Pedido:</strong> <?php echo esc_html(get_field('fecha_pedido')); ?></p>
                </div>

                <div class="ghd-card">
                    <h3 class="card-section-title">Información del Cliente</h3>
                    <p><strong>Nombre:</strong> <?php echo esc_html(get_field('nombre_cliente')); ?></p>
                    <!-- Aquí podríamos añadir más campos del cliente en el futuro -->
                </div>
            </div>

        <?php endwhile; endif; ?>
    </main>
</div>

<?php get_footer(); ?>