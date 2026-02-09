<?php

namespace Domain\ResourceManagement\Entity;

/**
 * Resource Entity
 * Represents a pedagogical resource in the system
 */
class Resource
{
    private int $resourceId;
    private int $ownerUserId;
    private string $resourceName;
    private ?string $description;
    private ?string $imagePath;
    private string $dateCreation;
    private string $ownerFirstname;
    private string $ownerLastname;
    private string $accessType;

    /**
     * Constructor
     *
     * @param int $resourceId Resource database ID
     * @param int $ownerUserId Owner user ID
     * @param string $resourceName Resource name
     * @param string|null $description Description
     * @param string|null $imagePath Image path
     * @param string $dateCreation Creation date
     * @param string $ownerFirstname Owner first name
     * @param string $ownerLastname Owner last name
     * @param string $accessType Access type ('owner' or 'shared')
     */
    public function __construct(
        int $resourceId,
        int $ownerUserId,
        string $resourceName,
        ?string $description,
        ?string $imagePath,
        string $dateCreation,
        string $ownerFirstname,
        string $ownerLastname,
        string $accessType = 'owner'
    ) {
        $this->resourceId = $resourceId;
        $this->ownerUserId = $ownerUserId;
        $this->resourceName = $resourceName;
        $this->description = $description;
        $this->imagePath = $imagePath;
        $this->dateCreation = $dateCreation;
        $this->ownerFirstname = $ownerFirstname;
        $this->ownerLastname = $ownerLastname;
        $this->accessType = $accessType;
    }

    public function getResourceId(): int
    {
        return $this->resourceId;
    }

    public function getOwnerUserId(): int
    {
        return $this->ownerUserId;
    }

    public function getResourceName(): string
    {
        return $this->resourceName;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function getDateCreation(): string
    {
        return $this->dateCreation;
    }

    public function getOwnerFirstname(): string
    {
        return $this->ownerFirstname;
    }

    public function getOwnerLastname(): string
    {
        return $this->ownerLastname;
    }

    public function getAccessType(): string
    {
        return $this->accessType;
    }

    /**
     * Check if user is owner
     *
     * @return bool True if access type is 'owner'
     */
    public function isOwner(): bool
    {
        return $this->accessType === 'owner';
    }

    /**
     * Get owner full name
     *
     * @return string Full name
     */
    public function getOwnerFullName(): string
    {
        return $this->ownerFirstname . ' ' . $this->ownerLastname;
    }

    /**
     * Convert to array
     *
     * @return array Resource data as array
     */
    public function toArray(): array
    {
        return [
            'resource_id' => $this->resourceId,
            'owner_user_id' => $this->ownerUserId,
            'resource_name' => $this->resourceName,
            'description' => $this->description,
            'image_path' => $this->imagePath,
            'date_creation' => $this->dateCreation,
            'owner_firstname' => $this->ownerFirstname,
            'owner_lastname' => $this->ownerLastname,
            'access_type' => $this->accessType
        ];
    }
}
