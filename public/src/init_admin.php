<?php
/**
 * ======================================================================
 * INICIALIZADOR DE ADMINISTRADOR
 * ======================================================================
 * Refactorizado v1.0
 * * Este archivo se usa en páginas que SON SÓLO PARA ADMINS
 * (ej: gestion_usuarios.php, reglas.php).
 * * Se encarga de:
 * 1. Cargar TODO lo de 'init.php' (Sesión, DB, Funciones, Header, Menú).
 * 2. Realizar una verificación de ROL.
 * 3. Si no es admin, lo redirige a la página de inicio.
 */

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