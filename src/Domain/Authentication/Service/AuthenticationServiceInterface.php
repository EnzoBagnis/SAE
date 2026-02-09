<?php

namespace Domain\Authentication\Service;

use Domain\Authentication\Entity\User;

/**
 * AuthenticationServiceInterface - Contract for authentication operations
 *
 * This interface defines authentication-related operations like session management.
 */
interface AuthenticationServiceInterface
{
    /**
     * Create an authenticated session for a user
     *
     * @param User $user User to authenticate
     * @return void
     */
    public function createSession(User $user): void;

    /**
     * Destroy the current authenticated session
     *
     * @return void
     */
    public function destroySession(): void;

    /**
     * Get the currently authenticated user
     *
     * @return User|null Current user or null if not authenticated
     */
    public function getCurrentUser(): ?User;

    /**
     * Check if a user is currently authenticated
     *
     * @return bool True if authenticated, false otherwise
     */
    public function isAuthenticated(): bool;
}
