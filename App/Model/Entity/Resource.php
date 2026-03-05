<?php

namespace App\Model\Entity;

/**
 * Resource Entity
 * Represents a pedagogical resource in the system.
 * Maps to the `resources` table.
 *
 * Schema: resource_id, owner_user_id, resource_name, description, image_path, date_creation
 */
class Resource
{
    private ?int $resourceId = null;
    private ?int $ownerUserId = null;
    private string $resourceName = '';
    private ?string $description = null;
    private ?string $imagePath = null;
    private string $accessType = 'owner';
    /** @var string|null Comma-separated list of shared user IDs */
    private ?string $sharedUserIds = null;

    /** @var string|null First name of the resource owner (joined from utilisateurs table) */
    private ?string $ownerFirstname = null;

    /** @var string|null Last name of the resource owner (joined from utilisateurs table) */
    private ?string $ownerLastname = null;

    // Getters and Setters

    public function getResourceId(): ?int
    {
        return $this->resourceId;
    }

    public function setResourceId(?int $resourceId): void
    {
        $this->resourceId = $resourceId;
    }

    public function getOwnerUserId(): ?int
    {
        return $this->ownerUserId;
    }

    public function setOwnerUserId(?int $ownerUserId): void
    {
        $this->ownerUserId = $ownerUserId;
    }

    /**
     * @deprecated Use getOwnerUserId() instead
     */
    public function getOwnerMail(): string
    {
        return (string) $this->ownerUserId;
    }

    /**
     * @deprecated Use setOwnerUserId() instead
     */
    public function setOwnerMail(string $ownerMail): void
    {
        // no-op for backward compat
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

    public function getSharedUserIds(): ?string
    {
        return $this->sharedUserIds;
    }

    public function setSharedUserIds(?string $sharedUserIds): void
    {
        $this->sharedUserIds = $sharedUserIds;
    }

    /**
     * @deprecated Use getSharedUserIds()
     */
    public function getSharedMails(): ?string
    {
        return $this->sharedUserIds;
    }

    /**
     * @deprecated Use setSharedUserIds()
     */
    public function setSharedMails(?string $sharedMails): void
    {
        $this->sharedUserIds = $sharedMails;
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

    public function getOwnerFullName(): string
    {
        return trim(($this->ownerFirstname ?? '') . ' ' . ($this->ownerLastname ?? ''));
    }
}
