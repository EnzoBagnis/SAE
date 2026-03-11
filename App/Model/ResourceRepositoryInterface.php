<?php

namespace App\Model;

use App\Model\Entity\Resource;

/**
 * Resource Repository Interface
 *
 * Defines the contract for resource persistence operations consumed by ResourcesController.
 * Follows the Interface Segregation Principle: only exposes methods that
 * the controller actually uses, not the full repository API.
 */
interface ResourceRepositoryInterface
{
    /**
     * Find all resources owned by the given teacher email.
     *
     * @param string $email Owner email address
     * @return Resource[] Array of Resource entities
     */
    public function findByOwnerMail(string $email): array;

    /**
     * Find all resources shared with the given teacher email.
     *
     * @param string $email Teacher email address
     * @return Resource[] Array of Resource entities
     */
    public function findSharedWithMail(string $email): array;

    /**
     * Get all teachers except the given one (used for sharing UI).
     *
     * @param string $excludeEmail Email to exclude (current user)
     * @return array<array{mail: string, name: string, surname: string}> List of teachers
     */
    public function findAllTeachersExcept(string $excludeEmail): array;

    /**
     * Find a resource by its ID.
     *
     * @param int $resourceId Resource ID
     * @return Resource|null Resource entity or null if not found
     */
    public function findById(int $resourceId): ?Resource;

    /**
     * Save a resource (insert or update).
     *
     * @param Resource $resource Resource entity to save
     * @return Resource The saved resource (with ID set if inserted)
     */
    public function save(Resource $resource): Resource;

    /**
     * Delete a resource and all its related data.
     *
     * @param int $resourceId Resource ID
     * @return bool True if deleted
     */
    public function delete($resourceId): bool;

    /**
     * Synchronize the sharing list for a resource.
     * Replaces all existing access entries with the given teacher emails.
     *
     * @param int      $resourceId   Resource ID
     * @param string[] $teacherMails List of teacher emails to share with
     * @return void
     */
    public function syncSharing(int $resourceId, array $teacherMails): void;
}
