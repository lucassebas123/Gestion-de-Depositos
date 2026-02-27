<?php
/**
 * MÓDULO: FUNCIONES DE EMAIL
 * Utiliza PHPMailer para envíos SMTP seguros.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Asegurarnos de cargar el autoload si no está cargado
require_once __DIR__ . '/../../../vendor/autoload.php';

/**
 * Envía un correo electrónico HTML.
 * * @param string $destinatario Email del receptor
 * @param string $nombre_destinatario Nombre del receptor
 * @param string $asunto Asunto del correo
 * @param string $cuerpoHTML Contenido en HTML
 * @return array [bool $exito, string $mensaje]
 */
function enviar_email($destinatario, $nombre_destinatario, $asunto, $cuerpoHTML) {
    
    $mail = new PHPMailer(true);

    try {
        // 1. Configuración del Servidor
        // $mail->SMTPDebug = 2; // Descomentar para ver log detallado en pantalla si falla
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // O ENCRYPTION_SMTPS para puerto 465
        $mail->Port       = $_ENV['SMTP_PORT'];
        $mail->CharSet    = 'UTF-8';

        // 2. Remitente y Destinatario
        $mail->setFrom($_ENV['SMTP_USER'], $_ENV['SMTP_FROM_NAME']);
        $mail->addAddress($destinatario, $nombre_destinatario);

        // 3. Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHTML;
        $mail->AltBody = strip_tags($cuerpoHTML); // Versión texto plano

        $mail->send();
        return [true, "Correo enviado correctamente."];

    } catch (Exception $e) {
        error_log("Error enviando email: {$mail->ErrorInfo}");
        return [false, "No se pudo enviar el correo. Error: {$mail->ErrorInfo}"];
    }
}
?>