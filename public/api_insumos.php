<?php
// ======================================================================
// API: OBTENER INSUMOS POR DEPÓSITO - IMPLEMENTACIÓN COMPLETA
// ======================================================================

declare(strict_types=1);

require_once __DIR__ . '/src/auth_check.php';
require_once __DIR__ . '/src/funciones_db.php';

header('Content-Type: application/json; charset=utf-8');

$deposito_id = 0;
// Permite GET, POST o JSON body
if (isset($_GET['deposito_id'])) $deposito_id = (int)$_GET['deposito_id'];
if (isset($_POST['deposito_id'])) $deposito_id = (int)$_POST['deposito_id'];
if (!$deposito_id) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (is_array($data) && isset($data['deposito_id'])) {
        $deposito_id = (int)$data['deposito_id'];
    }
}

if ($deposito_id <= 0) {
    http_response_code(400);
    echo json_encode(['error'=>'Depósito inválido']); 
    exit;
}
if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error'=>'Conexión de base no disponible ($pdo).']); 
    exit;
}

try {
    $insumos = obtener_insumos_para_deposito_asignado($pdo, $deposito_id);
    // Normalizar respuesta
    $out = [];
    foreach ($insumos as $row) {
        // ⭐️ CAMBIO: Añadimos el 'sku' a la respuesta ⭐️
        $out[] = [
            'id'     => (int)$row['id'], 
            'nombre' => (string)$row['nombre'],
            'sku'    => (string)$row['sku'] // <-- ¡NUEVO!
        ];
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    error_log('api_insumos.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error'=>'Error de base de datos.']); 
    exit;
}
?>
