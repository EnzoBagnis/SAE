<?php

namespace Presentation\Controller\ExerciseManagement;

use Application\ExerciseManagement\DTO\ListExercisesRequest;
use Application\ExerciseManagement\UseCase\ListExercises;

/**
 * Exercises Controller
 * Handles HTTP requests for exercise management
 */
class ExercisesController
{
    private ListExercises $listExercisesUseCase;

    /**
     * Constructor
     *
     * @param ListExercises $listExercisesUseCase List exercises use case
     */
    public function __construct(ListExercises $listExercisesUseCase)
    {
        $this->listExercisesUseCase = $listExercisesUseCase;
    }

    /**
     * Get exercises list (API endpoint)
     *
     * @return void Outputs JSON response
     */
    public function getExercises(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Check authentication
        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Non authentifié'
            ]);
            return;
        }

        // Get request parameters
        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;

        // Create request DTO
        $request = new ListExercisesRequest($resourceId);

        // Execute use case
        $response = $this->listExercisesUseCase->execute($request);

        // Return JSON response
        if (!$response->isSuccess()) {
            http_response_code(500);
        }

        echo json_encode($response->toArray());
    }
}
