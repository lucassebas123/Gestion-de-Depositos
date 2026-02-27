<?php
// ======================================================================
// API: REGLAS (Depósito ↔ Categorías)
// ======================================================================
// MODIFICADO v1.2 - CORREGIDO
// Se corrige el nombre de la variable de 'categoria_id' a 'categoria_ids'
// para que coincida con lo que envía el JavaScript.
declare(strict_types=1);

// Carga el verificador de supervisor/admin
require_once __DIR__ . '/src/api_supervisor_check.php';
require_once __DIR__ . '/src/funciones_db.php';

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || empty($data)) {
    $data = $_POST;
}

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['exito'=>false,'mensaje'=>'Conexión de base no disponible ($pdo).']);
    exit;
}

$accion = $data['accion'] ?? null;

try {
    if ($accion === 'listar') {
        $deposito_id = isset($data['deposito_id']) ? (int)$data['deposito_id'] : 0;
        if ($deposito_id <= 0) {
            http_response_code(400);
            echo json_encode(['exito'=>false,'mensaje'=>'Depósito inválido']);
            exit;
        }
        $asignadas = obtener_categorias_asignadas($pdo, $deposito_id);
        $no_asignadas = obtener_categorias_no_asignadas($pdo, $deposito_id);
        echo json_encode(['exito'=>true, 'asignadas'=>$asignadas, 'no_asignadas'=>$no_asignadas], JSON_UNESCAPED_UNICODE);
        exit;
    }
    elseif ($accion === 'asignar') {
        $deposito_id = (int)($data['deposito_id'] ?? 0);
        
        // ⭐️⭐️ AQUÍ ESTÁ LA CORRECCIÓN ⭐️⭐️
        // Antes buscaba 'categoria_id', ahora busca 'categoria_ids'
        $categoria_ids = $data['categoria_ids'] ?? [];
        // ⭐️⭐️ FIN DE LA CORRECCIÓN ⭐️⭐️

        if (!is_array($categoria_ids)) $categoria_ids = [$categoria_ids];
        $ok = 0; $err = 0;
        foreach ($categoria_ids as $cid) {
            $cid = (int)$cid;
            if ($cid <= 0) continue;
            list($ex, $msg) = agregar_link_categoria($pdo, $deposito_id, $cid);
            if ($ex) $ok++; else $err++;
        }
        echo json_encode(['exito'=>($err===0), 'ok'=>$ok, 'err'=>$err]);
        exit;
    }
    elseif ($accion === 'quitar') {
        $deposito_id = (int)($data['deposito_id'] ?? 0);
        
        
        // Antes buscaba 'categoria_id', ahora busca 'categoria_ids'
        $categoria_ids = $data['categoria_ids'] ?? [];
        

        if (!is_array($categoria_ids)) $categoria_ids = [$categoria_ids];
        $ok = 0; $err = 0;
        foreach ($categoria_ids as $cid) {
            $cid = (int)$cid;
            if ($cid <= 0) continue;
            list($ex, $msg) = quitar_link_categoria($pdo, $deposito_id, $cid);
            if ($ex) $ok++; else $err++;
        }
        echo json_encode(['exito'=>($err===0), 'ok'=>$ok, 'err'=>$err]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['exito'=>false,'mensaje'=>'Acción no soportada']);
    exit;

} catch (Throwable $e) {
    error_log('api_reglas.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['exito'=>false,'mensaje'=>'Error de base de datos']);
    exit;
}
?>