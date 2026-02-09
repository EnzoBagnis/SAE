<?php

namespace Application\Authentication\DTO;

/**
 * RegisterRequest DTO - Encapsulates registration request data
 */
class RegisterRequest
{
    public string $lastName;
    public string $firstName;
    public string $email;
    public string $password;

    /**
     * Constructor
     *
     * @param string $lastName User's last name
     * @param string $firstName User's first name
     * @param string $email User's email
     * @param string $password User's password
     */
    public function __construct(
        string $lastName,
        string $firstName,
        string $email,
        string $password
    ) {
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        $this->email = $email;
        $this->password = $password;
    }
}
