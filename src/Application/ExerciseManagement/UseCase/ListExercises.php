<?php

namespace Application\ExerciseManagement\UseCase;

use Application\ExerciseManagement\DTO\ListExercisesRequest;
use Application\ExerciseManagement\DTO\ListExercisesResponse;
use Domain\ExerciseManagement\Repository\ExerciseRepositoryInterface;

/**
 * List Exercises Use Case
 * Handles listing exercises
 */
class ListExercises
{
    private ExerciseRepositoryInterface $exerciseRepository;

    /**
     * Constructor
     *
     * @param ExerciseRepositoryInterface $exerciseRepository Exercise repository
     */
    public function __construct(ExerciseRepositoryInterface $exerciseRepository)
    {
        $this->exerciseRepository = $exerciseRepository;
    }

    /**
     * Execute use case
     *
     * @param ListExercisesRequest $request Request data
     * @return ListExercisesResponse Response data
     */
    public function execute(ListExercisesRequest $request): ListExercisesResponse
    {
        try {
            $exercises = $this->exerciseRepository->findAll($request->getResourceId());
            $total = count($exercises);

            return new ListExercisesResponse(
                true,
                $exercises,
                $total
            );
        } catch (\Exception $e) {
            error_log("Error in ListExercises: " . $e->getMessage());
            return new ListExercisesResponse(
                false,
                [],
                0,
                'Erreur lors du chargement des exercices'
            );
        }
    }
}
