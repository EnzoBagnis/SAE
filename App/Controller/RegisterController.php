<?php

namespace App\Controller;

use Core\Controller\AbstractController;
use App\Model\UseCase\RegisterUserUseCase;
use App\Model\UserRepository;
use App\Model\EmailService;

/**
 * Register Controller
 * Handles user registration
 */
class RegisterController extends AbstractController
{
    private RegisterUserUseCase $registerUseCase;

    /**
     * Constructor
     */
    public function __construct()
    {
        $userRepository = new UserRepository();
        $emailService = new EmailService();

        $this->registerUseCase = new RegisterUserUseCase(
            $userRepository,
            $emailService
        );
    }

    /**
     * Show registration form
     *
     * @return void
     */
    public function index(): void
    {
        $this->renderView('auth/register');
    }

    /**
     * Process registration
     *
     * @return void
     */
    public function register(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/auth/register');
            return;
        }

        $result = $this->registerUseCase->execute([
            'email'      => $this->getPost('email'),
            'password'   => $this->getPost('password'),
            'first_name' => $this->getPost('first_name'),
            'last_name'  => $this->getPost('last_name'),
        ]);

        if ($result['success']) {
            // Store email in session so the verify-email page can use it
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['pending_verification_email'] = $this->getPost('email');
            $this->redirect(BASE_URL . '/auth/verify-email');
            return;
        }

        $this->renderView('auth/register', [
            'error'      => $result['message'],
            'email'      => $this->getPost('email'),
            'first_name' => $this->getPost('first_name'),
            'last_name'  => $this->getPost('last_name'),
        ]);
    }
}
