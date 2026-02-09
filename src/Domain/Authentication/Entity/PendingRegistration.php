<?php

namespace Domain\Authentication\Entity;

/**
 * PendingRegistration Entity - Represents a user registration awaiting approval
 *
 * This entity represents users who have registered but are awaiting
 * email verification or admin approval before becoming active users.
 */
class PendingRegistration
{
    private ?int $id;
    private string $lastName;
    private string $firstName;
    private string $email;
    private string $passwordHash;
    private ?string $verificationCode;
    private bool $isVerified;
    private \DateTimeImmutable $createdAt;

    /**
     * Constructor for PendingRegistration entity
     *
     * @param int|null $id Registration's unique identifier
     * @param string $lastName User's last name
     * @param string $firstName User's first name
     * @param string $email User's email address
     * @param string $passwordHash Hashed password
     * @param string|null $verificationCode Email verification code
     * @param bool $isVerified Whether email is verified
     * @param \DateTimeImmutable|null $createdAt Registration timestamp
     */
    public function __construct(
        ?int $id,
        string $lastName,
        string $firstName,
        string $email,
        string $passwordHash,
        ?string $verificationCode = null,
        bool $isVerified = false,
        ?\DateTimeImmutable $createdAt = null
    ) {
        $this->id = $id;
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->verificationCode = $verificationCode;
        $this->isVerified = $isVerified;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    /**
     * Verify email with verification code
     *
     * @param string $code Verification code to check
     * @return bool True if code matches, false otherwise
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
        return new User(
            null,
            $this->lastName,
            $this->firstName,
            $this->email,
            $this->passwordHash,
            null,
            true,
            $this->createdAt
        );
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}
