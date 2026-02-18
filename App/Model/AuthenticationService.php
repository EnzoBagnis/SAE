<?php

namespace App\Model;

use App\Model\Entity\User;
use Core\Service\SessionService;

/**
 * Authentication Service
 * Handles user authentication and session management
 */
class AuthenticationService
{
    private SessionService $sessionService;

    /**
     * Constructor
     *
     * @param SessionService $sessionService Session service
     */
    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Create authenticated session for user
     *
     * @param User $user User entity
     * @return void
     */
    public function createSession(User $user): void
    {
        $this->sessionService->regenerate();

        $this->sessionService->set('user_id', $user->getId());
        $this->sessionService->set('user_email', $user->getEmail());
        $this->sessionService->set('user_firstname', $user->getFirstName());
        $this->sessionService->set('user_lastname', $user->getLastName());
        $this->sessionService->set('is_authenticated', true);
    }

    /**
     * Destroy authenticated session
     *
     * @return void
     */
    public function destroySession(): void
    {
        $this->sessionService->destroy();
    }

    /**
     * Check if user is authenticated
     *
     * @return bool True if authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->sessionService->get('is_authenticated', false) === true;
    }

    /**
     * Get authenticated user ID
     *
     * @return int|null User ID or null
     */
    public function getUserId(): ?int
    {
        return $this->sessionService->get('user_id');
    }

    /**
     * Get authenticated user email
     *
     * @return string|null User email or null
     */
    public function getUserEmail(): ?string
    {
        return $this->sessionService->get('user_email');
    }

    /**
     * Require authentication (redirect if not authenticated)
     *
     * @param string $redirectUrl Redirect URL if not authenticated
     * @return void
     */
    public function requireAuth(string $redirectUrl = '/auth/login'): void
    {
        if (!$this->isAuthenticated()) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
}

