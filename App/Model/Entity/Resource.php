<?php

namespace App\Model\Entity;

/**
 * Resource Entity
 * Represents a pedagogical resource in the system
 */
class Resource
{
    private ?int $resourceId = null;
    private int $ownerUserId;
    private string $resourceName;
    private ?string $description = null;
    private ?string $imagePath = null;
    private ?string $dateCreation = null;
    private ?string $ownerFirstname = null;
    private ?string $ownerLastname = null;
    private string $accessType = 'owner';

    // Getters and Setters

    public function getResourceId(): ?int
    {
        return $this->resourceId;
    }

    public function setResourceId(?int $resourceId): void
    {
        $this->resourceId = $resourceId;
    }

    public function getOwnerUserId(): int
    {
        return $this->ownerUserId;
    }

    public function setOwnerUserId(int $ownerUserId): void
    {
        $this->ownerUserId = $ownerUserId;
    }

    public function getResourceName(): string
    {
        return $this->resourceName;
    }

    public function setResourceName(string $resourceName): void
    {
        $this->resourceName = $resourceName;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): void
    {
        $this->imagePath = $imagePath;
    }

    public function getDateCreation(): ?string
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?string $dateCreation): void
    {
        $this->dateCreation = $dateCreation;
    }

    public function getOwnerFirstname(): ?string
    {
        return $this->ownerFirstname;
    }

    public function setOwnerFirstname(?string $ownerFirstname): void
    {
        $this->ownerFirstname = $ownerFirstname;
    }

    public function getOwnerLastname(): ?string
    {
        return $this->ownerLastname;
    }

    public function setOwnerLastname(?string $ownerLastname): void
    {
        $this->ownerLastname = $ownerLastname;
    }

    public function getAccessType(): string
    {
        return $this->accessType;
    }

    public function setAccessType(string $accessType): void
    {
        $this->accessType = $accessType;
    }

    /**
     * Get owner full name
     *
     * @return string Owner full name
     */
    public function getOwnerFullName(): string
    {
        return trim($this->ownerFirstname . ' ' . $this->ownerLastname);
    }

    /**
     * Check if user is owner
     *
     * @return bool True if access type is owner
     */
    public function isOwner(): bool
    {
        return $this->accessType === 'owner';
    }

    /**
     * Convert entity to array
     *
     * @return array Entity data as array
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
            'access_type' => $this->accessType,
        ];
    }
}

