<?php
// ======================================================================
// API: GESTIONAR USUARIOS (ADMIN) - IMPLEMENTACIÓN COMPLETA
// ======================================================================
// Requiere: src/admin_check.php  (inicia sesión, verifica rol admin y expone $pdo)
//           src/funciones_db.php (helpers: desactivar_usuario_db, reactivar_usuario_db, cambiar_password_admin)
declare(strict_types=1);

require_once __DIR__ . '/src/admin_check.php';
require_once __DIR__ . '/src/funciones_db.php';

header('Content-Type: application/json; charset=utf-8');

// Acepta JSON o application/x-www-form-urlencoded
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || empty($data)) {
    // fallback a POST clásico
    $data = $_POST;
}

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['exito'=>false,'mensaje'=>'Conexión de base no disponible ($pdo).']);
    exit;
}

$accion = $data['accion'] ?? null;
$usuario_id = isset($data['usuario_id']) ? (int)$data['usuario_id'] : 0;
$nueva_password = $data['nueva_password'] ?? null;

$exito = false;
$mensaje = 'Acción no reconocida.';

try {
    if ($accion === 'desactivar') {
        list($exito, $mensaje) = desactivar_usuario_db($pdo, $usuario_id);
    } elseif ($accion === 'reactivar') {
        list($exito, $mensaje) = reactivar_usuario_db($pdo, $usuario_id);
    } elseif ($accion === 'cambiar_password') {
        if (empty($nueva_password)) {
            $exito = false;
            $mensaje = 'La nueva contraseña no puede estar vacía.';
        } else {
            list($exito, $mensaje) = cambiar_password_admin($pdo, $usuario_id, $nueva_password);
        }
    }
} catch (Throwable $e) {
    error_log('api_usuarios.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['exito'=>false,'mensaje'=>'Error de base de datos.']);
    exit;
}

http_response_code($exito ? 200 : 400);
echo json_encode(['exito'=>$exito, 'mensaje'=>$mensaje]);
exit;
?>
