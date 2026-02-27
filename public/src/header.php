<?php
/**
 * ======================================================================
 * HEADER HTML - LIGHT VERSION
 * ======================================================================
 */

// Cabeceras de seguridad...
if (!headers_sent()) {
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: same-origin");
    
    header("Content-Security-Policy: ".
        "default-src 'self'; ".
        "img-src 'self' data: https:; ". 
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; ".
        "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; ".
        "script-src 'self' https://cdn.jsdelivr.net https://unpkg.com 'unsafe-inline' 'unsafe-eval'; ".
        "connect-src 'self' https://cdn.jsdelivr.net;"
    );
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <link href="style.css?v=6.0" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    
    <title><?php echo isset($titulo_pagina) ? htmlspecialchars($titulo_pagina) . ' - Gestor' : 'Gestor de Insumos'; ?></title>
    
    <?php if (function_exists('obtener_token_csrf')): ?>
    <script>
        window.CSRF_TOKEN = '<?php echo htmlspecialchars(obtener_token_csrf()); ?>';
        window.USUARIO_ROL = '<?php echo htmlspecialchars($USUARIO_ROL ?? 'observador'); ?>';
    </script>
    <?php endif; ?>

    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#4A55A2">
    <link rel="apple-touch-icon" href="icon-192.png">
    
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('service-worker.js')
                    .then(reg => console.log('Service Worker registrado', reg))
                    .catch(err => console.log('Error Service Worker', err));
            });
        }
    </script>

</head>
<body class="<?php echo $body_class ?? 'bg-light'; ?>">

<div class="sidebar-overlay d-lg-none" id="sidebar-overlay"></div>