<?php

namespace Domain\ResourceManagement\Repository;

use Domain\ResourceManagement\Entity\Resource;

/**
 * Resource Repository Interface
 * Defines contract for resource data persistence
 */
interface ResourceRepositoryInterface
{
    /**
     * Find all resources accessible by a user
     *
     * @param int $userId User ID
     * @return Resource[] Array of resources
     */
    public function findAllAccessibleByUser(int $userId): array;

    /**
     * Find resource by ID
     *
     * @param int $resourceId Resource ID
     * @param int $userId Current user ID (for access type)
     * @return Resource|null Resource or null if not found
     */
    public function findById(int $resourceId, int $userId): ?Resource;

    /**
     * Check if user has access to resource
     *
     * @param int $resourceId Resource ID
     * @param int $userId User ID
     * @return bool True if user has access
     */
    public function userHasAccess(int $resourceId, int $userId): bool;

    /**
     * Get shared user IDs for a resource
     *
     * @param int $resourceId Resource ID
     * @return int[] Array of user IDs
     */
    public function getSharedUserIds(int $resourceId): array;

    /**
     * Create a new resource
     *
     * @param string $name Resource name
     * @param int $userId Owner user ID
     * @param string|null $description Resource description
     * @param string|null $imagePath Image path
     * @return int Created resource ID
     */
    public function create(string $name, int $userId, ?string $description = null, ?string $imagePath = null): int;

    /**
     * Update an existing resource
     *
     * @param int $resourceId Resource ID
     * @param string $name Resource name
     * @param string|null $description Resource description
     * @param string|null $imagePath Image path
     * @return bool True if updated successfully
     */
    public function update(int $resourceId, string $name, ?string $description = null, ?string $imagePath = null): bool;

    /**
     * Delete a resource
     *
     * @param int $resourceId Resource ID
     * @return bool True if deleted successfully
     */
    public function delete(int $resourceId): bool;

    /**
     * Update shared users for a resource
     *
     * @param int $resourceId Resource ID
     * @param int[] $userIds Array of user IDs to share with
     * @return bool True if updated successfully
     */
    public function updateSharedUsers(int $resourceId, array $userIds): bool;
}
