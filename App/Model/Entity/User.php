<?php

namespace App\Model\Entity;

/**
 * User Entity
 * Represents an authenticated user in the system
 */
class User
{
    private ?int $id = null;
    private string $lastName;
    private string $firstName;
    private string $email;
    private string $passwordHash;
    private ?string $verificationCode = null;
    private bool $isVerified = false;
    private ?\DateTimeImmutable $createdAt = null;
    private ?string $resetToken = null;
    private ?\DateTimeImmutable $resetTokenExpiration = null;

    /**
     * Verify user's password
     *
     * @param string $plainPassword Plain text password to verify
     * @return bool True if password matches
     */
    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->passwordHash);
    }

    /**
     * Change user's password
     *
     * @param string $newPassword New plain text password
     * @return void
     */
    public function changePassword(string $newPassword): void
    {
        $this->passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->clearResetToken();
    }

    /**
     * Set password reset token
     *
     * @param string $token Reset token
     * @param \DateTimeImmutable $expiration Token expiration timestamp
     * @return void
     */
    public function setResetToken(string $token, \DateTimeImmutable $expiration): void
    {
        $this->resetToken = $token;
        $this->resetTokenExpiration = $expiration;
    }

    /**
     * Clear password reset token
     *
     * @return void
     */
    public function clearResetToken(): void
    {
        $this->resetToken = null;
        $this->resetTokenExpiration = null;
    }

    /**
     * Check if reset token is valid
     *
     * @return bool True if token is valid
     */
    public function isResetTokenValid(): bool
    {
        if ($this->resetToken === null || $this->resetTokenExpiration === null) {
            return false;
        }

        return $this->resetTokenExpiration > new \DateTimeImmutable();
    }

    /**
     * Verify email with verification code
     *
     * @param string $code Verification code to check
     * @return bool True if code matches
     */
    public function verifyEmail(string $code): bool
    {
        if ($this->verificationCode === $code) {
            $this->isVerified = true;
            $this->verificationCode = null;
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
     * Get full name
     *
     * @return string Full name
     */
    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
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

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function getResetTokenExpiration(): ?\DateTimeImmutable
    {
        return $this->resetTokenExpiration;
    }
}

