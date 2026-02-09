<?php

namespace Application\ExerciseManagement\DTO;

use Domain\ExerciseManagement\Entity\Exercise;

/**
 * List Exercises Response DTO
 */
class ListExercisesResponse
{
    private bool $success;
    private array $exercises;
    private int $total;
    private ?string $error;

    /**
     * Constructor
     *
     * @param bool $success Success status
     * @param Exercise[] $exercises Array of exercises
     * @param int $total Total count
     * @param string|null $error Error message
     */
    public function __construct(
        bool $success,
        array $exercises = [],
        int $total = 0,
        ?string $error = null
    ) {
        $this->success = $success;
        $this->exercises = $exercises;
        $this->total = $total;
        $this->error = $error;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getExercises(): array
    {
        return $this->exercises;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Convert to array
     *
     * @return array Response as array
     */
    public function toArray(): array
    {
        if (!$this->success) {
            return [
                'success' => false,
                'message' => $this->error
            ];
        }

        $formattedExercises = array_map(function (Exercise $exercise) {
            return [
                'id' => $exercise->getExerciseId(),
                'name' => $exercise->getExoName(),
                'funcname' => $exercise->getFuncname(),
                'description' => $exercise->getDescription(),
                'difficulte' => $exercise->getDifficulte(),
                'resource_id' => $exercise->getResourceId()
            ];
        }, $this->exercises);

        return [
            'success' => true,
            'data' => [
                'exercises' => $formattedExercises,
                'total' => $this->total
            ]
        ];
    }
}
