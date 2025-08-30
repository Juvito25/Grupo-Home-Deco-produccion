<?php
/**
 * Template Name: GHD - Generar Remito
 * Descripción: Genera un remito imprimible para un pedido específico.
 */

// Asegurarse de que el usuario está logueado y es administrador
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página.', 'Acceso Denegado', ['response' => 403]);
}

// Obtener el ID del pedido de la URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id || get_post_type($order_id) !== 'orden_produccion') {
    wp_die('ID de pedido no válido o pedido no encontrado.', 'Error de Pedido', ['response' => 404]);
}

// Recuperar los datos del pedido
$pedido_title = get_the_title($order_id);
$pedido_date = get_the_date('d/m/Y', $order_id);

$cliente_nombre = get_field('nombre_cliente', $order_id);
$cliente_email = get_field('cliente_email', $order_id);
$cliente_telefono = get_field('cliente_telefono', $order_id);
$cliente_id = get_field('id_de_cliente', $order_id); // Asumo este nombre de campo

$producto_nombre = get_field('nombre_producto', $order_id);
$producto_especificaciones = get_field('especificaciones_producto', $order_id);

$direccion_entrega = get_field('direccion_de_entrega', $order_id); // Asumo este nombre de campo
$instrucciones_entrega = get_field('instrucciones_de_entrega', $order_id); // Asumo este nombre de campo

$valor_total = get_field('valor_total_del_pedido', $order_id); // Asumo este nombre de campo
$sena_pagada = get_field('sena_pagada', $order_id); // Asumo este nombre de campo
$saldo_pendiente = ($valor_total && $sena_pagada) ? ($valor_total - $sena_pagada) : 'N/A';

// Cargar la cabecera mínima para un remito
// No queremos el header completo del tema, solo lo necesario para el HTML y CSS
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Remito #<?php echo esc_html($pedido_title); ?></title>
    <?php wp_head(); // Para encolar scripts y estilos de WordPress si son necesarios ?>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f6f8;
            color: #333;
            -webkit-print-color-adjust: exact !important; /* Para que los colores de fondo se impriman */
            color-adjust: exact !important;
        }
        .remito-container {
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
            padding: 30px;
            position: relative;
        }
        .remito-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .remito-logo {
            max-height: 60px;
        }
        .remito-title {
            font-size: 2em;
            font-weight: bold;
            color: #4A7C59;
            margin: 0;
        }
        .remito-details {
            text-align: right;
            font-size: 0.9em;
        }
        .remito-details p {
            margin: 0;
            line-height: 1.4;
        }
        .remito-section {
            margin-bottom: 25px;
            border: 1px solid #eee;
            border-radius: 6px;
            padding: 15px;
            background-color: #fcfcfc;
        }
        .remito-section h4 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #4A7C59;
            border-bottom: 1px dashed #eee;
            padding-bottom: 10px;
        }
        .remito-section p, .remito-section ul {
            margin: 0 0 8px 0;
            line-height: 1.5;
            font-size: 0.95em;
        }
        .remito-section strong {
            color: #333;
        }
        .remito-product-image {
            max-width: 100px;
            height: auto;
            border-radius: 4px;
            float: left;
            margin-right: 15px;
        }
        .remito-product-info::after {
            content: "";
            display: table;
            clear: both;
        }
        .remito-totals {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 0.95em;
            background-color: #fcfcfc;
        }
        .remito-totals th, .remito-totals td {
            border: 1px solid #eee;
            padding: 8px 12px;
            text-align: left;
        }
        .remito-totals th {
            background-color: #f8f8f8;
            font-weight: bold;
            color: #555;
        }
        .remito-totals td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .remito-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 0.85em;
            color: #777;
        }

        /* Estilos de impresión */
        @media print {
            body {
                background-color: #fff;
                padding: 0;
            }
            .remito-container {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 0;
                border-radius: 0;
            }
            .remito-header, .remito-section, .remito-totals, .remito-footer {
                page-break-inside: avoid; /* Evita que estos bloques se corten entre páginas */
            }
        }
    </style>
</head>
<body class="ghd-remito-body">
    <div class="remito-container">
        <div class="remito-header">
            <img src="<?php echo get_stylesheet_directory_uri(); ?>/img/logo.png" alt="Grupo Home Deco Logo" class="remito-logo">
            <div class="remito-details">
                <h1 class="remito-title">REMITO</h1>
                <p><strong>Número de Pedido:</strong> <?php echo esc_html($pedido_title); ?></p>
                <p><strong>Fecha:</strong> <?php echo esc_html($pedido_date); ?></p>
                <p><strong>Generado por:</strong> <?php echo esc_html(wp_get_current_user()->display_name); ?></p>
            </div>
        </div>

        <div class="remito-section">
            <h4>Información del Cliente</h4>
            <p><strong>Nombre:</strong> <?php echo esc_html($cliente_nombre); ?></p>
            <p><strong>Email:</strong> <?php echo esc_html($cliente_email); ?></p>
            <p><strong>Teléfono:</strong> <?php echo esc_html($cliente_telefono); ?></p>
            <?php if ($cliente_id) : ?><p><strong>ID Cliente:</strong> <?php echo esc_html($cliente_id); ?></p><?php endif; ?>
        </div>

        <div class="remito-section">
            <h4>Información del Producto</h4>
            <div class="remito-product-info">
                <?php 
                $product_image = get_field('imagen_del_producto', $order_id);
                if ($product_image) {
                    $image_url = is_array($product_image) ? $product_image['url'] : $product_image;
                    $image_alt = is_array($product_image) && !empty($product_image['alt']) ? $product_image['alt'] : $producto_nombre;
                    echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '" class="remito-product-image">';
                }
                ?>
                <p><strong>Producto:</strong> <?php echo esc_html($producto_nombre); ?></p>
                <?php if ($producto_especificaciones) : ?><p><strong>Especificaciones:</strong> <?php echo nl2br(esc_html($producto_especificaciones)); ?></p><?php endif; ?>
                <!-- Aquí podrías añadir cantidad, precio unitario, etc., si los tienes -->
            </div>
        </div>

        <div class="remito-section">
            <h4>Detalles de Entrega</h4>
            <p><strong>Dirección:</strong> <?php echo nl2br(esc_html($direccion_entrega)); ?></p>
            <?php if ($instrucciones_entrega) : ?><p><strong>Instrucciones:</strong> <?php echo nl2br(esc_html($instrucciones_entrega)); ?></p><?php endif; ?>
        </div>

        <div class="remito-section">
            <h4>Resumen Financiero</h4>
            <table class="remito-totals">
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Valor Total del Pedido</td>
                        <td>$<?php echo number_format($valor_total, 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>Seña Pagada</td>
                        <td>$<?php echo number_format($sena_pagada, 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Saldo Pendiente</strong></td>
                        <td><strong>$<?php echo number_format($saldo_pendiente, 2, ',', '.'); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="remito-footer">
            <p>Grupo Home Deco - Todos los derechos reservados.</p>
            <p>Generado el <?php echo date('d/m/Y H:i'); ?></p>
        </div>
    </div>
    <?php wp_footer(); // Para scripts y estilos de WordPress si son necesarios ?>
    <script>
        // Imprimir automáticamente al cargar la página
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>