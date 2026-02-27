<?php
// Aumentar límites para reportes pesados
set_time_limit(300); // 5 minutos máximo
ini_set('memory_limit', '512M'); // Permitir usar hasta 512MB de RAM

// 1. Cargar Auth y Funciones
require_once 'src/auth_check.php'; 
require_once 'src/funciones_db.php';

// 2. Definir nombre de archivo y headers CSV
$filename = 'reporte_movimientos_' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// 3. Leer los filtros
$filtro_deposito_id = $_GET['filtro_deposito_id'] ?? null;
$filtro_tipo = $_GET['filtro_tipo'] ?? null;
$filtro_usuario_id = $_GET['filtro_usuario_id'] ?? null;

try {
    // 4. Obtener los datos (usando $pdo global)
    $lista_depositos_permitidos = null;
    if ($USUARIO_ROL !== 'admin') {
        $depositos_filtro = obtener_depositos_por_usuario($pdo, $USUARIO_ID, $USUARIO_ROL);
        $lista_depositos_permitidos = array_map(function($d) { return $d['id']; }, $depositos_filtro);
    }

    // ⭐️ MODIFICADO: Se llama sin limit y offset para exportar TODO
    $historial = obtener_historial_movimientos_db(
        $pdo, 
        $filtro_deposito_id, 
        null, 
        $lista_depositos_permitidos,
        $filtro_tipo,
        $filtro_usuario_id,
        null, // No hay límite
        null  // No hay offset
    );
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8 (Para que Excel lea bien los acentos)
    fputs($output, $bom = ( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
    
    // 5. Escribir los Títulos
    fputcsv($output, [
        'ID Movimiento', 
        'Fecha Carga', 
        'Fecha Efectiva',
        'Estado',
        'Insumo', 
        'Categoria', 
        'Deposito', 
        'Tipo', 
        'Cantidad', 
        'Usuario (Creo)', 
        'Observaciones', 
        'Estado Anulacion',
        'Anulado Por' 
    ]);
    
    // 6. Escribir los datos
    if (!empty($historial)) {
        foreach ($historial as $mov) {
            
            $estado_anulacion = 'Vigente';
            $anulado_por = '-';
            
            if ($mov['anulado_por_id'] != null) {
                $estado_anulacion = 'Anulado';
                $anulado_por = $mov['anulado_por_username'] ?? 'Usuario Eliminado';
            }

            fputcsv($output, [
                $mov['id'],
                $mov['fecha'],
                $mov['fecha_efectiva'],
                $mov['estado'],
                $mov['insumo_nombre'],
                $mov['categoria_nombre'],
                $mov['deposito_nombre'],
                $mov['tipo_movimiento'],
                $mov['cantidad_movida'],
                $mov['username'] ?? 'Sistema',
                $mov['observaciones'],
                $estado_anulacion,
                $anulado_por
            ]);
        }
    }
    
    fclose($output);
    exit;

} catch (Exception $e) {
    error_log("Error al exportar movimientos: " . $e->getMessage());
    die("Error al generar el reporte.");
}
?>