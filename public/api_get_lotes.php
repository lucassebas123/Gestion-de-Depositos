<?php
// ======================================================================
// API: OBTENER LOTES DISPONIBLES POR INSUMO
// ======================================================================

declare(strict_types=1);

// 1. Cargar el verificador de autenticación
// (Cualquier usuario logueado puede consultar lotes)
require_once __DIR__ . '/src/api_auth_check.php';
// (Cargamos el cargador principal, que ahora incluye funciones_stock.php)
require_once __DIR__ . '/src/funciones_db.php'; 

header('Content-Type: application/json; charset=utf-8');

// 2. Leer los parámetros (insumo y depósito)
$insumo_id = (int)($_GET['insumo_id'] ?? 0);
$deposito_id = (int)($_GET['deposito_id'] ?? 0);

if ($insumo_id <= 0 || $deposito_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['exito' => false, 'mensaje' => 'Parámetros inválidos.']);
    exit;
}

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error de conexión.']);
    exit;
}

try {
    // 3. Usar la nueva función para obtener los lotes
    $lotes = obtener_lotes_disponibles_por_insumo($pdo, $insumo_id, $deposito_id);
    
    // 4. Devolver la respuesta
    echo json_encode([
        'exito' => true,
        'lotes' => $lotes
    ]);
    exit;

} catch (Throwable $e) {
    error_log('api_get_lotes.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error de base de datos.']);
    exit;
}

?>
