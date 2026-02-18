<?php

namespace App\Model\Entity;

/**
 * PendingRegistration Entity
 * Represents a user registration awaiting approval
 */
class PendingRegistration
{
    private ?int $id = null;
    private string $lastName;
    private string $firstName;
    private string $email;
    private string $passwordHash;
    private ?string $verificationCode = null;
    private bool $isVerified = false;
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Verify email with verification code
     *
     * @param string $code Verification code to check
     * @return bool True if code matches
     */
    public function verify(string $code): bool
    {
        if ($this->verificationCode === $code) {
            $this->isVerified = true;
            return true;
        }
        return false;
    }

    /**
     * Generate new verification code
     *
     * @return string New verification code
     */
    public function generateVerificationCode(): string
    {
        $this->verificationCode = sprintf('%06d', random_int(0, 999999));
        return $this->verificationCode;
    }

    /**
     * Convert to User entity
     *
     * @return User Active user entity
     */
    public function toUser(): User
    {
        $user = new User();
        $user->setLastName($this->lastName);
        $user->setFirstName($this->firstName);
        $user->setEmail($this->email);
        $user->setPasswordHash($this->passwordHash);
        $user->setIsVerified(true);
        $user->setCreatedAt($this->createdAt ?? new \DateTimeImmutable());
        return $user;
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function getVerificationCode(): ?string
    {
        return $this->verificationCode;
    }

    public function setVerificationCode(?string $verificationCode): void
    {
        $this->verificationCode = $verificationCode;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): void
    {
        $this->isVerified = $isVerified;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get full name
     *
     * @return string Full name
     */
    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}

