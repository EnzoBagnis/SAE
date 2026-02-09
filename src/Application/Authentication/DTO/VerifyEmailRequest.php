<?php

namespace Application\Authentication\DTO;

/**
 * VerifyEmailRequest DTO - Encapsulates email verification request data
 */
class VerifyEmailRequest
{
    public string $email;
    public string $verificationCode;

    /**
     * Constructor
     *
     * @param string $email User's email
     * @param string $verificationCode Verification code
     */
    public function __construct(string $email, string $verificationCode)
    {
        $this->email = $email;
        $this->verificationCode = $verificationCode;
    }
}
