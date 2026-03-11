<?php

namespace App\Model;

use App\Model\Entity\User;
use Core\Service\SessionServiceInterface;

/**
 * Authentication Service
 * Handles user authentication and session management.
 * Implements AuthenticationServiceInterface for dependency inversion.
 */
class AuthenticationService implements AuthenticationServiceInterface
{
    private SessionServiceInterface $sessionService;

    /**
     * Constructor
     *
     * @param SessionServiceInterface $sessionService Session service
     */
    public function __construct(SessionServiceInterface $sessionService)
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
     * Get authenticated user's first name
     *
     * @return string|null First name or null if not authenticated
     */
    public function getUserFirstName(): ?string
    {
        return $this->sessionService->get('user_firstname');
    }

    /**
     * Get authenticated user's last name
     *
     * @return string|null Last name or null if not authenticated
     */
    public function getUserLastName(): ?string
    {
        return $this->sessionService->get('user_lastname');
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
