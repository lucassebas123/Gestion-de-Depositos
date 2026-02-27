<?php

// 1. Cargar el inicializador estándar
// Esto ya nos da $pdo, $USUARIO_ROL, $USUARIO_ID, etc.
require_once __DIR__ . '/init.php';

// 2. Verificar si el usuario es Administrador
if ($USUARIO_ROL !== 'admin') {
    // Si no es admin, no tiene nada que hacer aquí.
    // Lo redirigimos a la página principal de operadores.
    header("Location: inicio.php");
    exit;
}

// Si el script continúa, significa que el usuario ES admin.

?>
