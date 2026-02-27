<?php
// ======================================================================
// VER ARCHIVO PRIVADO
// ======================================================================
// Refactorizado v1.0
// * Sirve archivos desde /uploads_privados/ de forma segura.

// 1. Cargar Auth
// 'auth_check.php' nos da la sesión, la conexión ($pdo) y la validación de usuario.
// ¡Esto previene que alguien adivine la URL de un archivo!
require_once 'src/auth_check.php';

// 2. Definir la ruta a la carpeta privada
$directorio_privado_base = __DIR__ . '/../uploads_privados/';

// 3. Obtener el nombre del archivo de la DB (usando $pdo global)
$nombre_archivo_seguro = null;

try {
    // Si pedimos la imagen de un INSUMO
    if (isset($_GET['insumo_id'])) {
        $id = (int)$_GET['insumo_id'];
        $stmt = $pdo->prepare("SELECT imagen_path FROM insumos WHERE id = ?");
        $stmt->execute([$id]);
        $resultado = $stmt->fetch();
        if ($resultado && !empty($resultado['imagen_path'])) {
            $nombre_archivo_seguro = $resultado['imagen_path'];
        }
    }
    
    // Si pedimos el recibo de un MOVIMIENTO
    elseif (isset($_GET['movimiento_id'])) {
        $id = (int)$_GET['movimiento_id'];
        // Cualquier usuario logueado puede ver el recibo (ya validado por auth_check.php)
        $stmt = $pdo->prepare("SELECT recibo_path FROM movimientos WHERE id = ?");
        $stmt->execute([$id]);
        $resultado = $stmt->fetch();
        if ($resultado && !empty($resultado['recibo_path'])) {
            $nombre_archivo_seguro = $resultado['recibo_path'];
        }
    }

} catch (Exception $e) {
    $nombre_archivo_seguro = null;
    error_log("Error en ver_archivo.php: " . $e->getMessage());
}

// 4. Servir el archivo (si se encontró y existe)
$ruta_completa_archivo = $directorio_privado_base . $nombre_archivo_seguro;

if ($nombre_archivo_seguro && file_exists($ruta_completa_archivo)) {
    
    $extension = strtolower(pathinfo($nombre_archivo_seguro, PATHINFO_EXTENSION));
    $mime_type = 'application/octet-stream';
    
    switch ($extension) {
        case 'jpg':
        case 'jpeg': $mime_type = 'image/jpeg'; break;
        case 'png':  $mime_type = 'image/png'; break;
        case 'pdf':  $mime_type = 'application/pdf'; break;
    }
    
    header('Content-Type: ' . $mime_type);
    header('Content-Length: ' . filesize($ruta_completa_archivo));
    
    // Mostrar en el navegador (inline) en lugar de forzar descarga (attachment)
    header('Content-Disposition: inline; filename="' . basename($nombre_archivo_seguro) . '"');
    
    readfile($ruta_completa_archivo);
    exit;
    
} else {
    // 5. Si no hay archivo, mostrar un "placeholder"
    $placeholder_url = 'https://placehold.co/100x100/e9ecef/6c757d?text=N/A';
    header('Location: ' . $placeholder_url);
    exit;
}
?>