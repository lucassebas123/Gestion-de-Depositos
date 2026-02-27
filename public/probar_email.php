<?php
// ======================================================================
// SCRIPT DE PRUEBA DE EMAIL (VISUAL)
// ======================================================================

// 1. Cargar el núcleo (Configuración, .env, Funciones, Header y Menú)
require_once 'src/init.php';

// Definir variable de mensaje para mostrar en el HTML
$resultado_html = "";

// Lógica de envío al hacer clic
if (isset($_GET['enviar'])) {
    
    // Cambia esto por tu correo real si es diferente al del .env
    // Ojo: Usará el mismo del .env si no lo cambias aquí.
    $mi_correo = $_ENV['SMTP_USER']; 

    $cuerpo = "
        <h2>¡Hola!</h2>
        <p>Si estás leyendo esto, la configuración SMTP de tu <strong>Gestor de Insumos</strong> funciona perfectamente.</p>
        <p><strong>Fecha:</strong> " . date('d/m/Y H:i:s') . "</p>
        <hr>
        <small>Este es un mensaje automático de prueba.</small>
    ";

    list($exito, $msg) = enviar_email($mi_correo, "Administrador", "Prueba de Sistema Exitosa", $cuerpo);

    if ($exito) {
        $resultado_html = "
            <div class='alert alert-success shadow-sm'>
                <h4 class='alert-heading'><i class='bi bi-check-circle-fill me-2'></i>¡Correo Enviado!</h4>
                <p class='mb-0'>$msg</p>
                <hr>
                <p class='mb-0'>Revisa tu bandeja de entrada (<strong>$mi_correo</strong>). No olvides revisar SPAM.</p>
            </div>";
    } else {
        $resultado_html = "
            <div class='alert alert-danger shadow-sm'>
                <h4 class='alert-heading'><i class='bi bi-exclamation-triangle-fill me-2'></i>Error de Envío</h4>
                <p class='mb-0'>$msg</p>
                <hr>
                <p class='mb-0'>Verifica tu archivo <code>.env</code> (usuario y contraseña de aplicación).</p>
            </div>";
    }
}
?>

<div class="content-wrapper">
    <?php require 'src/navbar.php'; ?>
    
    <div class="page-content-container">
        
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                
                <div class="card shadow border-0 mt-5">
                    <div class="card-header bg-primary text-white py-3">
                        <h4 class="mb-0"><i class="bi bi-envelope-paper-fill me-2"></i>Prueba de Email</h4>
                    </div>
                    <div class="card-body p-4 text-center">
                        
                        <p class="lead text-muted mb-4">
                            Esta herramienta probará la conexión SMTP configurada en tu archivo <code>.env</code>.
                        </p>

                        <?php if ($resultado_html): ?>
                            <?php echo $resultado_html; ?>
                            <a href="probar_email.php" class="btn btn-outline-secondary mt-3">Volver a intentar</a>
                        <?php else: ?>
                            <div class="d-grid gap-2 col-8 mx-auto">
                                <a href="probar_email.php?enviar=1" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send-fill me-2"></i> Enviar Correo de Prueba
                                </a>
                            </div>
                            <p class="small text-muted mt-3">Se enviará a: <strong><?php echo $_ENV['SMTP_USER'] ?? 'No configurado'; ?></strong></p>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<?php require 'src/footer.php'; ?>