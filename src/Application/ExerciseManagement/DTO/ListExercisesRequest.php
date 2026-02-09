<?php

namespace Application\ExerciseManagement\DTO;

/**
 * List Exercises Request DTO
 */
class ListExercisesRequest
{
    private ?int $resourceId;

    /**
     * Constructor
     *
     * @param int|null $resourceId Optional resource ID filter
     */
    public function __construct(?int $resourceId = null)
    {
        $this->resourceId = $resourceId;
    }

    public function getResourceId(): ?int
    {
        return $this->resourceId;
    }
}
