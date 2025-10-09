<?php
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';
require_once __DIR__ . '/../phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Service emailService - Gestion de l'envoi d'emails
 */
class emailService {
    private $env;
    
    public function __construct() {
        $this->env = parse_ini_file(__DIR__ . '/../../config/.env');
    }
    
    /**
     * Configurer PHPMailer avec les paramètres SMTP
     */
    private function configureMail() {
        $mail = new PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host       = $this->env['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->env['MAIL_USERNAME'];
        $mail->Password   = $this->env['MAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $this->env['MAIL_PORT'];
        $mail->CharSet    = 'UTF-8';
        
        $mail->setFrom($this->env['MAIL_USERNAME'], $this->env['MAIL_FROM_NAME']);
        
        return $mail;
    }
    
    /**
     * Envoyer un email de vérification avec code
     */
    public function envoyerCodeVerification($destinataire, $code_verif) {
        try {
            $mail = $this->configureMail();
            $mail->addAddress($destinataire);
            
            $mail->Subject = 'Code de vérification - StudTraj';
            $mail->Body    = "Bonjour,\n\n";
            $mail->Body   .= "Votre code de vérification est : $code_verif\n\n";
            $mail->Body   .= "Cordialement,\n";
            $mail->Body   .= "L'équipe StudTraj";
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur envoi mail : " . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Envoyer un email de réinitialisation de mot de passe
     */
    public function envoyerLienReset($destinataire, $token) {
        try {
            $mail = $this->configureMail();
            $mail->addAddress($destinataire);

            // Générer l'URL du lien de réinitialisation
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $lien = $protocol . "://" . $host . "/views/resetPassword.php?token=" . $token;

            $mail->Subject = 'Réinitialisation de mot de passe - StudTraj';
            $mail->Body    = "Bonjour,\n\n";
            $mail->Body   .= "Vous avez demandé à réinitialiser votre mot de passe sur StudTraj.\n\n";
            $mail->Body   .= "Cliquez sur le lien suivant pour créer un nouveau mot de passe :\n";
            $mail->Body   .= $lien . "\n\n";
            $mail->Body   .= "Ce lien est valable pendant 1 heure.\n\n";
            $mail->Body   .= "Si vous n'avez pas demandé cette réinitialisation, ignorez ce message.\n\n";
            $mail->Body   .= "Cordialement,\n";
            $mail->Body   .= "L'équipe StudTraj";

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Erreur envoi mail reset : " . $mail->ErrorInfo);
            return false;
        }
    }
}
