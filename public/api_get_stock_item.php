<?php
/**
 * ======================================================================
 * API: OBTENER STOCK DE UN ITEM ESPECÍFICO
 * ======================================================================
 */
declare(strict_types=1);

// 1. Cargar el verificador de autenticación
// Esto define $pdo, $USUARIO_ID, $USUARIO_ROL y protege el endpoint
require_once __DIR__ . '/src/api_auth_check.php';

// 2. Cargar las funciones de DB
require_once __DIR__ . '/src/funciones_db.php';

header('Content-Type: application/json; charset=utf-8');

// 3. Leer los parámetros (insumo y depósito)
$insumo_id = (int)($_GET['insumo_id'] ?? 0);
$deposito_id = (int)($_GET['deposito_id'] ?? 0);

if ($insumo_id <= 0 || $deposito_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['exito' => false, 'mensaje' => 'Parámetros inválidos.']);
    exit;
}

try {
    // 5. Usar la función existente para obtener el stock
    $stock_actual = obtener_stock_actual_insumo($pdo, $insumo_id, $deposito_id);

    // ⭐️ INICIO DE LA MODIFICACIÓN (LOGGING) ⭐️
    // Registramos quién, qué, cuándo y cuánto consultó.
    try {
        // $USUARIO_ID está disponible globalmente gracias a api_auth_check.php
        $sql_log = "INSERT INTO auditoria_consultas_stock 
                        (usuario_id, deposito_id, insumo_id, stock_consultado) 
                    VALUES 
                        (?, ?, ?, ?)";
        
        $stmt_log = $pdo->prepare($sql_log);
        $stmt_log->execute([
            $USUARIO_ID, 
            $deposito_id, 
            $insumo_id, 
            $stock_actual
        ]);
    } catch (\PDOException $e_log) {
        // ¡Importante! Si el log falla, no detenemos la aplicación.
        // El usuario necesita ver el stock. Solo registramos el error internamente.
        error_log("Error al registrar auditoría de consulta de stock: " . $e_log->getMessage());
    }
    // ⭐️ FIN DE LA MODIFICACIÓN (LOGGING) ⭐️
    
    // 6. Devolver la respuesta (La fecha_consulta es la fecha del servidor)
    echo json_encode([
        'exito' => true,
        'stock' => $stock_actual,
        'fecha_consulta' => date('d/m/Y H:i:s') 
    ]);
    exit;

} catch (Throwable $e) {
    error_log('api_get_stock_item.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error de base de datos.']);
    exit;
}

?>
