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
    /** @var string|null Comma-separated list of teacher mails this resource is shared with */
    private ?string $sharedMails = null;

    /** @var string|null First name of the resource owner (joined from teachers table) */
    private ?string $ownerFirstname = null;

    /** @var string|null Last name of the resource owner (joined from teachers table) */
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

    /**
     * Get the comma-separated list of teacher mails this resource is shared with.
     *
     * @return string|null Comma-separated mails or null
     */
    public function getSharedMails(): ?string
    {
        return $this->sharedMails;
    }

    /**
     * Set the comma-separated list of shared teacher mails.
     *
     * @param string|null $sharedMails Comma-separated mails
     * @return void
     */
    public function setSharedMails(?string $sharedMails): void
    {
        $this->sharedMails = $sharedMails;
    }

    /**
     * Get the first name of the resource owner.
     * Populated via JOIN with the teachers table.
     *
     * @return string|null Owner first name or null
     */
    public function getOwnerFirstname(): ?string
    {
        return $this->ownerFirstname;
    }

    /**
     * Set the first name of the resource owner.
     *
     * @param string|null $ownerFirstname Owner first name
     * @return void
     */
    public function setOwnerFirstname(?string $ownerFirstname): void
    {
        $this->ownerFirstname = $ownerFirstname;
    }

    /**
     * Get the last name of the resource owner.
     * Populated via JOIN with the teachers table.
     *
     * @return string|null Owner last name or null
     */
    public function getOwnerLastname(): ?string
    {
        return $this->ownerLastname;
    }

    /**
     * Set the last name of the resource owner.
     *
     * @param string|null $ownerLastname Owner last name
     * @return void
     */
    public function setOwnerLastname(?string $ownerLastname): void
    {
        $this->ownerLastname = $ownerLastname;
    }

    /**
     * Get the full name of the resource owner.
     * Combines first name (name) and last name (surname) from the teachers table.
     *
     * @return string Owner full name, or empty string if not available
     */
    public function getOwnerFullName(): string
    {
        return trim(($this->ownerFirstname ?? '') . ' ' . ($this->ownerLastname ?? ''));
    }
}
