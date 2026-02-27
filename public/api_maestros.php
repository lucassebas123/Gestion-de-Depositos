<?php
// ======================================================================
// API: CREAR Y GESTIONAR MAESTROS (Con Auditoría Completa)
// ======================================================================
// v2.1 - Gestiona creación, activación y desactivación con registro de auditoría.
declare(strict_types=1);

require_once __DIR__ . '/src/api_supervisor_check.php'; // Nos da $USUARIO_ID
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
    echo json_encode(['exito'=>false, 'mensaje'=>'Conexión de base no disponible.']);
    exit;
}

$accion = $data['accion'] ?? '';
$nombre = $data['nombre'] ?? ''; 
$id = (int)($data['id'] ?? 0);
$motivo = trim($data['motivo'] ?? 'Sin motivo especificado');

$exito = false;
$mensaje = "Acción no reconocida.";

try {
    // ==================================================================
    // 1. CREACIÓN (DEPÓSITOS Y CATEGORÍAS)
    // ==================================================================
    if ($accion === 'crear_deposito') {
        // Soporte legacy por si el JS envía el nombre viejo
        if (empty($nombre) && isset($data['nuevo_deposito_nombre'])) {
            $nombre = $data['nuevo_deposito_nombre'];
        }
        list($exito, $mensaje) = agregar_deposito($pdo, $nombre);
    }
    
    elseif ($accion === 'crear_categoria') {
        if (empty($nombre) && isset($data['nueva_categoria_nombre'])) {
            $nombre = $data['nueva_categoria_nombre'];
        }
        list($exito, $mensaje) = agregar_categoria($pdo, $nombre);
    }

    // ==================================================================
    // 2. ACTIVACIÓN (Reactiva y registra quién lo hizo)
    // ==================================================================
    elseif ($accion === 'activar_deposito') {
        if ($id > 0) {
            // Actualizamos activo=1, limpiamos baja, y guardamos alta
            $sql = "UPDATE depositos SET activo=1, motivo_baja=NULL, baja_por_id=NULL, fecha_baja=NULL, alta_por_id=?, fecha_alta=NOW() WHERE id=?";
            $pdo->prepare($sql)->execute([$USUARIO_ID, $id]);
            $exito = true;
            $mensaje = "Depósito reactivado con éxito.";
        } else {
            $mensaje = "ID inválido.";
        }
    }

    elseif ($accion === 'activar_categoria') {
        if ($id > 0) {
            $sql = "UPDATE categorias SET activo=1, motivo_baja=NULL, baja_por_id=NULL, fecha_baja=NULL, alta_por_id=?, fecha_alta=NOW() WHERE id=?";
            $pdo->prepare($sql)->execute([$USUARIO_ID, $id]);
            $exito = true;
            $mensaje = "Categoría reactivada con éxito.";
        } else {
            $mensaje = "ID inválido.";
        }
    }

    // ==================================================================
    // 3. DESACTIVACIÓN (Guarda motivo, usuario y fecha)
    // ==================================================================
    elseif ($accion === 'desactivar_deposito') {
        if ($id > 0) {
            $sql = "UPDATE depositos SET activo=0, motivo_baja=?, baja_por_id=?, fecha_baja=NOW() WHERE id=?";
            $pdo->prepare($sql)->execute([$motivo, $USUARIO_ID, $id]);
            $exito = true;
            $mensaje = "Depósito desactivado.";
        } else {
            $mensaje = "ID inválido.";
        }
    }

    elseif ($accion === 'desactivar_categoria') {
        if ($id > 0) {
            $sql = "UPDATE categorias SET activo=0, motivo_baja=?, baja_por_id=?, fecha_baja=NOW() WHERE id=?";
            $pdo->prepare($sql)->execute([$motivo, $USUARIO_ID, $id]);
            $exito = true;
            $mensaje = "Categoría desactivada.";
        } else {
            $mensaje = "ID inválido.";
        }
    }

    // Respuesta final
    if (!$exito && $mensaje === "Acción no reconocida.") {
        http_response_code(400);
    } else {
        http_response_code($exito ? 200 : 400);
    }

    echo json_encode(['exito' => $exito, 'mensaje' => $mensaje]);
    exit;

} catch (Throwable $e) {
    error_log('api_maestros.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error de base de datos: ' . $e->getMessage()]);
    exit;
}
?>