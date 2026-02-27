<?php
// 1. Primero, nos aseguramos de que el usuario haya iniciado sesión.
// ✅ CAMBIO: Usamos el verificador de API que devuelve JSON en caso de error.
require_once __DIR__ . '/api_auth_check.php';

// 2. Ahora, revisamos si el rol es 'admin'.
if ($USUARIO_ROL !== 'admin') {
    
    // Si NO es admin, devolvemos un error JSON
    if (headers_sent() === false) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403); // 403 Forbidden
    }
    
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Acceso denegado. Se requiere rol de administrador.'
    ]);
    
    exit;
}

// Si llegamos aquí, significa que el usuario SÍ es admin.
// La página que incluyó este archivo puede continuar cargando.