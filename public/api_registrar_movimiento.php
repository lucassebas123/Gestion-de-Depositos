<?php
/**
 * ======================================================================
 * API: REGISTRAR MOVIMIENTO (Backend)
 * ======================================================================
 * v1.1 - Procesa el formulario de movimientos.php
 * - Incluye validación de coherencia (Reglas de Negocio)
 */
declare(strict_types=1);

// 1. Cargar el verificador de autenticación
require_once __DIR__ . '/src/api_auth_check.php';
// 2. Cargar funciones de DB
require_once __DIR__ . '/src/funciones_db.php'; 

header('Content-Type: application/json; charset=utf-8');

// Configuración de subida (subir un nivel desde /public)
$directorio_subida_privado = __DIR__ . '/../uploads_privados/';

try {
    // Validar Rol Observador
    if ($USUARIO_ROL === 'observador') {
        throw new Exception("Permiso denegado.");
    }

    // Recoger datos
    $deposito_id = (int)($_POST['deposito_id'] ?? 0);
    $insumo_id = (int)($_POST['insumo_id'] ?? 0);
    $tipo_mov = $_POST['tipo_movimiento'] ?? '';
    $obs = $_POST['observaciones'] ?? '';
    $fecha_efectiva = $_POST['fecha_efectiva'] ?? null;
    
    // Datos específicos
    $cantidad = (int)($_POST['cantidad'] ?? 0);
    $numero_lote = $_POST['numero_lote'] ?? null;
    $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? null;
    
    // Array de lotes para salida
    $lotes_a_sacar = $_POST['lotes_cantidad'] ?? []; 

    // Validaciones básicas
    if ($deposito_id <= 0 || $insumo_id <= 0 || empty($tipo_mov)) {
        throw new Exception("Datos incompletos (Depósito, Insumo o Tipo).");
    }

    // ---------------------------------------------------------
    // VALIDACIÓN EXTRA: Coherencia Depósito-Insumo (Reglas)
    // ---------------------------------------------------------
    // Verificamos que el insumo pertenezca a una categoría permitida en este depósito.
    $stmt_reglas = $pdo->prepare("
        SELECT COUNT(*) 
        FROM insumos i
        JOIN deposito_categoria_link dcl ON i.categoria_id = dcl.categoria_id
        WHERE i.id = ? AND dcl.deposito_id = ?
    ");
    $stmt_reglas->execute([$insumo_id, $deposito_id]);
    
    if ($stmt_reglas->fetchColumn() == 0) {
        throw new Exception("Error de Seguridad: Este insumo no pertenece a una categoría autorizada para el depósito seleccionado.");
    }
    // ---------------------------------------------------------

    // Manejo de Archivo (Recibo)
    $nombre_archivo_recibo = null;
    if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
        $tipos_permitidos = ['jpg', 'jpeg', 'png', 'pdf'];
        list($exito_subida, $resultado_subida) = manejar_subida_movimiento(
            'comprobante', 
            $directorio_subida_privado, 
            $tipos_permitidos
        );
        if ($exito_subida) {
            $nombre_archivo_recibo = $resultado_subida;
        } else {
            throw new Exception("Error al subir archivo: " . $resultado_subida);
        }
    }

    // Llamar a la función principal de registro
    list($exito, $mensaje) = registrar_movimiento_db(
        $pdo, 
        $USUARIO_ID, 
        $deposito_id,
        $insumo_id,
        $tipo_mov, 
        $cantidad, 
        $lotes_a_sacar, 
        $obs, 
        $nombre_archivo_recibo,
        $numero_lote, 
        $fecha_vencimiento, 
        $fecha_efectiva
    );

    if ($exito) {
        echo json_encode(['exito' => true, 'mensaje' => $mensaje]);
    } else {
        // Si falló y subimos un archivo, borrarlo
        if ($nombre_archivo_recibo && file_exists($directorio_subida_privado . $nombre_archivo_recibo)) {
            unlink($directorio_subida_privado . $nombre_archivo_recibo);
        }
        throw new Exception($mensaje);
    }

} catch (Throwable $e) {
    error_log("api_registrar_movimiento.php: " . $e->getMessage());
    http_response_code(400); 
    echo json_encode(['exito' => false, 'mensaje' => $e->getMessage()]);
}
?>