<?php
/**
 * ======================================================================
 * INICIALIZADOR ESTÁNDAR
 * ======================================================================
 * Refactorizado v1.3 - [Asistente] Sincronización de Zona Horaria
 *
 * Propósito:
 * - Configurar el entorno (Zona horaria, Errores).
 * - Iniciar seguridad (CSRF, Sesión).
 * - Conectar a la Base de Datos.
 * - Cargar funciones globales.
 * - Cargar la interfaz (Header y Menú).
 */

// 1. Configurar Zona Horaria de PHP (Ajustado a Argentina)
date_default_timezone_set('America/Argentina/Buenos_Aires');

// ⭐️ NUEVO: Cargar el Gestor de Tokens y generar el token para la sesión
require_once __DIR__ . '/token_manager.php';
generar_token_csrf(); // Genera un token si la sesión aún no lo tiene

// 2. Autenticación y Carga de Usuario
// Esto define $pdo, $USUARIO_ID, $USUARIO_USERNAME, $USUARIO_ROL
require_once __DIR__ . '/auth_check.php';

// 3. Sincronizar Zona Horaria de MySQL (¡CRÍTICO PARA SEGURIDAD!)
// Esto asegura que la función NOW() de SQL coincida con la hora de PHP.
if (isset($pdo)) {
    try {
        // Enviamos el offset horario actual (ej: '-03:00') a MySQL
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    } catch (Exception $e) {
        // Si falla (común en algunos hostings compartidos sin permisos), 
        // lo ignoramos silenciosamente, pero en XAMPP esto arregla el desfase.
        error_log("Advertencia: No se pudo sincronizar la zona horaria de MySQL: " . $e->getMessage());
    }
}

// 4. Cargar todas las funciones de la base de datos
require_once __DIR__ . '/funciones_db.php';

// 5. Ejecutar el procesador de tareas programadas
// Esto se ejecutará en cada carga de página,
// procesando movimientos que hayan vencido.
if (isset($pdo)) {
    procesar_movimientos_programados($pdo);
}

// 6. Cargar la plantilla (header y menú)
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/menu.php';

// La página que incluye este archivo ahora puede empezar a dibujar
// su contenido (usualmente dentro de <div class="content-wrapper">)
?>