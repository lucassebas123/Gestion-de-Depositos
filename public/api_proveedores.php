<?php
// ======================================================================
// API: GESTIÓN DE PROVEEDORES
// ======================================================================
// Añadida lógica de activar/desactivar

declare(strict_types=1);

// 1. Seguridad: Solo Supervisores y Admins
require_once __DIR__ . '/src/api_supervisor_check.php';
require_once __DIR__ . '/src/funciones_db.php';

header('Content-Type: application/json; charset=utf-8');

// Leer entrada (JSON o POST)
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || empty($data)) {
    $data = $_POST;
}

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['exito'=>false, 'mensaje'=>'Error de conexión.']);
    exit;
}

$accion = $data['accion'] ?? '';

try {
    // --- LISTAR (Sin cambios, usa la función de DB que solo trae activos) ---
    if ($accion === 'listar') {
        // Esta acción se mantiene para el <select> de 'gestion_insumos.php'
        // que solo debe mostrar proveedores activos.
        $proveedores = obtener_todos_proveedores($pdo, false); // false = solo activos
        echo json_encode(['exito'=>true, 'datos'=>$proveedores]);
        exit;
    }
    
    // --- OBTENER UNO (para editar) ---
    if ($accion === 'obtener') {
        $id = (int)($data['id'] ?? 0);
        $prov = obtener_proveedor_por_id($pdo, $id);
        if ($prov) {
            echo json_encode(['exito'=>true, 'datos'=>$prov]);
        } else {
            echo json_encode(['exito'=>false, 'mensaje'=>'Proveedor no encontrado.']);
        }
        exit;
    }

    // --- GUARDAR (Crear o Editar) ---
    if ($accion === 'guardar') {
        // Preparamos los datos
        $datos_prov = [
            'id' => !empty($data['id']) ? (int)$data['id'] : null,
            'nombre' => trim($data['nombre'] ?? ''),
            'contacto' => trim($data['contacto'] ?? ''),
            'telefono' => trim($data['telefono'] ?? ''),
            'email' => trim($data['email'] ?? ''),
            'direccion' => trim($data['direccion'] ?? ''),
            'activo' => isset($data['activo']) ? (int)$data['activo'] : 1 // ⭐️ Añadido
        ];

        list($exito, $msg) = guardar_proveedor($pdo, $datos_prov);
        
        echo json_encode(['exito'=>$exito, 'mensaje'=>$msg]);
        exit;
    }

    // ⭐️ ACCIÓN MODIFICADA (antes 'eliminar') ⭐️
    if ($accion === 'desactivar') {
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['exito'=>false, 'mensaje'=>'ID inválido.']);
            exit;
        }
        
        // Llama a la nueva función renombrada
        list($exito, $msg) = desactivar_proveedor_logico($pdo, $id);
        echo json_encode(['exito'=>$exito, 'mensaje'=>$msg]);
        exit;
    }

    // ⭐️ NUEVA ACCIÓN ⭐️
    if ($accion === 'activar') {
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['exito'=>false, 'mensaje'=>'ID inválido.']);
            exit;
        }
        
        list($exito, $msg) = activar_proveedor_logico($pdo, $id);
        echo json_encode(['exito'=>$exito, 'mensaje'=>$msg]);
        exit;
    }


    // Acción desconocida
    http_response_code(400);
    echo json_encode(['exito'=>false, 'mensaje'=>'Acción no reconocida.']);

} catch (Throwable $e) {
    error_log("API Proveedores Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['exito'=>false, 'mensaje'=>'Error del servidor.']);
}

?>
