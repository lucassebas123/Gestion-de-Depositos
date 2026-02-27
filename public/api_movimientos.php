<?php
// ======================================================================
// API: ACCIONES DE MOVIMIENTOS
// ======================================================================
//Creado para eliminar movimientos programados
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

try {
    // --- ELIMINAR MOVIMIENTO PROGRAMADO ---
    if ($accion === 'eliminar_programado') {
        $movimiento_id = (int)($data['movimiento_id'] ?? 0);
        
        if ($movimiento_id <= 0) {
            echo json_encode(['exito' => false, 'mensaje' => 'ID de movimiento inválido.']);
            exit;
        }

        // $USUARIO_ROL viene de 'api_supervisor_check.php'
        list($exito, $msg) = eliminar_movimiento_programado_db($pdo, $movimiento_id, $USUARIO_ROL);
        
        http_response_code($exito ? 200 : 400);
        echo json_encode(['exito' => $exito, 'mensaje' => $msg]);
        exit;
    }

    // Acción desconocida
    http_response_code(400);
    echo json_encode(['exito' => false, 'mensaje' => 'Acción no reconocida.']);

} catch (Throwable $e) {
    error_log("API Movimientos Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error del servidor.']);
}

?>
