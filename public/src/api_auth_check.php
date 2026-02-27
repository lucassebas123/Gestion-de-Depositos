<?php

// 1. Iniciar o reanudar la sesión de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ⭐️ INCLUSIÓN CSRF: Necesario para usar las funciones de validación
require_once __DIR__ . '/token_manager.php';
// ⭐️ FIN INCLUSIÓN CSRF

// 2. Cargar el conector de DB
require_once __DIR__ . '/db_conexion.php';

// Variables globales que definiremos
$USUARIO_ID = null;
$USUARIO_USERNAME = null;
$USUARIO_ROL = null;
$USUARIO_LOGUEADO = false;
$pdo = null; // Variable global de PDO para toda la app

// ======================================================================
// ⭐️ COMPROBACIÓN CRÍTICA: VALIDACIÓN CSRF ⭐️
// Esto previene que sitios externos envíen peticiones POST al API.
// ======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Buscar el token en la cabecera X-CSRF-Token (uso estándar para AJAX)
    $token_enviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    
    // Si no se encuentra en la cabecera, buscarlo en el body (para formularios POST)
    if (!$token_enviado) {
        // Leemos el cuerpo de la petición (si es JSON o POST estándar)
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (isset($data['csrf_token'])) {
            $token_enviado = $data['csrf_token'];
        } elseif (isset($_POST['csrf_token'])) {
             $token_enviado = $_POST['csrf_token'];
        }
    }

    if (!validar_token_csrf($token_enviado)) {
        if (headers_sent() === false) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400); // 400 Bad Request
        }
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Error de seguridad: Token CSRF inválido o ausente.'
        ]);
        exit;
    }
}
// ======================================================================

try {
    // 3. Conectar a la DB
    $pdo = conectar_db();

    // 4. Verificar si existe una sesión de usuario
    if (!isset($_SESSION["usuario_id"])) {
        throw new Exception("No hay sesión de usuario.");
    }

    // 5. Validar la sesión contra la Base de Datos
    $stmt_auth = $pdo->prepare("SELECT id, username, rol, activo FROM usuarios WHERE id = ?");
    $stmt_auth->execute([$_SESSION["usuario_id"]]);
    $usuario = $stmt_auth->fetch();

    if ($usuario && $usuario['activo'] == 1) {
        // --- ÉXITO ---
        $USUARIO_ID = (int)$usuario['id'];
        $USUARIO_USERNAME = $usuario['username'];
        $USUARIO_ROL = $usuario['rol'];
        $USUARIO_LOGUEADO = true;
        $_SESSION['rol'] = $usuario['rol'];
    } else {
        // --- FALLO ---
        throw new Exception("Usuario no encontrado o desactivado.");
    }

} catch (Exception $e) {
    // Si algo falla...
    
    // Devolvemos un error JSON
    if (headers_sent() === false) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401); // 401 Unauthorized
    }
    
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error de autenticación: ' . $e->getMessage()
    ]);
    
    // Detenemos el script para que no continúe
    exit;

}
