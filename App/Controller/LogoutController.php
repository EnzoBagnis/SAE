<?php

namespace App\Controller;

use Core\Controller\AbstractController;
use App\Model\AuthenticationService;
use Core\Service\SessionService;

/**
 * Logout Controller
 * Handles user logout
 */
class LogoutController extends AbstractController
{
    private AuthenticationService $authService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->authService = new AuthenticationService(new SessionService());
    }

    /**
     * Process logout
     *
     * @return void
     */
    public function logout(): void
    {
        $this->authService->destroySession();
        $this->redirect('/auth/login');
    }
}
