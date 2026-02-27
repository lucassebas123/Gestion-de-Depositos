<?php
// ======================================================================
// API: DATOS PARA GRÁFICOS DEL DASHBOARD
// ======================================================================
// v1.1 - CORREGIDO
// Se reemplaza 'init.php' por 'auth_check.php' y 'funciones_db.php'
// para evitar que la API imprima HTML (header y menu).
declare(strict_types=1);

// ⭐️ CAMBIO: Reemplazamos init.php por estos dos archivos ⭐️
require_once __DIR__ . '/src/auth_check.php'; 
require_once __DIR__ . '/src/funciones_db.php';
// ⭐️ FIN DEL CAMBIO ⭐️

header('Content-Type: application/json; charset=utf-8');

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error'=>'Conexión de base no disponible ($pdo).']);
    exit;
}

try {
    // Definir la lista de permisos
    $lista_depositos_permitidos = null;
    if ($USUARIO_ROL !== 'admin') {
        $depositos_filtro = obtener_depositos_por_usuario($pdo, $USUARIO_ID, $USUARIO_ROL);
        $lista_depositos_permitidos = array_map(function($d) { return $d['id']; }, $depositos_filtro);
    }

    // Consultar los datos para los gráficos
    $stock_por_categoria = obtener_stock_por_categoria($pdo, $lista_depositos_permitidos);
    $stock_por_deposito = obtener_stock_por_deposito($pdo, $lista_depositos_permitidos);

    // Preparar la salida
    $datos_graficos = [
        'stockPorCategoria' => [
            'labels' => array_column($stock_por_categoria, 'label'),
            'data'   => array_column($stock_por_categoria, 'data')
        ],
        'stockPorDeposito' => [
            'labels' => array_column($stock_por_deposito, 'label'),
            'data'   => array_column($stock_por_deposito, 'data')
        ]
    ];
    
    echo json_encode($datos_graficos, JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('api_dashboard.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error'=>'Error de base de datos']);
    exit;
}
?>