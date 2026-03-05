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
     * Update the extension and date of an existing exercise.
     *
     * Required to refresh metadata when a re-imported exercise already exists.
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
     * Required to persist a newly imported exercise.
     *
     * @param int    $ressourceId  Resource ID.
     * @param string $exerciceName Exercise name.
     * @param string $extention    File extension.
     * @param string $date         Date (Y-m-d).
     * @return int The new exercise ID.
     */
    public function insertExercice(int $ressourceId, string $exerciceName, string $extention, string $date): int;
}
