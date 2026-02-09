<?php

namespace Domain\Authentication\Service;

/**
 * EmailServiceInterface - Contract for email operations
 *
 * This interface defines email-related operations for authentication.
 */
interface EmailServiceInterface
{
    /**
     * Send verification code to user's email
     *
     * @param string $email Recipient email address
     * @param string $firstName Recipient first name
     * @param string $verificationCode Verification code
     * @return bool True if email sent successfully, false otherwise
     */
    public function sendVerificationCode(
        string $email,
        string $firstName,
        string $verificationCode
    ): bool;

    /**
     * Send password reset email
     *
     * @param string $email Recipient email address
     * @param string $firstName Recipient first name
     * @param string $resetToken Password reset token
     * @return bool True if email sent successfully, false otherwise
     */
    public function sendPasswordResetEmail(
        string $email,
        string $firstName,
        string $resetToken
    ): bool;
}
