<?php
// ======================================================================
// API: OBTENER LISTA DE USUARIOS (ADMIN)
// ======================================================================

declare(strict_types=1);

// 1. Cargar el verificador de ADMIN
require_once __DIR__ . '/src/api_admin_check.php';
// 2. Cargar todas las funciones de la DB
require_once __DIR__ . '/src/funciones_db.php'; 

header('Content-Type: application/json; charset=utf-8');

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error de conexión.']);
    exit;
}

try {
    // Obtenemos todos los usuarios
    $usuarios = obtener_todos_los_usuarios($pdo);

    // Devolver la respuesta JSON
    echo json_encode([
        'exito' => true,
        'usuarios' => $usuarios
    ]);
    exit;

} catch (Throwable $e) {
    error_log('api_get_usuarios.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error de base de datos.']);
    exit;

}
