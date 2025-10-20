<?php
namespace Controllers\Auth;

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/AuthController.php';

/**
 * EmailVerificationController - Handles email verification
 */
class EmailVerificationController extends \BaseController {
    private $authController;

    public function __construct() {
        $this->authController = new AuthController();
    }

    /**
     * Show email verification page
     */
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $errorMessage = null;
        $successMessage = null;

        if (isset($_GET['erreur'])) {
            $errorMessage = $this->getErrorMessage($_GET['erreur']);
        }

        if (isset($_GET['succes'])) {
            $successMessage = $this->getSuccessMessage($_GET['succes']);
        }

        $data = [
            'title' => 'Vérification Email - StudTraj',
            'error_message' => $errorMessage,
            'success_message' => $successMessage
        ];

        $this->loadView('auth/email-verification', $data);
    }

    /**
     * Verify email code
     */
    public function verify() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=emailverification');
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $code = $_POST['code'] ?? '';
        $email = $_SESSION['mail'] ?? '';

        if (empty($email)) {
            header('Location: /index.php?action=signup&error=session_expiree');
            exit;
        }

        if (empty($code)) {
            header('Location: /index.php?action=emailverification&erreur=code_vide');
            exit;
        }

        $result = $this->authController->validateCode($email, $code);

        if ($result['success']) {
            $this->authController->createSession($result['user']);
            header('Location: /index.php?action=dashboard');
            exit;
        } else {
            header('Location: /index.php?action=emailverification&erreur=' . $result['error']);
            exit;
        }
    }

    /**
     * Resend verification code
     */
    public function resendCode() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=emailverification');
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['mail'])) {
            header('Location: /index.php?action=signup&error=session_expiree');
            exit;
        }

        $email = $_SESSION['mail'];
        $result = $this->authController->resendCode($email);

        if ($result['success']) {
            header('Location: /index.php?action=emailverification&succes=code_renvoye');
        } else {
            header('Location: /index.php?action=emailverification&erreur=' . $result['error']);
        }
        exit;
    }

    /**
     * Get error message from error code
     */
    private function getErrorMessage($errorCode) {
        $messages = [
            'code_incorrect' => 'Code incorrect, veuillez réessayer.',
            'registration_expired' => 'Votre inscription a expiré. Veuillez vous réinscrire.',
            'email_send_failed' => 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.',
            'session_expiree' => 'Session expirée. Veuillez vous réinscrire.',
            'code_vide' => 'Veuillez entrer le code de vérification'
        ];

        return $messages[$errorCode] ?? 'Une erreur est survenue';
    }

    /**
     * Get success message from success code
     */
    private function getSuccessMessage($successCode) {
        $messages = [
            'inscription' => 'Un code de vérification a été envoyé à votre email !',
            'code_renvoye' => 'Un nouveau code de vérification a été envoyé !'
        ];

        return $messages[$successCode] ?? '';
    }
}
