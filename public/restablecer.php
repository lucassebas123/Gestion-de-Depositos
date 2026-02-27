<?php
// ======================================================================
// RESTABLECER CONTRASEÑA (ACCIÓN) - DISEÑO UNIFICADO
// ======================================================================
session_start();
require_once __DIR__ . '/src/db_conexion.php';
require_once __DIR__ . '/src/funciones_db.php';

date_default_timezone_set('America/Argentina/Buenos_Aires');

$token = $_GET['token'] ?? '';
$mensaje = "";
$tipo_mensaje = "";
$token_valido = false;
$email_asociado = "";

try {
    $pdo = conectar_db();
    try { $pdo->exec("SET time_zone = '" . date('P') . "'"); } catch (Exception $e) {}

    // 1. Validar Token
    $stmt = $pdo->prepare("SELECT email, expira FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if ($reset) {
        if (strtotime($reset['expira']) > time()) {
            $token_valido = true;
            $email_asociado = $reset['email'];
        } else {
            $mensaje = "El enlace ha expirado. Por favor, solicita uno nuevo.";
            $tipo_mensaje = "warning";
        }
    } else {
        $mensaje = "Token inválido o no encontrado.";
        $tipo_mensaje = "danger";
    }

    // 2. Procesar Cambio de Clave
    if ($_SERVER["REQUEST_METHOD"] === "POST" && $token_valido) {
        $pass1 = $_POST['pass1'] ?? '';
        $pass2 = $_POST['pass2'] ?? '';

        if (empty($pass1) || strlen($pass1) < 4) {
            $mensaje = "La contraseña debe tener al menos 4 caracteres.";
            $tipo_mensaje = "warning";
        } elseif ($pass1 !== $pass2) {
            $mensaje = "Las contraseñas no coinciden.";
            $tipo_mensaje = "warning";
        } else {
            // CAMBIAR CLAVE
            $hash = password_hash($pass1, PASSWORD_DEFAULT);
            
            $pdo->beginTransaction();
            try {
                // Actualizar usuario
                $stmtUser = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE email = ?");
                $stmtUser->execute([$hash, $email_asociado]);

                // Borrar el token usado
                $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email_asociado]);

                $pdo->commit();
                
                // Redirigir al login con mensaje de éxito
                header("Location: acceso.php?mensaje_error=" . urlencode("¡Contraseña restablecida! Inicia sesión con tu nueva clave."));
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $mensaje = "Error al guardar: " . $e->getMessage();
                $tipo_mensaje = "danger";
            }
        }
    }

} catch (Exception $e) {
    $mensaje = "Error de conexión con la base de datos.";
    $tipo_mensaje = "danger";
}

// Configuración para Header (Activa el fondo de imagen y centrado)
$titulo_pagina = "Nueva Contraseña";
$body_class = "login-page-body"; 

require __DIR__ . '/src/header.php';
?>

<div class="container d-flex flex-column align-items-center justify-content-center" style="position: relative; z-index: 2; min-height: 100vh;">
    
    <div class="glass-card text-center">
        
        <div class="bg-white rounded-circle p-3 d-inline-block mb-3 shadow-lg">
            <i class="bi bi-key-fill fs-1 text-primary"></i>
        </div>

        <h3 class="mb-2 text-white fw-bold">Nueva Contraseña</h3>
        
        <?php if ($token_valido): ?>
            <p class="text-white-50 mb-4 small">Ingresa tu nueva clave para recuperar el acceso.</p>
        <?php else: ?>
            <p class="text-white-50 mb-4 small">Enlace no válido o expirado.</p>
        <?php endif; ?>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> border-0 bg-opacity-75 shadow-sm mb-4 text-start">
                <i class="bi bi-info-circle-fill me-2"></i><?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <?php if ($token_valido): ?>
            <form method="post" autocomplete="off">
                
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="pass1" name="pass1" placeholder="Nueva clave" required>
                    <label for="pass1"><i class="bi bi-lock me-2"></i>Nueva Contraseña</label>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="pass2" name="pass2" placeholder="Repetir clave" required>
                    <label for="pass2"><i class="bi bi-check-lg me-2"></i>Confirmar Contraseña</label>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg btn-login-premium">
                        Guardar Cambios <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="d-grid gap-2">
                <a href="recuperar.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-arrow-counterclockwise me-2"></i> Solicitar nuevo enlace
                </a>
                <a href="acceso.php" class="btn btn-link text-white-50 text-decoration-none mt-2">
                    Volver al inicio
                </a>
            </div>
        <?php endif; ?>

    </div>
    
    <div class="text-center mt-4">
        <small class="text-white-50">
            &copy; <?= date('Y') ?> Sistema de Gestión Interno
        </small>
    </div>

</div>

<?php require __DIR__ . '/src/footer.php'; ?>