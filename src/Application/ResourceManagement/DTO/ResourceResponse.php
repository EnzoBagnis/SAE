<?php

namespace Application\ResourceManagement\DTO;

/**
 * ResourceResponse DTO
 *
 * Data Transfer Object for resource operation responses
 */
class ResourceResponse
{
    public bool $success;
    public string $message;
    public ?int $resourceId;

    /**
     * Constructor
     *
     * @param bool $success Operation success status
     * @param string $message Response message
     * @param int|null $resourceId Resource ID if applicable
     */
    public function __construct(bool $success, string $message, ?int $resourceId = null)
    {
        $this->success = $success;
        $this->message = $message;
        $this->resourceId = $resourceId;
    }
}

