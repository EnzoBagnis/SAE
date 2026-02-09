<?php

namespace Domain\Authentication\Entity;

/**
 * User Entity - Core domain entity representing an authenticated user
 *
 * This entity represents the core business concept of a user in the system.
 * It contains only business logic and is independent of any framework or infrastructure.
 */
class User
{
    private ?int $id;
    private string $lastName;
    private string $firstName;
    private string $email;
    private string $passwordHash;
    private ?string $verificationCode;
    private bool $isVerified;
    private ?\DateTimeImmutable $createdAt;
    private ?string $resetToken;
    private ?\DateTimeImmutable $resetTokenExpiration;

    /**
     * Constructor for User entity
     *
     * @param int|null $id User's unique identifier
     * @param string $lastName User's last name
     * @param string $firstName User's first name
     * @param string $email User's email address
     * @param string $passwordHash Hashed password
     * @param string|null $verificationCode Email verification code
     * @param bool $isVerified Whether the email is verified
     * @param \DateTimeImmutable|null $createdAt Account creation timestamp
     * @param string|null $resetToken Password reset token
     * @param \DateTimeImmutable|null $resetTokenExpiration Reset token expiration
     */
    public function __construct(
        ?int $id,
        string $lastName,
        string $firstName,
        string $email,
        string $passwordHash,
        ?string $verificationCode = null,
        bool $isVerified = false,
        ?\DateTimeImmutable $createdAt = null,
        ?string $resetToken = null,
        ?\DateTimeImmutable $resetTokenExpiration = null
    ) {
        $this->id = $id;
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->verificationCode = $verificationCode;
        $this->isVerified = $isVerified;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->resetToken = $resetToken;
        $this->resetTokenExpiration = $resetTokenExpiration;
    }

    /**
     * Verify user's password
     *
     * @param string $plainPassword Plain text password to verify
     * @return bool True if password matches, false otherwise
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
     * @return bool True if token is valid, false otherwise
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
     * @return bool True if code matches, false otherwise
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

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getVerificationCode(): ?string
    {
        return $this->verificationCode;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function getResetTokenExpiration(): ?\DateTimeImmutable
    {
        return $this->resetTokenExpiration;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}
