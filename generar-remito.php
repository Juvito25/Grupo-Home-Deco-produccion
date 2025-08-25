<?php
/**
 * Script para generar el Remito en PDF.
 * Versión 2.2 - Final y Funcional.
 */

// 1. CARGADOR DE WORDPRESS INTELIGENTE
$wp_load_path = __DIR__;
while (!file_exists($wp_load_path . '/wp-load.php')) {
    $wp_load_path = dirname($wp_load_path);
    if (empty($wp_load_path) || $wp_load_path === '/') {
        die('Error crítico: No se pudo localizar el archivo wp-load.php.');
    }
}
require_once($wp_load_path . '/wp-load.php');

// 2. SEGURIDAD Y VALIDACIÓN
if (!is_user_logged_in() || !isset($_GET['pedido_id'])) {
    wp_die('Acceso no autorizado.');
}
$pedido_id = intval($_GET['pedido_id']);
if (!$pedido_id) {
    wp_die('ID de pedido no válido.');
}

// 3. CARGAR LA LIBRERÍA FPDF (CON RUTA ABSOLUTA)
require_once(__DIR__ . '/lib/fpdf/fpdf.php');

// 4. OBTENER LOS DATOS DEL PEDIDO
$codigo_pedido = get_the_title($pedido_id);
$nombre_cliente = get_field('nombre_cliente', $pedido_id);
$direccion = get_field('direccion_de_entrega', $pedido_id);
$telefono = get_field('cliente_telefono', $pedido_id);
$nombre_producto = get_field('nombre_producto', $pedido_id);
$especificaciones = get_field('especificaciones_producto', $pedido_id);
$valor_total = get_field('valor_total_del_pedido', $pedido_id) ?: 0;
$sena_pagada = get_field('sena_pagada', $pedido_id) ?: 0;
$saldo_pendiente = $valor_total - $sena_pagada;

// 5. CREAR EL PDF
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
// --- INICIO DEL BLOQUE PARA AÑADIR EL LOGO ---
// Obtenemos la ruta absoluta al logo para evitar errores.
$logo_path = get_stylesheet_directory() . '/img/logo.png';

// Comprobamos si el archivo del logo existe antes de intentar insertarlo.
if (file_exists($logo_path)) {
    // Image(ruta, x, y, ancho)
    // x=10 (10mm desde la izquierda), y=6 (6mm desde arriba), ancho=40 (40mm de ancho)
    // La altura se calcula automáticamente para mantener la proporción.
    $pdf->Image($logo_path, 10, 6, 40);
    // Añadimos un salto de línea más grande para dejar espacio al logo.
    $pdf->Ln(20);
}
// --- FIN DEL BLOQUE PARA AÑADIR EL LOGO ---
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'REMITO DE ENTREGA', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 10, 'Pedido Nro:');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, $codigo_pedido, 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 10, 'Fecha:');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, date('d/m/Y'), 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 10, 'Cliente:');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, utf8_decode($nombre_cliente), 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 10, utf8_decode('Dirección:'));
$pdf->SetFont('Arial', '', 12);
$pdf->MultiCell(0, 10, utf8_decode($direccion));

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 10, utf8_decode('Teléfono:'));
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, $telefono, 0, 1);
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(130, 10, 'Producto', 1, 0, 'C');
$pdf->Cell(60, 10, 'Cantidad', 1, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(130, 10, utf8_decode($nombre_producto), 1, 0);
$pdf->Cell(60, 10, '1', 1, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Especificaciones:', 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->MultiCell(0, 10, utf8_decode($especificaciones));
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(130, 10, 'Total del Pedido:', 0, 0, 'R');
$pdf->Cell(60, 10, '$' . number_format($valor_total, 2), 0, 1, 'R');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(130, 10, 'Pagado a cuenta:', 0, 0, 'R');
$pdf->Cell(60, 10, '$' . number_format($sena_pagada, 2), 0, 1, 'R');

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(130, 10, 'SALDO A ABONAR:', 0, 0, 'R');
$pdf->Cell(60, 10, '$' . number_format($saldo_pendiente, 2), 0, 1, 'R');
$pdf->Ln(20);

$pdf->Cell(0, 10, '-----------------------------------', 0, 1, 'C');
$pdf->Cell(0, 10, 'Firma, Aclaracion y DNI', 0, 1, 'C');

// 6. ENVIAR EL PDF AL NAVEGADOR
$pdf->Output('D', 'Remito-' . $codigo_pedido . '.pdf');
exit;