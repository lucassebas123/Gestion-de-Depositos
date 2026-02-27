<?php
// ======================================================================
// LOGOUT (CERRAR SESIÓN)
// ======================================================================
// Refactorizado v1.0

session_start();
$_SESSION = array(); // Limpiar variables

// Destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirigir a la página de acceso
header("Location: acceso.php");
exit;
?>