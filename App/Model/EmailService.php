<?php

namespace App\Model;

use Core\Config\EnvLoader;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Email Service
 * Handles email sending using PHPMailer
 */
class EmailService
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->host = EnvLoader::get('MAIL_HOST', 'smtp-studtraj.alwaysdata.net');
        $this->port = (int) EnvLoader::get('MAIL_PORT', 587);
        $this->username = EnvLoader::get('MAIL_USERNAME', '');
        $this->password = EnvLoader::get('MAIL_PASSWORD', '');
        $this->fromEmail = EnvLoader::get('MAIL_USERNAME', $this->username);
        $this->fromName = EnvLoader::get('MAIL_FROM_NAME', 'StudTraj');
    }

    /**
     * Send verification code email
     *
     * @param string $email Recipient email
     * @param string $firstName Recipient first name
     * @param string $verificationCode Verification code
     * @return bool True if sent successfully
     */
    public function sendVerificationCode(
        string $email,
        string $firstName,
        string $verificationCode
    ): bool {
        $subject = 'Code de vérification - StudTraj';
        $body = "
            <h2>Bienvenue {$firstName}!</h2>
            <p>Votre code de vérification est : <strong>{$verificationCode}</strong></p>
            <p>Merci de votre inscription.</p>
        ";

        return $this->sendEmail($email, $subject, $body);
    }

    /**
     * Send password reset email
     *
     * @param string $email Recipient email
     * @param string $firstName Recipient first name
     * @param string $resetToken Reset token
     * @return bool True if sent successfully
     */
    public function sendPasswordResetEmail(
        string $email,
        string $firstName,
        string $resetToken
    ): bool {
        $baseUrl = EnvLoader::get('BASE_URL', 'https://studtraj.alwaysdata.net');
        $resetUrl = $baseUrl . "/index.php?action=resetpassword&token={$resetToken}";

        $subject = 'Réinitialisation de mot de passe - StudTraj';
        $body = "
            <h2>Bonjour {$firstName},</h2>
            <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
            <p>Cliquez sur le lien suivant pour réinitialiser votre mot de passe :</p>
            <p><a href='{$resetUrl}'>{$resetUrl}</a></p>
            <p>Ce lien expire dans 1 heure.</p>
            <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
        ";

        return $this->sendEmail($email, $subject, $body);
    }

    /**
     * Send email using PHPMailer
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @return bool True if sent successfully
     */
    private function sendEmail(string $to, string $subject, string $body): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->username;
            $mail->Password = $this->password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->port;
            $mail->CharSet = 'UTF-8';

            // Recipients
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $mail->ErrorInfo);
            return false;
        }
    }
}

