<?php
/**
 * ======================================================================
 * INICIALIZADOR DE SUPERVISOR (¡Y ADMIN!)
 * ======================================================================
 * v1.0 - Nuevo archivo
 * * Este archivo se usa en páginas que son para Supervisores Y Admins
 * (ej: gestion_insumos.php, anular_movimiento.php).
 * * Se encarga de:
 * 1. Cargar TODO lo de 'init.php' (Sesión, DB, Funciones, Header, Menú).
 * 2. Realizar una verificación de ROL.
 * 3. Si no es 'admin' O 'supervisor', lo redirige a la página de inicio.
 */

// 1. Cargar el inicializador estándar
// Esto ya nos da $pdo, $USUARIO_ROL, $USUARIO_ID, etc.
require_once __DIR__ . '/init.php';

// 2. Verificar si el usuario es Administrador O Supervisor
if ($USUARIO_ROL !== 'admin' && $USUARIO_ROL !== 'supervisor') {
    
    // Si no es ninguno de los dos, no tiene nada que hacer aquí.
    // Lo redirigimos a la página principal.
    header("Location: inicio.php");
    exit;
}

// Si el script continúa, significa que el usuario tiene permisos suficientes.
?>