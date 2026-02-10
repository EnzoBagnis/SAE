<?php

namespace Presentation\Controller\Authentication;

use Application\Authentication\UseCase\RegisterUser;
use Application\Authentication\DTO\RegisterRequest;

/**
 * RegisterController - Handles user registration requests
 *
 * This controller is thin and delegates business logic to use cases.
 */
class RegisterController
{
    private RegisterUser $registerUseCase;

    /**
     * Constructor
     *
     * @param RegisterUser $registerUseCase Register use case
     */
    public function __construct(RegisterUser $registerUseCase)
    {
        $this->registerUseCase = $registerUseCase;
    }

    /**
     * Display registration form
     *
     * @return void
     */
    public function index(): void
    {
        require __DIR__ . '/../../Views/auth/register.php';
    }

    /**
     * Process registration
     *
     * @return void
     */
    public function register(): void
    {
        $lastName = $_POST['nom'] ?? '';
        $firstName = $_POST['prenom'] ?? '';
        $email = $_POST['mail'] ?? '';
        $password = $_POST['mdp'] ?? '';

        $request = new RegisterRequest($lastName, $firstName, $email, $password);
        $response = $this->registerUseCase->execute($request);

        if ($response->success) {
            $_SESSION['success'] = $response->message;
            $_SESSION['pending_verification_email'] = $email; // Store email for verification
            header('Location: ' . BASE_URL . '/index.php?action=emailverification');
            exit;
        } else {
            $_SESSION['error'] = $response->message;
            header('Location: ' . BASE_URL . '/index.php?action=signup');
            exit;
        }
    }
}
