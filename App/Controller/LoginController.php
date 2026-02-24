<?php

namespace App\Controller;

use Core\Controller\AbstractController;
use App\Model\UseCase\LoginUserUseCase;
use App\Model\UserRepository;
use App\Model\AuthenticationService;
use Core\Service\SessionService;

/**
 * Login Controller
 * Handles user login
 */
class LoginController extends AbstractController
{
    private LoginUserUseCase $loginUseCase;

    /**
     * Constructor
     */
    public function __construct()
    {
        $userRepository = new UserRepository();
        $authService = new AuthenticationService(new SessionService());
        $this->loginUseCase = new LoginUserUseCase($userRepository, $authService);
    }

    /**
     * Show login form
     *
     * @return void
     */
    public function index(): void
    {
        $this->renderView('auth/login');
    }

    /**
     * Process login
     *
     * @return void
     */
    public function login(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/auth/login');
            return;
        }

        $result = $this->loginUseCase->execute([
            'email' => $this->getPost('email'),
            'password' => $this->getPost('password'),
        ]);

        if ($result['success']) {
            $this->redirect('/resources');
        } else {
            $this->renderView('auth/login', [
                'error' => $result['message'],
                'email' => $this->getPost('email'),
            ]);
        }
    }
}

