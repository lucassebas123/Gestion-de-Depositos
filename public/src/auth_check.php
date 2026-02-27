<?php
// 1. Iniciar o reanudar la sesión de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Cargar el conector de DB
require_once __DIR__ . '/db_conexion.php';

// Variables globales que definiremos
$USUARIO_ID = null;
$USUARIO_USERNAME = null;
$USUARIO_ROL = null;
$USUARIO_LOGUEADO = false;
$pdo = null; // Variable global de PDO para toda la app

try {
    // 3. Conectar a la DB (esta variable $pdo se usará en toda la app)
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
        // El usuario existe y está activo. Definimos las variables globales.
        $USUARIO_ID = (int)$usuario['id'];
        $USUARIO_USERNAME = $usuario['username'];
        $USUARIO_ROL = $usuario['rol'];
        $USUARIO_LOGUEADO = true;

        // Sincronizar el ROL en la sesión (útil para el menú)
        $_SESSION['rol'] = $usuario['rol'];

    } else {
        // --- FALLO ---
        // El usuario no existe o fue desactivado.
        throw new Exception("Usuario no encontrado o desactivado.");
    }

} catch (Exception $e) {
    // Si algo falla (sin sesión, DB offline, usuario inactivo),
    // destruimos la sesión y redirigimos al login.
    
    $error_message = urlencode($e->getMessage());

    // Limpiar sesión
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    // Redirigir a la página de acceso
    header("Location: acceso.php?mensaje_error=" . $error_message);
    exit;
}

?>
