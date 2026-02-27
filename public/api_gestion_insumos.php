<?php
// ======================================================================
// API: GESTIÓN DE INSUMOS (CREAR/ACTUALIZAR)
// ======================================================================
declare(strict_types=1);

// 1. Seguridad: Solo Supervisores y Admins
require_once __DIR__ . '/src/api_supervisor_check.php';
require_once __DIR__ . '/src/funciones_db.php';

header('Content-Type: application/json; charset=utf-8');

// 2. Definir la ruta a la carpeta privada
$directorio_subida_privado = __DIR__ . '/../uploads_privados/';

// 3. Obtener la acción (crear o actualizar)
$accion = $_POST['accion'] ?? '';

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error de conexión.']);
    exit;
}

try {
    
    // ==================================================
    // ACCIÓN: CREAR NUEVO INSUMO
    // ==================================================
    if ($accion === 'crear') {
        
        // 1. Manejar subida de IMAGEN
        $tipos_img = ['jpg', 'jpeg', 'png'];
        list($exito_img, $nombre_archivo_imagen) = manejar_subida_movimiento(
            'imagen_producto', 
            $directorio_subida_privado, 
            $tipos_img
        );

        if (!$exito_img) {
            throw new Exception($nombre_archivo_imagen); // Contiene el error
        }

        // 2. Recoger datos del formulario
        $datos_insumo = [
            'nombre' => $_POST['nombre'] ?? null,
            'descripcion' => $_POST['descripcion'] ?? null,
            'categoria_id' => $_POST['categoria_id'] ?? null,
            'sku' => $_POST['sku'] ?? null,
            'proveedor_id' => $_POST['proveedor_id'] ?? null, 
            'agrupador' => $_POST['agrupador'] ?? null,
            'unidad_medida' => $_POST['unidad_medida'] ?? null,
            'stock_minimo' => $_POST['stock_minimo'] ?? 0,
            'ubicacion' => $_POST['ubicacion'] ?? null,
            'notas' => $_POST['notas'] ?? null,
            'imagen_path' => $nombre_archivo_imagen
        ];

        // 3. Insertar en la base de datos
        list($exito_db, $mensaje_db) = insertar_insumo_completo($pdo, $datos_insumo);
        
        if ($exito_db) {
            echo json_encode(['exito' => true, 'mensaje' => $mensaje_db]);
        } else {
            // Si la DB falla, borrar el archivo que sí se subió
            if ($nombre_archivo_imagen && file_exists($directorio_subida_privado . $nombre_archivo_imagen)) {
                unlink($directorio_subida_privado . $nombre_archivo_imagen);
            }
            throw new Exception($mensaje_db);
        }
        exit;
    }
    
    // ==================================================
    // ACCIÓN: ACTUALIZAR INSUMO EXISTENTE
    // ==================================================
    elseif ($accion === 'actualizar') {

        $insumo_id = (int)($_POST['insumo_id'] ?? 0);
        if ($insumo_id <= 0) {
            throw new Exception("ID de insumo no válido.");
        }

        // 1. Manejar subida de IMAGEN (si se proporcionó una nueva)
        $nombre_archivo_imagen = $_POST['imagen_path_actual'] ?? null;
        
        if (isset($nuevo_nombre_archivo)) {
            $nombre_archivo_imagen = $nuevo_nombre_archivo;
            
            // Verificamos si había una imagen vieja y si es diferente a la nueva
            $imagen_anterior = $_POST['imagen_path_actual'] ?? '';
            if (!empty($imagen_anterior) && file_exists($directorio_subida_privado . $imagen_anterior)) {
                // Borramos el archivo físico viejo para liberar espacio
                unlink($directorio_subida_privado . $imagen_anterior);
            }
        }
        
        // 2. Recoger datos del formulario
        $datos_insumo = [
            'nombre' => $_POST['nombre'] ?? null,
            'descripcion' => $_POST['descripcion'] ?? null,
            'categoria_id' => $_POST['categoria_id'] ?? null,
            'sku' => $_POST['sku'] ?? null,
            'proveedor_id' => $_POST['proveedor_id'] ?? null,
            'agrupador' => $_POST['agrupador'] ?? null,
            'unidad_medida' => $_POST['unidad_medida'] ?? null,
            'stock_minimo' => $_POST['stock_minimo'] ?? 0,
            'ubicacion' => $_POST['ubicacion'] ?? null,
            'notas' => $_POST['notas'] ?? null,
            'imagen_path' => $nombre_archivo_imagen,
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];

        // 3. Actualizar la base de datos
        list($exito_db, $mensaje_db) = actualizar_insumo_completo($pdo, $insumo_id, $datos_insumo);
        
        if ($exito_db) {
            echo json_encode(['exito' => true, 'mensaje' => $mensaje_db]);
        } else {
            throw new Exception($mensaje_db);
        }
        exit;
    }

    // Si no se reconoce la acción
    throw new Exception('Acción no reconocida.');

} catch (Throwable $e) {
    http_response_code(400); // Bad Request
    echo json_encode(['exito' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
}

?>
