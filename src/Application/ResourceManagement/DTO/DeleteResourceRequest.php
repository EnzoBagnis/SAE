<?php

namespace Application\ResourceManagement\DTO;

/**
 * DeleteResourceRequest DTO
 *
 * Data Transfer Object for resource deletion requests
 */
class DeleteResourceRequest
{
    public int $resourceId;
    public int $userId;

    /**
     * Constructor
     *
     * @param int $resourceId Resource ID to delete
     * @param int $userId User ID requesting deletion
     */
    public function __construct(int $resourceId, int $userId)
    {
        $this->resourceId = $resourceId;
        $this->userId = $userId;
    }
}
