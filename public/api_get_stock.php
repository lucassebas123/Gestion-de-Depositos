<?php
// ======================================================================
// API: OBTENER STOCK ACTUAL (CON FILTROS Y PAGINACIÓN)
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
    $items_por_pagina = 10; 
    $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina_actual < 1) $pagina_actual = 1;
    $offset = ($pagina_actual - 1) * $items_por_pagina;

    // Leer Filtros desde GET
    $filtro_deposito_id = isset($_GET['filtro_deposito_id']) && !empty($_GET['filtro_deposito_id']) ? (int)$_GET['filtro_deposito_id'] : null;
    $filtro_categoria_id = isset($_GET['filtro_categoria_id']) && !empty($_GET['filtro_categoria_id']) ? (int)$_GET['filtro_categoria_id'] : null;
    $filtro_especial = isset($_GET['filtro']) && !empty($_GET['filtro']) ? $_GET['filtro'] : null;
    
    // Obtener permisos del usuario
    $lista_depositos_permitidos = null;
    if ($USUARIO_ROL !== 'admin') {
        $depositos_filtro_perm = obtener_depositos_por_usuario($pdo, $USUARIO_ID, $USUARIO_ROL);
        $lista_depositos_permitidos = array_map(function($d) { return $d['id']; }, $depositos_filtro_perm);
    }
    
    // Obtener el total de páginas
    $total_stock = obtener_total_stock(
        $pdo, 
        $lista_depositos_permitidos, 
        $filtro_deposito_id, 
        $filtro_categoria_id, 
        null, // Búsqueda de texto (se hace en JS en la página)
        $filtro_especial
    );
    $total_paginas = ceil($total_stock / $items_por_pagina);

    // Obtener los datos de la página actual
    $stock_actual = consultar_stock_db(
        $pdo, 
        $lista_depositos_permitidos, 
        $filtro_deposito_id, 
        $filtro_categoria_id, 
        null, // Búsqueda de texto (se hace en JS en la página)
        $filtro_especial,
        $items_por_pagina, // limit
        $offset            // offset
    );

    // Devolver la respuesta JSON
    echo json_encode([
        'exito' => true,
        'stock' => $stock_actual,
        'paginacion' => [
            'pagina_actual' => $pagina_actual,
            'total_paginas' => $total_paginas,
            'total_items' => $total_stock
        ]
    ]);
    exit;

} catch (Throwable $e) {
    error_log('api_get_stock.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error de base de datos.']);
    exit;
}

?>
