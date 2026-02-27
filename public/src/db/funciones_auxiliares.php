<?php
function manejar_subida_movimiento($input_name, $directorio_destino, $allowed_extensions) {
    
    // 1. Verificar si se subió un archivo
    if (!isset($_FILES[$input_name]) || $_FILES[$input_name]['error'] !== UPLOAD_ERR_OK) {
        // Si no se subió (UPLOAD_ERR_NO_FILE) o simplemente no está, no es un error.
        if ($_FILES[$input_name]['error'] === UPLOAD_ERR_NO_FILE) {
            return [true, null]; // No hay archivo, pero la operación continúa.
        }
        // Para otros errores (demasiado grande, etc.)
        if ($_FILES[$input_name]['error'] !== UPLOAD_ERR_OK) {
             return [false, "Error en la subida del archivo. Código: " . $_FILES[$input_name]['error']];
        }
    }
    
    $archivo = $_FILES[$input_name];

    // 2. Validar tamaño
    if ($archivo['size'] > 5 * 1024 * 1024) { // 5 MB Límite
        return [false, "El archivo es demasiado grande (máximo 5MB)."];
    }

    // 3. Validar extensión (Primer filtro)
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (empty($extension) || !in_array($extension, $allowed_extensions)) {
        return [false, "Tipo de archivo no permitido (extensión). Solo se permiten: " . implode(', ', $allowed_extensions) . "."];
    }

    // 4. ⭐️ VERIFICACIÓN DE TIPO MIME (El filtro de seguridad real) ⭐️
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);

    // Mapeo de extensiones permitidas a tipos MIME seguros
    $mime_map = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'pdf'  => 'application/pdf'
    ];

    $allowed_mime_types = [];
    foreach ($allowed_extensions as $ext) {
        if (isset($mime_map[$ext])) {
            $allowed_mime_types[] = $mime_map[$ext];
        }
    }
    // Asegurarnos de no tener duplicados (ej: jpg y jpeg)
    $allowed_mime_types = array_unique($allowed_mime_types);

    if (!in_array($mime_type, $allowed_mime_types)) {
        // ¡Alerta de seguridad! El contenido no coincide con la extensión.
        error_log("Alerta de Seguridad: Archivo '" . $archivo['name'] . "' (MIME: $mime_type) no coincide con extensiones permitidas.");
        return [false, "Error: El contenido del archivo no es válido o está corrupto."];
    }

    // 5. Generar nombre seguro y mover
    $nombre_archivo_seguro = uniqid('file_', true) . '.' . $extension;
    $ruta_final = $directorio_destino . $nombre_archivo_seguro;

    if (move_uploaded_file($archivo['tmp_name'], $ruta_final)) {
        return [true, $nombre_archivo_seguro];
    } else {
        error_log("Error crítico: No se pudo mover el archivo subido a '$ruta_final'.");
        return [false, "Error al mover el archivo subido al destino privado."];
    }

}
