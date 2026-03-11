<?php

namespace App\Model\UseCase\Ports;

use App\Model\Entity\User;

/**
 * Port interface for the RegisterUserUseCase.
 *
 * Extends {@see UserFinderPort} for email lookup and adds operations
 * needed to register a new user: email existence check and persistence.
 */
interface UserRegistrationPort extends UserFinderPort
{
    /**
     * Check whether an email address is already registered.
     *
     * Required to enforce the uniqueness constraint before creating an account.
     *
     * @param string $email The email address to check.
     * @return bool True if the email already exists.
     */
    public function emailExists(string $email): bool;

    /**
     * Persist a new User entity.
     *
     * Required to save the newly created user account.
     *
     * @param User $user The User entity to persist.
     * @return User The persisted User entity.
     */
    public function save(User $user): User;
}
