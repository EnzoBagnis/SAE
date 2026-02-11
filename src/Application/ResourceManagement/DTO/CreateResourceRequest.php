<?php

namespace Application\ResourceManagement\DTO;

/**
 * CreateResourceRequest DTO
 *
 * Data Transfer Object for resource creation requests
 */
class CreateResourceRequest
{
    public string $name;
    public int $userId;
    public ?string $description;
    public ?string $imagePath;
    public array $sharedUserIds;

    /**
     * Constructor
     *
     * @param string $name Resource name
     * @param int $userId Owner user ID
     * @param string|null $description Resource description
     * @param string|null $imagePath Image path
     * @param array $sharedUserIds Array of user IDs to share with
     */
    public function __construct(
        string $name,
        int $userId,
        ?string $description = null,
        ?string $imagePath = null,
        array $sharedUserIds = []
    ) {
        $this->name = $name;
        $this->userId = $userId;
        $this->description = $description;
        $this->imagePath = $imagePath;
        $this->sharedUserIds = $sharedUserIds;
    }
}

