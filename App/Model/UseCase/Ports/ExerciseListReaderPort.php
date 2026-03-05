<?php

namespace App\Model\UseCase\Ports;

use App\Model\Entity\Exercise;

/**
 * Port interface for the ListExercisesUseCase.
 *
 * Provides the minimal read operation needed to list exercises that have
 * associated attempts, optionally filtered by resource.
 */
interface ExerciseListReaderPort
{
    /**
     * Find all exercises that have at least one attempt, optionally filtered by resource.
     *
     * Required to populate the exercise listing view with only relevant exercises.
     *
     * @param int|null $resourceId Optional resource ID filter.
     * @return Exercise[] Array of Exercise entities.
     */
    public function findAllWithAttempts(?int $resourceId = null): array;
}

