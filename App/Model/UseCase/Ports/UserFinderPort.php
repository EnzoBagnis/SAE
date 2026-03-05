<?php

namespace App\Model\UseCase\Ports;

use App\Model\Entity\User;

/**
 * Base port interface for user lookup operations.
 *
 * Provides the common read operation to find a user by email address.
 * This interface is segregated to allow composition by more specific ports.
 */
interface UserFinderPort
{
    /**
     * Find a user by email address.
     *
     * @param string $email The email address to search for.
     * @return User|null The matching User entity, or null if not found.
     */
    public function findByEmail(string $email): ?User;
}
