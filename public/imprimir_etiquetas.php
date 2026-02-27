<?php
/**
 * ======================================================================
 * IMPRESIÓN DE ETIQUETAS (CÓDIGOS DE BARRAS)
 * ======================================================================
 * Requiere: composer require picqer/php-barcode-generator
 * v1.1 - Corregido: Eliminada columna inexistente
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'src/auth_check.php';
require_once 'src/funciones_db.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use Picqer\Barcode\BarcodeGeneratorPNG;

// 1. Obtener IDs (puede ser uno solo o varios separados por coma)
$ids_raw = $_GET['ids'] ?? '';
if (empty($ids_raw)) die("No se seleccionaron insumos.");

$ids = array_map('intval', explode(',', $ids_raw));
$ids = array_filter($ids); // Quitar ceros o vacíos

if (empty($ids)) die("IDs inválidos.");

// 2. Obtener datos de la BD
// Construimos los placeholders (?,?,?) dinámicamente
$placeholders = str_repeat('?,', count($ids) - 1) . '?';

// CORRECCIÓN: Se eliminó 'codigo_barras_custom' de la consulta
$sql = "SELECT id, nombre, sku FROM insumos WHERE id IN ($placeholders)";

$stmt = $pdo->prepare($sql);
$stmt->execute($ids);
$insumos = $stmt->fetchAll();

if (empty($insumos)) die("No se encontraron insumos.");

// 3. Generador de Barras
$generator = new BarcodeGeneratorPNG();

// 4. Construir HTML
$html = '
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .etiqueta-container {
            width: 100%;
            text-align: center;
        }
        .etiqueta {
            width: 48%; /* Dos columnas */
            float: left;
            border: 2px dashed #ccc;
            margin: 5px;
            padding: 10px;
            height: 140px; /* Altura fija */
            box-sizing: border-box;
            page-break-inside: avoid;
        }
        .nombre { font-size: 14px; font-weight: bold; height: 35px; overflow: hidden; margin-bottom: 5px; }
        .sku { font-size: 12px; color: #555; margin-bottom: 5px; }
        .barcode { margin-top: 5px; }
        .barcode img { height: 40px; width: 80%; }
        .clearfix { clear: both; }
    </style>
</head>
<body>
    <div class="etiqueta-container">';

foreach ($insumos as $item) {
    // Usamos el SKU como código. Si no tiene, usamos el ID con prefijo "ID-".
    $codigo = !empty($item['sku']) ? $item['sku'] : 'ID-' . str_pad((string)$item['id'], 6, '0', STR_PAD_LEFT);
    
    // Generar imagen en Base64
    try {
        $barcode_data = base64_encode($generator->getBarcode($codigo, $generator::TYPE_CODE_128));
        $imagen_html = '<img src="data:image/png;base64,' . $barcode_data . '">';
    } catch (Exception $e) {
        $imagen_html = 'ERROR CODIGO';
    }
    
    $html .= '
        <div class="etiqueta">
            <div class="nombre">' . substr(htmlspecialchars($item['nombre']), 0, 50) . '</div>
            <div class="sku">SKU: ' . htmlspecialchars($codigo) . '</div>
            <div class="barcode">
                ' . $imagen_html . '
            </div>
            <div style="font-size:10px; margin-top:2px;">' . htmlspecialchars($codigo) . '</div>
        </div>';
}

$html .= '
    </div>
</body>
</html>';

// 5. Renderizar PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("Etiquetas_Insumos.pdf", ["Attachment" => 0]);
?>