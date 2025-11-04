<?php

namespace Controllers\Auth;

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/AuthController.php';

/**
 * RegisterController - Handles user registration
 */
class RegisterController extends \BaseController
{
    private $authController;

    public function __construct()
    {
        $this->authController = new AuthController();
    }

    /**
     * Show registration page
     */
    public function index()
    {
        $errorMessage = null;

        if (isset($_GET['error'])) {
            $errorMessage = $this->getErrorMessage($_GET['error']);
        }

        $data = [
            'title' => 'Inscription gratuite - StudTraj',
            'error_message' => $errorMessage
        ];

        $this->loadView('auth/register', $data);
    }

    /**
     * Process registration form
     */
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=signup');
            exit;
        }

        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
        $email = $_POST['mail'] ?? '';
        $password = $_POST['mdp'] ?? '';

        if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
            header('Location: /index.php?action=signup&error=empty_fields');
            exit;
        }

        $result = $this->authController->register($nom, $prenom, $email, $password);

        if ($result['success']) {
            session_start();
            $_SESSION['mail'] = $result['email'];
            header('Location: /index.php?action=emailverification&succes=inscription');
            exit;
        } else {
            header('Location: /index.php?action=signup&error=' . $result['error']);
            exit;
        }
    }

    /**
     * Get error message from error code
     */
    private function getErrorMessage($errorCode)
    {
        $messages = [
            'email_exists' => 'Cet email est déjà utilisé !',
            'pending_exists' => 'Un code de vérification a déjà été envoyé à cet email',
            'creation_failed' => 'Erreur lors de l\'inscription',
            'email_send_failed' => 'Erreur lors de l\'envoi de l\'email',
            'empty_fields' => 'Tous les champs sont requis'
        ];

        return $messages[$errorCode] ?? 'Une erreur est survenue';
    }
}
