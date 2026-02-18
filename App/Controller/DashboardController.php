<?php

namespace App\Controller;

use Core\Controller\AbstractController;
use App\Model\AuthenticationService;
use Core\Service\SessionService;

/**
 * Dashboard Controller
 * Handles user dashboard
 */
class DashboardController extends AbstractController
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
     * Show dashboard
     *
     * @return void
     */
    public function index(): void
    {
        // Require authentication
        $this->authService->requireAuth('/auth/login');

        $this->renderView('user/dashboard', [
            'user_id' => $this->authService->getUserId(),
            'user_email' => $this->authService->getUserEmail(),
        ]);
    }
}

