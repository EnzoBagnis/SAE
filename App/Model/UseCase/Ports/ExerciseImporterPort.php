<?php

namespace App\Model\UseCase\Ports;

use App\Model\Entity\Exercise;

/**
 * Port interface for the ImportExercisesUseCase.
 *
 * Extends {@see ExerciseFinderPort} for lookup operations and adds
 * write operations needed to import exercises into the system.
 */
interface ExerciseImporterPort extends ExerciseFinderPort
{
    /**
     * Find an exercise by resource ID and original hash.
     *
     * @param int    $ressourceId Resource ID.
     * @param string $hash        Original hash.
     * @return Exercise|null
     */
    public function findByRessourceIdAndHash(int $ressourceId, string $hash): ?Exercise;

    /**
     * Update the exercice_name of an existing exercise (e.g. replace hash by readable name).
     *
     * @param int    $exerciceId   Exercise ID.
     * @param string $exerciceName New readable name.
     * @return void
     */
    public function updateName(int $exerciceId, string $exerciceName): void;

    /**
     * Update the extension and date of an existing exercise.
     *
     * @param int    $exerciceId Exercise ID.
     * @param string $extention  New file extension.
     * @param string $date       New date (Y-m-d).
     * @return void
     */
    public function updateExtentionAndDate(int $exerciceId, string $extention, string $date): void;

    /**
     * Insert a new exercise row.
     *
     * @param int         $ressourceId  Resource ID.
     * @param string      $exerciceName Exercise name.
     * @param string      $extention    File extension.
     * @param string      $date         Date (Y-m-d).
     * @param string|null $hash         Original hash from source JSON (optional).
     * @return int The new exercise ID.
     */
    public function insertExercice(
        int $ressourceId,
        string $exerciceName,
        string $extention,
        string $date,
        ?string $hash = null
    ): int;
}
