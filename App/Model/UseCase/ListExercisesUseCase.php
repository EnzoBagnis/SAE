<?php

namespace App\Model\UseCase;

use App\Model\UseCase\Ports\ExerciseListReaderPort;

/**
 * List Exercises Use Case.
 *
 * Handles listing exercises for a resource. Depends on
 * {@see ExerciseListReaderPort} for read access, following the
 * Dependency Inversion Principle.
 */
class ListExercisesUseCase
{
    private ExerciseListReaderPort $exerciseRepository;

    /**
     * Constructor.
     *
     * @param ExerciseListReaderPort $exerciseRepository Port for reading exercises.
     */
    public function __construct(ExerciseListReaderPort $exerciseRepository)
    {
        $this->exerciseRepository = $exerciseRepository;
    }

    /**
     * Execute use case
     *
     * @param int|null $resourceId Resource ID filter
     * @return array Result array with success status and data
     */
    public function execute(?int $resourceId = null): array
    {
        try {
            $exercises = $this->exerciseRepository->findAllWithAttempts($resourceId);

            // Convert entities to arrays for response
            $exercisesData = array_map(fn($exercise) => $exercise->toArray(), $exercises);

            return [
                'success' => true,
                'exercises' => $exercisesData,
                'total' => count($exercisesData),
            ];
        } catch (\Exception $e) {
            error_log("Error in ListExercisesUseCase: " . $e->getMessage());

            return [
                'success' => false,
                'exercises' => [],
                'total' => 0,
                'error' => 'Erreur lors du chargement des exercices',
            ];
        }
    }
}
