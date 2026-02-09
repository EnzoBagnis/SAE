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
        $email = $_POST['mail'] ?? '';
        $password = $_POST['mdp'] ?? '';

        $request = new LoginRequest($email, $password);
        $response = $this->loginUseCase->execute($request);

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
