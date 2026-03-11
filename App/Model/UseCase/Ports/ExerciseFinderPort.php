<?php

namespace App\Model\UseCase\Ports;

use App\Model\Entity\Exercise;

/**
 * Base port interface for exercise lookup operations.
 *
 * Provides the common read operation to find an exercise by resource ID and name.
 * This interface is segregated to allow composition by more specific ports.
 */
interface ExerciseFinderPort
{
    /**
     * Find an exercise by resource ID and name.
     *
     * @param int    $ressourceId Resource ID.
     * @param string $name        Exercise name.
     * @return Exercise|null The matching Exercise entity, or null if not found.
     */
    public function findByRessourceIdAndName(int $ressourceId, string $name): ?Exercise;
}
