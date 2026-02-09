<?php

namespace Application\Authentication\DTO;

/**
 * Password Reset Request DTO
 */
class PasswordResetRequest
{
    public string $email;

    /**
     * Constructor
     *
     * @param string $email User email
     */
    public function __construct(string $email)
    {
        $this->email = $email;
    }
}
