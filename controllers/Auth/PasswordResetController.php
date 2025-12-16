<?php

namespace Controllers\Auth;

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/AuthController.php';

/**
 * PasswordResetController - Handles password reset functionality
 */
class PasswordResetController extends \BaseController
{
    private $authController;

    public function __construct()
    {
        $this->authController = new AuthController();
    }

    /**
     * Show forgot password page
     */
    public function forgotPassword()
    {
        $errorMessage = null;

        if (isset($_GET['error'])) {
            $errorMessage = $this->getErrorMessage($_GET['error']);
        }

        $data = [
            'title' => 'Mot de passe oublié - StudTraj',
            'error_message' => $errorMessage
        ];

        $this->loadView('auth/forgot-password', $data);
    }

    /**
     * Request password reset
     */
    public function requestReset()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=forgotpassword');
            exit;
        }

        $email = trim($_POST['mail'] ?? '');

        if (empty($email)) {
            header('Location: /index.php?action=forgotpassword&error=email_vide');
            exit;
        }

        $result = $this->authController->requestPasswordReset($email);

        if ($result['success']) {
            header('Location: /index.php?action=login&succes=reset_envoye');
        } else {
            header('Location: /index.php?action=forgotpassword&error=' . $result['error']);
        }
        exit;
    }

    /**
     * Show reset password page with token
     */
    public function showResetForm()
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            header('Location: /index.php?action=login&error=token_invalide');
            exit;
        }

        $errorMessage = null;

        if (isset($_GET['error'])) {
            $errorMessage = $this->getErrorMessage($_GET['error']);
        }

        $data = [
            'title' => 'Réinitialiser le mot de passe - StudTraj',
            'token' => $token,
            'error_message' => $errorMessage
        ];

        $this->loadView('auth/reset-password', $data);
    }

    /**
     * Reset password with token
     */
    public function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=login');
            exit;
        }

        $token = $_POST['token'] ?? '';
        $newPassword = $_POST['nouveau_mdp'] ?? '';
        $confirmPassword = $_POST['confirm_mdp'] ?? '';

        if (empty($token) || empty($newPassword)) {
            header('Location: /index.php?action=resetpassword&token=' . urlencode($token) . '&error=champs_vides');
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            header('Location: /index.php?action=resetpassword&token=' . urlencode($token) . '&error=mdp_different');
            exit;
        }

        $result = $this->authController->resetPassword($token, $newPassword);

        if ($result['success']) {
            header('Location: /index.php?action=login&succes=mdp_reinitialise');
        } else {
            header('Location: /index.php?action=login&error=' . $result['error']);
        }
        exit;
    }

    /**
     * Get error message from error code
     */
    private function getErrorMessage($errorCode)
    {
        $messages = [
            'email_not_found' => 'Aucun compte trouvé avec cet email.',
            'email_send_failed' => 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.',
            'email_vide' => 'Veuillez entrer votre email',
            'token_invalide' => 'Lien de réinitialisation invalide',
            'token_expired' => 'Lien de réinitialisation expiré',
            'champs_vides' => 'Tous les champs sont requis',
            'mdp_different' => 'Les mots de passe ne correspondent pas'
        ];

        return $messages[$errorCode] ?? 'Une erreur est survenue';
    }
}
