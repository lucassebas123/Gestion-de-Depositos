<?php
/**
 * ======================================================================
 * GENERADOR DE PDF (REMITO DE MOVIMIENTO)
 * ======================================================================
 * Requiere: composer require dompdf/dompdf
 */

// 1. Cargar el Autoloader de Composer (IMPORTANTE: Ajustar ruta si es necesario)
// Como estamos en /public, subimos un nivel para encontrar /vendor
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Cargar el núcleo del sistema (para obtener los datos)
require_once 'src/auth_check.php'; // Seguridad
require_once 'src/funciones_db.php'; // Datos

use Dompdf\Dompdf;
use Dompdf\Options;

// 3. Validar ID
$movimiento_id = (int)($_GET['id'] ?? 0);
if ($movimiento_id <= 0) die("ID inválido.");

// 4. Obtener datos
$mov = obtener_movimiento_detalle_db($pdo, $movimiento_id);
if (!$mov) die("Movimiento no encontrado.");

// 5. Preparar el HTML del PDF
// Usamos estilos CSS en línea (inline) porque los lectores de PDF son básicos.
// Convertimos imágenes a Base64 para que se vean en el PDF sin problemas de rutas.

$logo_path = __DIR__ . '/logo1.png';
$logo_data = '';
if (file_exists($logo_path)) {
    $type = pathinfo($logo_path, PATHINFO_EXTENSION);
    $data = file_get_contents($logo_path);
    $logo_data = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

$html = '
<html>
<head>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #333; }
        .header { width: 100%; border-bottom: 2px solid #4A55A2; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { width: 60px; vertical-align: middle; }
        .empresa { font-size: 18px; font-weight: bold; color: #4A55A2; margin-left: 10px; }
        .titulo { text-align: right; float: right; font-size: 20px; font-weight: bold; color: #555; margin-top: 10px; }
        
        .info-box { width: 100%; margin-bottom: 20px; }
        .info-col { width: 48%; display: inline-block; vertical-align: top; }
        .label { font-weight: bold; color: #666; }
        
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th { background-color: #f0f0f0; padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .table td { padding: 8px; border-bottom: 1px solid #eee; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; height: 30px; font-size: 10px; text-align: center; border-top: 1px solid #ddd; padding-top: 5px; color: #777; }
        
        .status-anulado { color: red; font-size: 14px; font-weight: bold; border: 2px solid red; padding: 5px; display: inline-block; transform: rotate(-10deg); }
    </style>
</head>
<body>

    <div class="header">
        <img src="' . $logo_data . '" class="logo">
        <span class="empresa">Gestor de Insumos</span>
        <div class="titulo">COMPROBANTE #' . str_pad((string)$mov['id'], 6, '0', STR_PAD_LEFT) . '</div>
    </div>

    <div class="info-box">
        <div class="info-col">
            <div class="label">ORIGEN / DETALLE</div>
            <p>
                <strong>Tipo:</strong> ' . $mov['tipo_movimiento'] . '<br>
                <strong>Depósito:</strong> ' . $mov['deposito_nombre'] . '<br>
                <strong>Fecha:</strong> ' . date('d/m/Y H:i', strtotime($mov['fecha_efectiva'])) . '<br>
                <strong>Usuario:</strong> ' . ($mov['usuario_creador'] ?? 'Sistema') . '
            </p>
        </div>
        <div class="info-col" style="text-align: right;">
            <div class="label">ESTADO</div>
            <p>
                ' . ($mov['anulado_por_id'] ? '<span class="status-anulado">ANULADO</span>' : 'CONFIRMADO') . '
            </p>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Código (SKU)</th>
                <th>Descripción del Insumo</th>
                <th>Lote / Vencimiento</th>
                <th style="text-align: right;">Cantidad</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>' . ($mov['insumo_sku'] ?? '-') . '</td>
                <td>
                    <strong>' . $mov['insumo_nombre'] . '</strong><br>
                    <small>' . $mov['categoria_nombre'] . '</small>
                </td>
                <td>
                    Lote: ' . ($mov['numero_lote'] ?? '-') . '<br>
                    Vence: ' . ($mov['fecha_vencimiento'] ?? '-') . '
                </td>
                <td style="text-align: right; font-size: 14px; font-weight: bold;">
                    ' . $mov['cantidad_movida'] . '
                </td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 30px; border: 1px solid #eee; padding: 10px; background-color: #fafafa;">
        <div class="label">OBSERVACIONES:</div>
        <p>' . ($mov['observaciones'] ?? 'Sin observaciones adicionales.') . '</p>
    </div>

    <div style="margin-top: 60px; text-align: center;">
        <div style="border-top: 1px solid #333; width: 200px; margin: 0 auto 5px auto;"></div>
        <small>Firma / Conforme</small>
    </div>

    <div class="footer">
        Documento generado automáticamente el ' . date('d/m/Y H:i:s') . ' por el Sistema de Gestión de Insumos.
    </div>

</body>
</html>';

// 6. Generar PDF con Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true); // Permitir imágenes locales
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// 7. Descargar o Mostrar
// 'Attachment' => 0 lo muestra en el navegador. Pon 1 para forzar descarga.
$dompdf->stream("Comprobante_Mov_" . $mov['id'] . ".pdf", ["Attachment" => 0]);
?>