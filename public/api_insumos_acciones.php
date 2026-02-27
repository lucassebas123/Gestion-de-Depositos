<?php
// ======================================================================
// API: ACCIONES DE INSUMOS
// ======================================================================
// v1.0 - Creado para desactivar/activar insumos
declare(strict_types=1);

// 1. Seguridad: Solo Supervisores y Admins
require_once __DIR__ . '/src/api_supervisor_check.php';
require_once __DIR__ . '/src/funciones_db.php';

header('Content-Type: application/json; charset=utf-8');

// 2. Leer entrada (JSON o POST)
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || empty($data)) {
    $data = $_POST;
}

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error de conexión.']);
    exit;
}

$accion = $data['accion'] ?? '';
$insumo_id = (int)($data['insumo_id'] ?? 0);

if ($insumo_id <= 0) {
    http_response_code(400);
    echo json_encode(['exito' => false, 'mensaje' => 'ID de insumo inválido.']);
    exit;
}

try {
    if ($accion === 'desactivar') {
        list($exito, $msg) = desactivar_insumo_logico($pdo, $insumo_id);
    } elseif ($accion === 'activar') {
        list($exito, $msg) = activar_insumo_logico($pdo, $insumo_id);
    } else {
        http_response_code(400);
        echo json_encode(['exito' => false, 'mensaje' => 'Acción no reconocida.']);
        exit;
    }
    
    http_response_code($exito ? 200 : 400);
    echo json_encode(['exito' => $exito, 'mensaje' => $msg]);
    exit;

} catch (Throwable $e) {
    error_log("API Insumos Acciones Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error del servidor.']);
}
?>