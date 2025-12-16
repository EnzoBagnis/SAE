<?php

require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';
require_once __DIR__ . '/../phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * EmailService class - Email sending service
 * Handles email sending with PHPMailer
 */
class EmailService
{
    private $env;

    public function __construct()
    {
        // Load email configuration from .env file
        $this->env = parse_ini_file(__DIR__ . '/../../config/.env');
    }

    /**
     * Configure PHPMailer with SMTP settings
     * @return PHPMailer Configured PHPMailer instance
     */
    private function configureMail()
    {
        $mail = new PHPMailer(true);

        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = $this->env['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->env['MAIL_USERNAME'];
        $mail->Password   = $this->env['MAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $this->env['MAIL_PORT'];
        $mail->CharSet    = 'UTF-8';

        // Set sender information
        $mail->setFrom($this->env['MAIL_USERNAME'], $this->env['MAIL_FROM_NAME']);

        return $mail;
    }

    /**
     * Send verification code email
     * @param string $recipient Email recipient
     * @param string $verificationCode Verification code to send
     * @return bool Success status
     */
    public function sendVerificationCode($recipient, $verificationCode)
    {
        try {
            $mail = $this->configureMail();
            $mail->addAddress($recipient);

            // Email content
            $mail->Subject = 'Code de vérification - StudTraj';
            $mail->Body    = "Bonjour,\n\n";
            $mail->Body   .= "Votre code de vérification est : $verificationCode\n\n";
            $mail->Body   .= "Cordialement,\n";
            $mail->Body   .= "L'équipe StudTraj";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending error: " . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Send password reset link email
     * @param string $recipient Email recipient
     * @param string $token Reset token
     * @return bool Success status
     */
    public function sendResetLink($recipient, $token)
    {
        try {
            $mail = $this->configureMail();
            $mail->addAddress($recipient);

            // Generate reset link URL
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $resetLink = $protocol . "://" . $host . "/index.php?action=resetpassword&token=" . $token;

            // Email content
            $mail->Subject = 'Réinitialisation de mot de passe - StudTraj';
            $mail->Body    = "Bonjour,\n\n";
            $mail->Body   .= "Vous avez demandé à réinitialiser votre mot de passe sur StudTraj.\n\n";
            $mail->Body   .= "Cliquez sur le lien suivant pour créer un nouveau mot de passe :\n";
            $mail->Body   .= $resetLink . "\n\n";
            $mail->Body   .= "Ce lien est valable pendant 1 heure.\n\n";
            $mail->Body   .= "Si vous n'avez pas demandé cette réinitialisation, ignorez ce message.\n\n";
            $mail->Body   .= "Cordialement,\n";
            $mail->Body   .= "L'équipe StudTraj";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Reset email sending error: " . $mail->ErrorInfo);
            return false;
        }
    }
}
