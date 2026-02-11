<?php

namespace Presentation\Controller\Authentication;

use Application\Authentication\UseCase\RequestPasswordReset;
use Application\Authentication\UseCase\ResetPassword;
use Application\Authentication\DTO\PasswordResetRequest;
use Application\Authentication\DTO\UpdatePasswordRequest;

/**
 * Password Reset Controller
 * Handles password reset functionality
 */
class PasswordResetController
{
    private ?RequestPasswordReset $requestPasswordResetUseCase;
    private ?ResetPassword $resetPasswordUseCase;

    /**
     * Constructor
     *
     * @param RequestPasswordReset|null $requestPasswordResetUseCase Password reset use case
     * @param ResetPassword|null $resetPasswordUseCase Reset password use case
     */
    public function __construct(
        ?RequestPasswordReset $requestPasswordResetUseCase = null,
        ?ResetPassword $resetPasswordUseCase = null
    ) {
        $this->requestPasswordResetUseCase = $requestPasswordResetUseCase;
        $this->resetPasswordUseCase = $resetPasswordUseCase;
    }

    /**
     * Show forgot password page
     *
     * @return void
     */
    public function forgotPassword(): void
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
     *
     * @return void
     */
    public function requestReset(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?action=forgotpassword');
            exit;
        }

        // Accept both 'mail' and 'email' for compatibility
        $email = trim($_POST['mail'] ?? $_POST['email'] ?? '');

        // Validate email
        if (empty($email)) {
            header('Location: ' . BASE_URL . '/index.php?action=forgotpassword&error=email_vide');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . BASE_URL . '/index.php?action=forgotpassword&error=email_invalide');
            exit;
        }

        // Execute use case if available
        if ($this->requestPasswordResetUseCase) {
            $request = new PasswordResetRequest($email);
            $response = $this->requestPasswordResetUseCase->execute($request);

            if ($response->success) {
                $_SESSION['success'] = $response->message;
                header('Location: ' . BASE_URL . '/index.php?action=login');
            } else {
                $_SESSION['error'] = $response->message;
                header('Location: ' . BASE_URL . '/index.php?action=forgotpassword');
            }
        } else {
            // Fallback if use case not injected
            $_SESSION['info'] = 'La fonctionnalité de réinitialisation sera bientôt disponible';
            header('Location: ' . BASE_URL . '/index.php?action=login');
        }
        exit;
    }

    /**
     * Show reset password form
     *
     * @return void
     */
    public function showResetForm(): void
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            header('Location: ' . BASE_URL . '/index.php?action=login&error=token_invalide');
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
     * Process password reset
     *
     * @return void
     */
    public function resetPassword(): void
    {
        error_log("PasswordResetController::resetPassword() - Method called");
        error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST data: " . print_r($_POST, true));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Not a POST request, redirecting to login");
            header('Location: ' . BASE_URL . '/index.php?action=login');
            exit;
        }

        $token = $_POST['token'] ?? '';
        // Accept both naming conventions for compatibility
        $password = $_POST['nouveau_mdp'] ?? $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_mdp'] ?? $_POST['confirm_password'] ?? '';

        error_log("Token: " . ($token ? 'present' : 'missing'));
        error_log("Password: " . ($password ? 'present' : 'missing'));
        error_log("Confirm Password: " . ($confirmPassword ? 'present' : 'missing'));

        // Validate inputs
        if (empty($token) || empty($password) || empty($confirmPassword)) {
            error_log("Validation failed: empty fields");
            $url = BASE_URL . '/index.php?action=resetpassword&token=' . urlencode($token);
            header('Location: ' . $url . '&error=champs_manquants');
            exit;
        }

        if ($password !== $confirmPassword) {
            error_log("Validation failed: passwords don't match");
            $url = BASE_URL . '/index.php?action=resetpassword&token=' . urlencode($token);
            header('Location: ' . $url . '&error=passwords_mismatch');
            exit;
        }

        // Validate password strength
        if (strlen($password) < 8) {
            error_log("Validation failed: password too short");
            $url = BASE_URL . '/index.php?action=resetpassword&token=' . urlencode($token);
            header('Location: ' . $url . '&error=password_too_short');
            exit;
        }

        error_log("Use case available: " . ($this->resetPasswordUseCase ? 'yes' : 'no'));

        // Execute use case if available
        if ($this->resetPasswordUseCase) {
            error_log("Executing reset password use case");
            $request = new UpdatePasswordRequest($token, $password);
            $response = $this->resetPasswordUseCase->execute($request);

            error_log("Use case response - Success: " . ($response->success ? 'yes' : 'no') . ", Message: " . $response->message);

            if ($response->success) {
                $_SESSION['success'] = $response->message;
                error_log("Redirecting to login with success message");
                header('Location: ' . BASE_URL . '/index.php?action=login');
            } else {
                $_SESSION['error'] = $response->message;
                error_log("Redirecting to reset form with error message");
                header('Location: ' . BASE_URL . '/index.php?action=resetpassword&token=' . urlencode($token));
            }
        } else {
            // Fallback if use case not injected
            error_log("Use case not available, using fallback");
            $_SESSION['info'] = 'La mise à jour du mot de passe sera bientôt disponible';
            header('Location: ' . BASE_URL . '/index.php?action=login');
        }
        exit;
    }

    /**
     * Get error message from error code
     *
     * @param string $errorCode Error code
     * @return string Error message
     */
    private function getErrorMessage(string $errorCode): string
    {
        $messages = [
            'email_vide' => 'Veuillez saisir votre adresse email',
            'email_invalide' => 'Adresse email invalide',
            'token_invalide' => 'Token invalide ou expiré',
            'passwords_mismatch' => 'Les mots de passe ne correspondent pas',
            'password_too_short' => 'Le mot de passe doit contenir au moins 8 caractères',
            'champs_manquants' => 'Veuillez remplir tous les champs'
        ];

        return $messages[$errorCode] ?? 'Une erreur est survenue';
    }

    /**
     * Load a view
     *
     * @param string $view View name
     * @param array $data Data to pass to view
     * @return void
     */
    private function loadView(string $view, array $data = []): void
    {
        extract($data);
        require_once __DIR__ . '/../../Views/' . $view . '.php';
    }
}
