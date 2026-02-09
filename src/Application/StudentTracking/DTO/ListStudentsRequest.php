<?php

namespace Application\StudentTracking\DTO;

/**
 * List Students Request DTO
 */
class ListStudentsRequest
{
    private int $page;
    private int $perPage;
    private ?int $resourceId;

    /**
     * Constructor
     *
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param int|null $resourceId Optional resource ID filter
     */
    public function __construct(int $page = 1, int $perPage = 15, ?int $resourceId = null)
    {
        $this->page = max(1, $page);
        $this->perPage = min(max(1, $perPage), 10000);
        $this->resourceId = $resourceId;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getResourceId(): ?int
    {
        return $this->resourceId;
    }
}
