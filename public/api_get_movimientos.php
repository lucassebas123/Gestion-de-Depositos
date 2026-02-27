<?php
// ======================================================================
// API: OBTENER HISTORIAL DE MOVIMIENTOS (CON FILTROS Y PAGINACIÓN)
// ======================================================================

declare(strict_types=1);

// 1. Cargar el verificador de autenticación
require_once __DIR__ . '/src/api_auth_check.php';
// 2. Cargar todas las funciones de la DB
require_once __DIR__ . '/src/funciones_db.php'; 

header('Content-Type: application/json; charset=utf-8');

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error de conexión.']);
    exit;
}

try {
    // --- Lógica de Paginación y Filtros ---
    $items_por_pagina = 10; // Usamos 10 como solicitaste
    $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina_actual < 1) $pagina_actual = 1;
    $offset = ($pagina_actual - 1) * $items_por_pagina;

    // Leer Filtros desde GET
    $filtro_deposito_id = isset($_GET['filtro_deposito_id']) && !empty($_GET['filtro_deposito_id']) ? (int)$_GET['filtro_deposito_id'] : null;
    $filtro_tipo = isset($_GET['filtro_tipo']) && !empty($_GET['filtro_tipo']) ? $_GET['filtro_tipo'] : null;
    $filtro_usuario_id = isset($_GET['filtro_usuario_id']) && !empty($_GET['filtro_usuario_id']) ? (int)$_GET['filtro_usuario_id'] : null;
    
    // Obtener permisos del usuario (para filtrar por sus depósitos)
    $lista_depositos_permitidos = null;
    if ($USUARIO_ROL !== 'admin') {
        $depositos_filtro_perm = obtener_depositos_por_usuario($pdo, $USUARIO_ID, $USUARIO_ROL);
        $lista_depositos_permitidos = array_map(function($d) { return $d['id']; }, $depositos_filtro_perm);
    }
    
    // Obtener el total de páginas (usando los filtros)
    $total_movimientos = obtener_total_movimientos(
        $pdo, 
        $filtro_deposito_id, 
        null, // Búsqueda de texto (se hace en JS en la página)
        $lista_depositos_permitidos,
        $filtro_tipo,
        $filtro_usuario_id
    );
    $total_paginas = ceil($total_movimientos / $items_por_pagina);

    // Obtener los datos de la página actual
    $historial = obtener_historial_movimientos_db(
        $pdo, 
        $filtro_deposito_id, 
        null, 
        $lista_depositos_permitidos,
        $filtro_tipo,
        $filtro_usuario_id,
        $items_por_pagina, // limit
        $offset            // offset
    );

    // Devolver la respuesta JSON
    echo json_encode([
        'exito' => true,
        'movimientos' => $historial,
        'paginacion' => [
            'pagina_actual' => $pagina_actual,
            'total_paginas' => $total_paginas,
            'total_items' => $total_movimientos
        ]
    ]);
    exit;

} catch (Throwable $e) {
    error_log('api_get_movimientos.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error de base de datos.']);
    exit;
}

?>
