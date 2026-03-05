<?php

namespace App\Model\UseCase\Ports;

use App\Model\Entity\Exercise;

/**
 * Port interface for the ImportAttemptsUseCase (exercise lookup).
 *
 * Extends {@see ExerciseFinderPort} for resource-scoped lookup and adds
 * a global name-based lookup fallback.
 */
interface ExerciseLookupPort extends ExerciseFinderPort
{
    /**
     * Find an exercise by name globally (returns the most recent match).
     *
     * Required as a fallback when no resource ID is provided in the import payload.
     *
     * @param string $name Exercise name.
     * @return Exercise|null The matching Exercise entity, or null if not found.
     */
    public function findByName(string $name): ?Exercise;
}
