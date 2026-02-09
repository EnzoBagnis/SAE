<?php

namespace Application\Authentication\DTO;

/**
 * LoginRequest DTO - Encapsulates login request data
 */
class LoginRequest
{
    public string $email;
    public string $password;

    /**
     * Constructor
     *
     * @param string $email User's email
     * @param string $password User's password
     */
    public function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->password = $password;
    }
}
