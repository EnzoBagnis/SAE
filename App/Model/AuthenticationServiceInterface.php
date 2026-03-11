<?php

namespace App\Model;

/**
 * Authentication Service Interface
 *
 * Defines the contract for authentication operations consumed by controllers.
 * Follows the Interface Segregation Principle: exposes only the methods
 * that controller-level consumers actually need.
 */
interface AuthenticationServiceInterface
{
    /**
     * Require the user to be authenticated.
     * Redirects to the given URL if not authenticated.
     *
     * @param string $redirectUrl URL to redirect to if not authenticated
     * @return void
     */
    public function requireAuth(string $redirectUrl = '/auth/login'): void;

    /**
     * Check if the current user is authenticated.
     *
     * @return bool True if authenticated
     */
    public function isAuthenticated(): bool;

    /**
     * Get the authenticated user's email address.
     *
     * @return string|null User email or null if not authenticated
     */
    public function getUserEmail(): ?string;

    /**
     * Get the authenticated user's ID.
     *
     * @return int|null User ID or null if not authenticated
     */
    public function getUserId(): ?int;

    /**
     * Get the authenticated user's first name.
     *
     * @return string|null First name or null if not authenticated
     */
    public function getUserFirstName(): ?string;

    /**
     * Get the authenticated user's last name.
     *
     * @return string|null Last name or null if not authenticated
     */
    public function getUserLastName(): ?string;
}
