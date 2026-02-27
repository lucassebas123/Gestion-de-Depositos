<?php
/**
 * ======================================================================
 * GESTOR DE TOKENS CSRF
 * ======================================================================
 * v1.0 - Creado por [Asistente]
 *
 * Funciones para generar, obtener y validar el token CSRF.
 */

define('CSRF_TOKEN_KEY', 'csrf_token_app');

/**
 * Genera un nuevo token CSRF si no existe en la sesión.
 * NOTA: Esto debe ser llamado en cada carga de página.
 */
function generar_token_csrf() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION[CSRF_TOKEN_KEY])) {
        // Genera un token aleatorio, más largo que un ID de sesión.
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32)); 
    }
}

/**
 * Obtiene el token CSRF actual de la sesión.
 * @return string|null
 */
function obtener_token_csrf() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION[CSRF_TOKEN_KEY] ?? null;
}

/**
 * Valida un token CSRF enviado contra el token de la sesión.
 * @param string|null $token_enviado
 * @return bool
 */
function validar_token_csrf($token_enviado) {
    if (empty($token_enviado)) {
        return false;
    }
    $token_sesion = obtener_token_csrf();
    
    // Usar hash_equals() para prevención de ataques de temporización
    return $token_sesion && hash_equals($token_sesion, $token_enviado);
}