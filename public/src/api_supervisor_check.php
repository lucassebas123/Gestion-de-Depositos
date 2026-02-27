<?php
/**
 * ======================================================================
 * VERIFICADOR DE PERMISOS (SUPERVISOR O ADMIN) - VERSIÓN API
 * ======================================================================
 * v1.0 - Nuevo archivo
 * * Este archivo se usa en endpoints de API que son para Supervisores Y Admins
 * (ej: api_reglas.php).
 * * Se encarga de:
 * 1. Cargar 'api_auth_check.php' (valida sesión y define $pdo, $USUARIO_ROL)
 * 2. Si no es 'admin' O 'supervisor', devuelve un JSON de error 403.
 */

// 1. Primero, nos aseguramos de que el usuario haya iniciado sesión.
// Esto define $pdo, $USUARIO_ID, $USUARIO_USERNAME, $USUARIO_ROL
require_once __DIR__ . '/api_auth_check.php';

// 2. Ahora, revisamos si el rol es 'admin' O 'supervisor'.
if ($USUARIO_ROL !== 'admin' && $USUARIO_ROL !== 'supervisor') {
    
    // Si NO tiene permisos, devolvemos un error JSON
    if (headers_sent() === false) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403); // 403 Forbidden
    }
    
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Acceso denegado. Se requiere rol de Supervisor o Administrador.'
    ]);
    
    exit;
}

// Si llegamos aquí, significa que el usuario SÍ tiene permisos.
// La página que incluyó este archivo puede continuar cargando.