<?php

namespace Application\Authentication\DTO;

use Domain\Authentication\Entity\User;

/**
 * LoginResponse DTO - Encapsulates login response data
 */
class LoginResponse
{
    public bool $success;
    public string $message;
    public ?User $user;

    /**
     * Constructor
     *
     * @param bool $success Whether login was successful
     * @param string $message Response message
     * @param User|null $user Authenticated user (if successful)
     */
    public function __construct(bool $success, string $message, ?User $user)
    {
        $this->success = $success;
        $this->message = $message;
        $this->user = $user;
    }
}
