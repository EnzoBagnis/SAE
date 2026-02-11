<?php

namespace Application\ResourceManagement\DTO;

/**
 * UpdateResourceRequest DTO
 *
 * Data Transfer Object for resource update requests
 */
class UpdateResourceRequest
{
    public int $resourceId;
    public string $name;
    public int $userId;
    public ?string $description;
    public ?string $imagePath;
    public array $sharedUserIds;

    /**
     * Constructor
     *
     * @param int $resourceId Resource ID
     * @param string $name Resource name
     * @param int $userId Owner user ID
     * @param string|null $description Resource description
     * @param string|null $imagePath Image path
     * @param array $sharedUserIds Array of user IDs to share with
     */
    public function __construct(
        int $resourceId,
        string $name,
        int $userId,
        ?string $description = null,
        ?string $imagePath = null,
        array $sharedUserIds = []
    ) {
        $this->resourceId = $resourceId;
        $this->name = $name;
        $this->userId = $userId;
        $this->description = $description;
        $this->imagePath = $imagePath;
        $this->sharedUserIds = $sharedUserIds;
    }
}

