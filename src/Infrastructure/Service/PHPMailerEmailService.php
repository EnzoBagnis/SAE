<?php

namespace Infrastructure\Service;

use Domain\Authentication\Service\EmailServiceInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * PHPMailer Email Service - Sends emails using PHPMailer
 *
 * This service implements email sending using the PHPMailer library.
 */
class PHPMailerEmailService implements EmailServiceInterface
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;

    /**
     * Constructor
     *
     * @param string $host SMTP host
     * @param int $port SMTP port
     * @param string $username SMTP username
     * @param string $password SMTP password
     * @param string $fromEmail Sender email address
     * @param string $fromName Sender name
     */
    public function __construct(
        string $host,
        int $port,
        string $username,
        string $password,
        string $fromEmail,
        string $fromName
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    /**
     * {@inheritdoc}
     */
    public function sendVerificationCode(
        string $email,
        string $firstName,
        string $verificationCode
    ): bool {
        $subject = 'Code de vérification - Plateforme SAE';
        $body = "
            <h2>Bienvenue {$firstName}!</h2>
            <p>Votre code de vérification est : <strong>{$verificationCode}</strong></p>
            <p>Merci de votre inscription.</p>
        ";

        return $this->sendEmail($email, $subject, $body);
    }

    /**
     * {@inheritdoc}
     */
    public function sendPasswordResetEmail(
        string $email,
        string $firstName,
        string $resetToken
    ): bool {
        $resetUrl = BASE_URL . "/index.php?action=resetpassword&token={$resetToken}";
        $subject = 'Réinitialisation de mot de passe - Plateforme SAE';
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
     * Send an email using PHPMailer
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @return bool True if sent successfully, false otherwise
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
            error_log("Email sending failed: {$mail->ErrorInfo}");
            return false;
        }
    }
}
