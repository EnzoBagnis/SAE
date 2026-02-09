<?php

namespace Application\Authentication\DTO;

/**
 * VerifyEmailResponse DTO - Encapsulates email verification response data
 */
class VerifyEmailResponse
{
    public bool $success;
    public string $message;

    /**
     * Constructor
     *
     * @param bool $success Whether verification was successful
     * @param string $message Response message
     */
    public function __construct(bool $success, string $message)
    {
        $this->success = $success;
        $this->message = $message;
    }
}
