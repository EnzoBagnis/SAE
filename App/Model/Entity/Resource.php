<?php

namespace App\Model\Entity;

/**
 * Resource Entity
 * Represents a pedagogical resource in the system.
 * Maps to the `ressources` table.
 */
class Resource
{
    private ?int $resourceId = null;
    private string $ownerMail = '';
    private string $resourceName = '';
    private ?string $description = null;
    private ?string $imagePath = null;
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

    public function getOwnerMail(): string
    {
        return $this->ownerMail;
    }

    public function setOwnerMail(string $ownerMail): void
    {
        $this->ownerMail = $ownerMail;
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

    public function getAccessType(): string
    {
        return $this->accessType;
    }

    public function setAccessType(string $accessType): void
    {
        $this->accessType = $accessType;
    }
}
