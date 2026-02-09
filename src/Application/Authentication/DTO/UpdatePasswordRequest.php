<?php

namespace Application\Authentication\DTO;

/**
 * Update Password Request DTO
 * Contains data required to update a user's password
 */
class UpdatePasswordRequest
{
    public string $token;
    public string $newPassword;

    /**
     * Constructor
     *
     * @param string $token Password reset token
     * @param string $newPassword New password
     */
    public function __construct(string $token, string $newPassword)
    {
        $this->token = $token;
        $this->newPassword = $newPassword;
    }
}
