<?php

namespace Application\Authentication\DTO;

use Domain\Authentication\Entity\PendingRegistration;

/**
 * RegisterResponse DTO - Encapsulates registration response data
 */
class RegisterResponse
{
    public bool $success;
    public string $message;
    public ?PendingRegistration $pendingRegistration;

    /**
     * Constructor
     *
     * @param bool $success Whether registration was successful
     * @param string $message Response message
     * @param PendingRegistration|null $pendingRegistration Created pending registration (if successful)
     */
    public function __construct(
        bool $success,
        string $message,
        ?PendingRegistration $pendingRegistration = null
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->pendingRegistration = $pendingRegistration;
    }
}
