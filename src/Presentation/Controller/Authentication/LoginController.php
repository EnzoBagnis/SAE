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
        // Debug: Log that we're in authenticate
        error_log("LoginController::authenticate() called");
        error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST data: " . print_r($_POST, true));

        $email = $_POST['mail'] ?? '';
        $password = $_POST['mdp'] ?? '';

        error_log("Email: $email, Password length: " . strlen($password));

        $request = new LoginRequest($email, $password);
        $response = $this->loginUseCase->execute($request);

        error_log("Response success: " . ($response->success ? 'true' : 'false'));
        error_log("Response message: " . $response->message);

        if ($response->success) {
            header('Location: ' . BASE_URL . '/index.php?action=dashboard');
            exit;
        } else {
            $_SESSION['error'] = $response->message;
            header('Location: ' . BASE_URL . '/index.php?action=login');
            exit;
        }
    }
}
