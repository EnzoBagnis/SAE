<?php

namespace Application\Authentication\DTO;

/**
 * Password Reset Response DTO
 */
class PasswordResetResponse
{
    public bool $success;
    public string $message;

    /**
     * Constructor
     *
     * @param bool $success Whether the operation was successful
     * @param string $message Response message
     */
    public function __construct(bool $success, string $message)
    {
        $this->success = $success;
        $this->message = $message;
    }
}
