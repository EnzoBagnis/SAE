<?php

namespace Domain\Authentication\Repository;

use Domain\Authentication\Entity\PendingRegistration;

/**
 * PendingRegistrationRepositoryInterface - Contract for pending registration persistence
 *
 * This interface defines the contract for managing pending user registrations
 * awaiting verification or approval.
 */
interface PendingRegistrationRepositoryInterface
{
    /**
     * Find a pending registration by ID
     *
     * @param int $id Registration's ID
     * @return PendingRegistration|null Pending registration or null if not found
     */
    public function findById(int $id): ?PendingRegistration;

    /**
     * Find a pending registration by email address
     *
     * @param string $email User's email
     * @return PendingRegistration|null Pending registration or null if not found
     */
    public function findByEmail(string $email): ?PendingRegistration;

    /**
     * Check if an email already exists in pending registrations
     *
     * @param string $email Email to check
     * @return bool True if email exists, false otherwise
     */
    public function emailExists(string $email): bool;

    /**
     * Save a new pending registration or update an existing one
     *
     * @param PendingRegistration $registration Pending registration to save
     * @return PendingRegistration Saved registration with ID
     */
    public function save(PendingRegistration $registration): PendingRegistration;

    /**
     * Delete a pending registration
     *
     * @param int $id Registration's ID
     * @return bool True if deleted successfully, false otherwise
     */
    public function delete(int $id): bool;

    /**
     * Get all pending registrations (for admin purposes)
     *
     * @return PendingRegistration[] Array of pending registrations
     */
    public function findAll(): array;
}
