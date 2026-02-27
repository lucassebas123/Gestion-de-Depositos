<?php
// Aumentar límites para reportes pesados
set_time_limit(300); // 5 minutos máximo
ini_set('memory_limit', '512M'); // Permitir usar hasta 512MB de RAM

// 1. Cargar Auth y Funciones
require_once 'src/auth_check.php'; 
require_once 'src/funciones_db.php';

// 2. Definir nombre de archivo y headers CSV
$filename = 'reporte_stock_' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// 3. Leer los filtros
$filtro_deposito_id = $_GET['filtro_deposito_id'] ?? null;
$filtro_categoria_id = $_GET['filtro_categoria_id'] ?? null;
$termino_busqueda = $_GET['busqueda'] ?? null; // (Este filtro de búsqueda de texto se usa aquí)
$filtro_especial = $_GET['filtro'] ?? null;

try {
    // 4. Obtener los datos (usando $pdo global)
    
    $lista_depositos_permitidos = null;
    if ($USUARIO_ROL !== 'admin') {
        $depositos_filtro = obtener_depositos_por_usuario($pdo, $USUARIO_ID, $USUARIO_ROL);
        $lista_depositos_permitidos = array_map(function($d) { return $d['id']; }, $depositos_filtro);
    }
    
    // ⭐️ CAMBIO: Llamamos a la función sin limit/offset
    $stock_actual = consultar_stock_db(
        $pdo, 
        $lista_depositos_permitidos, 
        $filtro_deposito_id, 
        $filtro_categoria_id, 
        $termino_busqueda, // La exportación SÍ usa el filtro de búsqueda de texto
        $filtro_especial,
        null, // No hay límite
        null  // No hay offset
    );
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fputs($output, $bom = ( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
    
    // 5. Escribir los Títulos
    fputcsv($output, [
        'Insumo', 'Categoria', 'Deposito', 'Proveedor', 
        'Stock Actual', 'Stock Minimo', 'Ultima Actualizacion'
    ]);
    
    // 6. Escribir los datos
    if (!empty($stock_actual)) {
        foreach ($stock_actual as $stock) {
            fputcsv($output, [
                $stock['insumo_nombre'],
                $stock['categoria_nombre'],
                $stock['deposito_nombre'],
                $stock['proveedor'] ?? 'N/A',
                $stock['cantidad'],
                $stock['stock_minimo'],
                $stock['fecha_actualizacion']
            ]);
        }
    }
    
    fclose($output);
    exit;

} catch (Exception $e) {
    error_log("Error al exportar stock: " . $e->getMessage());
    die("Error al generar el reporte.");
}
?>