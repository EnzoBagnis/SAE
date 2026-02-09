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
}
