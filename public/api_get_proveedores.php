<?php
// ======================================================================
// API: OBTENER LISTA DE PROVEEDORES (LÓGICA EXCLUSIVA)
// ======================================================================

declare(strict_types=1);

// 1. Cargar el verificador de SUPERVISOR (y Admin)
require_once __DIR__ . '/src/api_supervisor_check.php';
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

    // ⭐️ CAMBIO: Ahora interpretamos el parámetro 'inactivos' como "solo inactivos"
    $solo_inactivos = isset($_GET['inactivos']) && $_GET['inactivos'] == '1';

    // Obtener el total de páginas
    $total_proveedores = obtener_total_proveedores($pdo, $solo_inactivos);
    $total_paginas = ceil($total_proveedores / $items_por_pagina);

    // Obtener los datos de la página actual
    $proveedores = obtener_todos_proveedores($pdo, $solo_inactivos, $items_por_pagina, $offset);

    // Devolver la respuesta JSON
    echo json_encode([
        'exito' => true,
        'proveedores' => $proveedores,
        'paginacion' => [
            'pagina_actual' => $pagina_actual,
            'total_paginas' => $total_paginas,
            'total_items' => $total_proveedores
        ]
    ]);
    exit;

} catch (Throwable $e) {
    error_log('api_get_proveedores.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error de base de datos.']);
    exit;
}

?>
