<?php

namespace Application\Authentication\DTO;

/**
 * Update Password Response DTO
 * Contains the result of a password update operation
 */
class UpdatePasswordResponse
{
    public bool $success;
    public string $message;

    /**
     * Constructor
     *
     * @param bool $success Whether the operation succeeded
     * @param string $message Response message
     */
    public function __construct(bool $success, string $message)
    {
        $this->success = $success;
        $this->message = $message;
    }
}
