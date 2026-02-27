<?php
// ======================================================================
// RECUPERAR CONTRASEÑA (SOLICITUD) - ESTILO CORREGIDO
// ======================================================================
session_start();

require_once __DIR__ . '/src/db_conexion.php';
require_once __DIR__ . '/src/funciones_db.php'; 
require_once __DIR__ . '/src/db/funciones_email.php'; // Asegurar carga de email

date_default_timezone_set('America/Argentina/Buenos_Aires');

$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if (empty($email)) {
        $mensaje = "Por favor, ingrese su correo electrónico.";
        $tipo_mensaje = "danger";
    } else {
        try {
            $pdo = conectar_db();
            try { $pdo->exec("SET time_zone = '" . date('P') . "'"); } catch (Exception $e) {}

            // 1. Verificar si el email existe
            $stmt = $pdo->prepare("SELECT id, username FROM usuarios WHERE email = ? AND activo = 1");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario) {
                // 2. Generar Token Único
                $token = bin2hex(random_bytes(32));
                $expira = date('Y-m-d H:i:s', strtotime('+1 hour')); 

                // 3. Guardar Token
                $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                $stmtInsert = $pdo->prepare("INSERT INTO password_resets (email, token, expira) VALUES (?, ?, ?)");
                $stmtInsert->execute([$email, $token, $expira]);

                // 4. Enviar Email
                $link = "http://" . $_SERVER['HTTP_HOST'] . "/mi_gestor/public/restablecer.php?token=" . $token;

                $cuerpoHTML = "
                    <div style='font-family: Arial, sans-serif; color: #333;'>
                        <h2 style='color: #4A55A2;'>Recuperación de Contraseña</h2>
                        <p>Hola <strong>{$usuario['username']}</strong>,</p>
                        <p>Recibimos una solicitud para restablecer tu contraseña.</p>
                        <p>Haz clic en el siguiente botón para crear una nueva clave:</p>
                        <p style='text-align: center; margin: 30px 0;'>
                            <a href='$link' style='background-color: #4A55A2; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>Restablecer Contraseña</a>
                        </p>
                        <p style='font-size: 12px; color: #777;'>Si no solicitaste esto, ignora este mensaje. El enlace expira en 1 hora.</p>
                    </div>
                ";

                list($enviado, $msgEmail) = enviar_email($email, $usuario['username'], "Restablecer Clave - Gestor Insumos", $cuerpoHTML);

                if ($enviado) {
                    $mensaje = "¡Correo enviado! Revisa tu bandeja de entrada.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al enviar correo. Intente más tarde.";
                    $tipo_mensaje = "danger";
                    error_log("Error Mailer: $msgEmail");
                }

            } else {
                // Mensaje genérico de seguridad
                $mensaje = "Si el correo existe, recibirás las instrucciones.";
                $tipo_mensaje = "info";
            }

        } catch (Exception $e) {
            $mensaje = "Error del sistema.";
            $tipo_mensaje = "danger";
            error_log("Error Recuperar: " . $e->getMessage());
        }
    }
}

// Configuración para Header (Usa los mismos estilos que login)
$titulo_pagina = "Recuperar Acceso";
$body_class = "login-page-body"; // ¡Esto activa la imagen de fondo y el centrado!

require __DIR__ . '/src/header.php';
?>

<div class="container d-flex flex-column align-items-center justify-content-center" style="position: relative; z-index: 2; min-height: 100vh;">
    
    <div class="glass-card text-center">
        
        <div class="bg-white rounded-circle p-3 d-inline-block mb-3 shadow-lg">
            <i class="bi bi-envelope-at-fill fs-1 text-primary"></i>
        </div>

        <h3 class="mb-2 text-white fw-bold">Recuperar Acceso</h3>
        <p class="text-white-50 mb-4 small">Ingresa tu correo electrónico registrado para recibir un enlace de recuperación.</p>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> border-0 bg-opacity-75 shadow-sm mb-4 text-start">
                <i class="bi bi-info-circle-fill me-2"></i><?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="form-floating mb-4">
                <input type="email" class="form-control" id="email" name="email" placeholder="nombre@ejemplo.com" required>
                <label for="email"><i class="bi bi-envelope me-2"></i>Correo Electrónico</label>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg btn-login-premium">
                    Enviar Enlace <i class="bi bi-send-fill ms-2"></i>
                </button>
                
                <a href="acceso.php" class="btn btn-outline-light border-0 mt-2">
                    <i class="bi bi-arrow-left me-2"></i>Volver al Login
                </a>
            </div>
        </form>
    </div>
    
    <div class="text-center mt-4">
        <small class="text-white-50">
            &copy; <?= date('Y') ?> Sistema de Gestión Interno
        </small>
    </div>

</div>

<?php require __DIR__ . '/src/footer.php'; ?>