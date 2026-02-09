<?php

namespace Presentation\Controller\Authentication;

use Application\Authentication\UseCase\LoginUser;
use Application\Authentication\DTO\LoginRequest;

/**
 * LoginController - Handles user login requests
 *
 * This controller is thin and delegates business logic to use cases.
 */
class LoginController
{
    private LoginUser $loginUseCase;

    /**
     * Constructor
     *
     * @param LoginUser $loginUseCase Login use case
     */
    public function __construct(LoginUser $loginUseCase)
    {
        $this->loginUseCase = $loginUseCase;
    }

    /**
     * Display login form
     *
     * @return void
     */
    public function index(): void
    {
        require __DIR__ . '/../../Views/auth/login.php';
    }

    /**
     * Process login authentication
     *
     * @return void
     */
    public function authenticate(): void
    {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Debug: Log that we're in authenticate
        error_log("=== LoginController::authenticate() START ===");
        error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST data: " . print_r($_POST, true));
        error_log("Session ID: " . session_id());

        try {
            $email = $_POST['mail'] ?? '';
            $password = $_POST['mdp'] ?? '';

            error_log("Email: $email, Password length: " . strlen($password));

            if (empty($email) || empty($password)) {
                error_log("ERROR: Email ou mot de passe vide");
                $_SESSION['error'] = 'Veuillez remplir tous les champs';
                header('Location: ' . BASE_URL . '/index.php?action=login');
                exit;
            }

            $request = new LoginRequest($email, $password);
            error_log("LoginRequest créé");

            $response = $this->loginUseCase->execute($request);
            error_log("LoginUseCase exécuté");

            error_log("Response success: " . ($response->success ? 'true' : 'false'));
            error_log("Response message: " . $response->message);

            if ($response->success) {
                error_log("Login réussi, redirection vers dashboard");
                header('Location: ' . BASE_URL . '/index.php?action=dashboard');
                exit;
            } else {
                error_log("Login échoué: " . $response->message);
                $_SESSION['error'] = $response->message;
                header('Location: ' . BASE_URL . '/index.php?action=login');
                exit;
            }
        } catch (\Exception $e) {
            error_log("EXCEPTION dans authenticate: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error'] = 'Une erreur est survenue lors de la connexion';
            header('Location: ' . BASE_URL . '/index.php?action=login');
            exit;
        } finally {
            error_log("=== LoginController::authenticate() END ===");
        }
    }
}
