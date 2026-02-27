<?php
// ======================================================================
// API: OBTENER LISTA DE INSUMOS (LÓGICA EXCLUSIVA)
// ======================================================================

declare(strict_types=1);

require_once __DIR__ . '/src/api_supervisor_check.php';
require_once __DIR__ . '/src/funciones_db.php'; 

header('Content-Type: application/json; charset=utf-8');

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error de conexión.']);
    exit;
}

try {
    $items_por_pagina = 10;
    $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina_actual < 1) $pagina_actual = 1;
    $offset = ($pagina_actual - 1) * $items_por_pagina;

    $termino_busqueda = isset($_GET['busqueda']) && !empty($_GET['busqueda']) ? $_GET['busqueda'] : null;
    
    // ⭐️ CAMBIO: Ahora interpretamos el parámetro 'inactivos' como "solo inactivos"
    $solo_inactivos = isset($_GET['inactivos']) && $_GET['inactivos'] == '1';
    
    $filtro_categoria = isset($_GET['categoria_id']) && !empty($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : null;

    $total_insumos = obtener_total_insumos($pdo, $termino_busqueda, $solo_inactivos, $filtro_categoria);
    $total_paginas = ceil($total_insumos / $items_por_pagina);

    $insumos = obtener_insumos_completos(
        $pdo, 
        $termino_busqueda, 
        $solo_inactivos, // true = solo inactivos, false = solo activos
        $items_por_pagina,
        $offset,
        $filtro_categoria
    );

    echo json_encode([
        'exito' => true,
        'insumos' => $insumos,
        'paginacion' => [
            'pagina_actual' => $pagina_actual,
            'total_paginas' => $total_paginas,
            'total_items' => $total_insumos
        ]
    ]);
    exit;

} catch (Throwable $e) {
    error_log('api_get_insumos.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error de base de datos.']);
    exit;
}

?>
