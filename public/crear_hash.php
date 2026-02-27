<?php
// crear_hash.php
// Una herramienta temporal para generar un hash válido en TU sistema.

$password_plana = '1234';
$hash_generado = password_hash($password_plana, PASSWORD_DEFAULT);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generador de Hash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style> body { background: #f8f9fa; padding: 2rem; } </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h1>Generador de Hash de Contraseña</h1>
                    </div>
                    <div class="card-body">
                        <p>Este script ha generado un hash para la contraseña: <strong><?php echo $password_plana; ?></strong></p>
                        
                        <div class="alert alert-success">
                            <p class="mb-2"><strong>Copia TODO el texto de este recuadro:</strong></p>
                            <textarea class="form-control" rows="3" readonly onclick="this.select();"><?php echo $hash_generado; ?></textarea>
                        </div>

                        <hr>
                        <h3>Siguientes Pasos:</h3>
                        <ol>
                            <li>Copia el hash de arriba (el texto largo que empieza con `$2y$10...`).</li>
                            <li>Abre phpMyAdmin y ve a la tabla <strong><code>usuarios</code></strong>.</li>
                            <li>Busca la fila del usuario <strong><code>admin</code></strong> y haz clic en "Editar".</li>
                            <li>Borra el contenido del campo <strong><code>password_hash</code></strong>.</li>
                            <li><strong>Pega</strong> el nuevo hash que copiaste.</li>
                            <li>Haz clic en "Continuar" para guardar los cambios.</li>
                            <li>¡Intenta iniciar sesión en <code>acceso.php</code> con <code>admin</code> / <code>1234</code>!</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>