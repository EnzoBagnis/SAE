<?php

namespace Presentation\Controller\Authentication;

use Domain\Authentication\Service\AuthenticationServiceInterface;

/**
 * LogoutController - Handles user logout
 */
class LogoutController
{
    private AuthenticationServiceInterface $authService;

    /**
     * Constructor
     *
     * @param AuthenticationServiceInterface $authService Authentication service
     */
    public function __construct(AuthenticationServiceInterface $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Logout user and destroy session
     *
     * @return void
     */
    public function logout(): void
    {
        $this->authService->destroySession();
        header('Location: ' . BASE_URL . '/index.php?action=login');
        exit;
    }
}
