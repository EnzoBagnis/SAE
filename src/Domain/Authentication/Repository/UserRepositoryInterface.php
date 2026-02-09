<?php

namespace Domain\Authentication\Repository;

use Domain\Authentication\Entity\User;

/**
 * UserRepositoryInterface - Contract for user data persistence
 *
 * This interface defines the contract for persisting and retrieving users.
 * It's part of the domain layer and is independent of any specific database implementation.
 */
interface UserRepositoryInterface
{
    /**
     * Find a user by their unique identifier
     *
     * @param int $id User's ID
     * @return User|null User entity or null if not found
     */
    public function findById(int $id): ?User;

    /**
     * Find a user by their email address
     *
     * @param string $email User's email
     * @return User|null User entity or null if not found
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find a user by their password reset token
     *
     * @param string $token Reset token
     * @return User|null User entity or null if not found
     */
    public function findByResetToken(string $token): ?User;

    /**
     * Check if an email already exists in the system
     *
     * @param string $email Email to check
     * @return bool True if email exists, false otherwise
     */
    public function emailExists(string $email): bool;

    /**
     * Save a new user or update an existing one
     *
     * @param User $user User entity to save
     * @return User Saved user entity with ID
     */
    public function save(User $user): User;

    /**
     * Delete a user from the system
     *
     * @param int $id User's ID
     * @return bool True if deleted successfully, false otherwise
     */
    public function delete(int $id): bool;

    /**
     * Get all users (for admin purposes)
     *
     * @return User[] Array of user entities
     */
    public function findAll(): array;
}
