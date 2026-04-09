<?php
// Richiediamo l'autoloader di Composer
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($email, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // Impostazioni del Server
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST') ?: 'mailhog';
        $mail->Port       = getenv('SMTP_PORT') ?: 1025;
        
        $smtpUser = getenv('SMTP_USER');
        $smtpPass = getenv('SMTP_PASS');
        
        // Se abbiamo le credenziali (Siamo in Produzione su Render con Brevo/Resend)
        if ($smtpUser && $smtpPass) {
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            
            // Abilita la crittografia TLS per i server esterni
            if ($mail->Port == 587) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
        } else {
            // Nessuna credenziale (Siamo in Locale con Mailhog)
            $mail->SMTPAuth   = false;
            $mail->SMTPAutoTLS = false;
        }

        // Mittente e Destinatario
        $mail->setFrom('no-reply@camagru.it', 'Camagru App');
        $mail->addAddress($email);

        // Contenuto della mail
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Errore invio mail: {$mail->ErrorInfo}");
        return false;
    }
}
?>