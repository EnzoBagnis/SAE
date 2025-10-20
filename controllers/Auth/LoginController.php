<?php
namespace Controllers\Auth;

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/AuthController.php';

/**
 * LoginController - Handles login display and authentication
 */
class LoginController extends \BaseController {
    private $authController;

    public function __construct() {
        $this->authController = new AuthController();
    }

    /**
     * Show login page
     */
    public function index() {
        // Prepare messages from URL parameters
        $errorMessage = null;
        $successMessage = null;

        if (isset($_GET['error'])) {
            $errorMessage = $this->getErrorMessage($_GET['error']);
        }

        if (isset($_GET['succes'])) {
            $successMessage = $this->getSuccessMessage($_GET['succes']);
        }

        $data = [
            'title' => 'Connexion - StudTraj',
            'error_message' => $errorMessage,
            'success_message' => $successMessage
        ];

        $this->loadView('auth/login', $data);
    }

    /**
     * Process login form
     */
    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=login');
            exit;
        }

        $email = $_POST['mail'] ?? '';
        $password = $_POST['mdp'] ?? '';

        if (empty($email) || empty($password)) {
            header('Location: /index.php?action=login&error=empty_fields');
            exit;
        }

        $result = $this->authController->login($email, $password);

        if ($result['success']) {
            $this->authController->createSession($result['user']);
            header('Location: /index.php?action=dashboard');
            exit;
        } else {
            header('Location: /index.php?action=login&error=' . $result['error']);
            exit;
        }
    }

    /**
     * Get error message from error code
     */
    private function getErrorMessage($errorCode) {
        $messages = [
            'email_not_found' => 'Cet email n\'existe pas !',
            'password_incorrect' => 'Mot de passe incorrect',
            'empty_fields' => 'Tous les champs sont requis',
            'token_invalide' => 'Lien de réinitialisation invalide',
            'token_expire' => 'Lien de réinitialisation expiré'
        ];

        return $messages[$errorCode] ?? 'Une erreur est survenue';
    }

    /**
     * Get success message from success code
     */
    private function getSuccessMessage($successCode) {
        $messages = [
            'reset_envoye' => 'Un email de réinitialisation a été envoyé !',
            'mdp_reinitialise' => 'Votre mot de passe a été réinitialisé avec succès !'
        ];

        return $messages[$successCode] ?? '';
    }
}

