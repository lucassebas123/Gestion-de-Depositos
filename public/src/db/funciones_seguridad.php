<?php
/**
 * MÓDULO: FUNCIONES DE SEGURIDAD (CORREGIDO)
 * Control de intentos de login y bloqueos.
 */

// Configuración
define('MAX_INTENTOS_LOGIN', 5);
define('MINUTOS_BLOQUEO', 15);

/**
 * Verifica si una IP está bloqueada por exceso de intentos.
 * @return array [bool $bloqueado, int $minutos_restantes]
 */
function verificar_bloqueo_ip($pdo, $ip) {
    // 1. Definir el tiempo límite hacia atrás
    // Ejemplo: Si son las 14:30, buscamos intentos desde las 14:15
    $tiempo_limite = date('Y-m-d H:i:s', strtotime("-" . MINUTOS_BLOQUEO . " minutes"));
    
    // 2. Contar intentos fallidos recientes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_intentos WHERE ip_address = ? AND fecha_intento > ?");
    $stmt->execute([$ip, $tiempo_limite]);
    $intentos = (int)$stmt->fetchColumn();

    // (DEBUG: Esto escribirá en el log de errores de PHP para que sepamos qué pasa)
    error_log("Seguridad: La IP $ip tiene $intentos intentos fallidos en los últimos " . MINUTOS_BLOQUEO . " min.");

    if ($intentos >= MAX_INTENTOS_LOGIN) {
        // 3. Calcular tiempo restante
        $stmtLast = $pdo->prepare("SELECT fecha_intento FROM login_intentos WHERE ip_address = ? ORDER BY fecha_intento DESC LIMIT 1");
        $stmtLast->execute([$ip]);
        $ultimo_intento = $stmtLast->fetchColumn();
        
        $hora_desbloqueo = strtotime($ultimo_intento) + (MINUTOS_BLOQUEO * 60);
        $segundos_restantes = $hora_desbloqueo - time();
        $minutos_restantes = ceil($segundos_restantes / 60);
        
        // Si el cálculo da negativo o cero pero sigue bloqueado, forzamos 1 minuto
        return [true, ($minutos_restantes > 0 ? $minutos_restantes : 1)];
    }

    return [false, 0];
}

/**
 * Registra un intento fallido en la base de datos.
 */
function registrar_intento_fallido($pdo, $ip, $username) {
    try {
        $stmt = $pdo->prepare("INSERT INTO login_intentos (ip_address, username, fecha_intento) VALUES (?, ?, NOW())");
        $stmt->execute([$ip, substr($username, 0, 100)]);
    } catch (Exception $e) {
        error_log("Error al registrar intento fallido: " . $e->getMessage());
    }
}

/**
 * Limpia los intentos de una IP (se usa cuando el login es exitoso).
 */
function limpiar_intentos_ip($pdo, $ip) {
    try {
        // Borramos TODOS los intentos de esa IP para "perdonarla"
        $stmt = $pdo->prepare("DELETE FROM login_intentos WHERE ip_address = ?");
        $stmt->execute([$ip]);
    } catch (Exception $e) {
        error_log("Error al limpiar intentos: " . $e->getMessage());
    }
}
?>