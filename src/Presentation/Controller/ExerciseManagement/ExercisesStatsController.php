<?php

namespace Presentation\Controller\ExerciseManagement;

use Domain\ExerciseManagement\Repository\ExerciseRepositoryInterface;

/**
 * Exercises Stats Controller
 * Handles exercise statistics API endpoints
 */
class ExercisesStatsController
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
     * Get exercises statistics (API endpoint)
     *
     * @return void Outputs JSON response
     */
    public function getStats(): void
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

        try {
            // Get all exercises for the resource
            $exercises = $this->exerciseRepository->findByResourceId($resourceId);

            // Format data for chart
            $chartData = [];
            foreach ($exercises as $exercise) {
                $chartData[] = [
                    'exercise_id' => $exercise->exercise_id,
                    'exo_name' => $exercise->exo_name ?? $exercise->title ?? 'Exercice',
                    'description' => $exercise->description ?? '',
                    'difficulte' => $exercise->difficulte ?? 'Non spécifiée',
                    'funcname' => $exercise->funcname ?? '',
                    'success_rate' => $exercise->success_rate ?? 0,
                    'attempts_count' => $exercise->attempts_count ?? 0,
                    'students_count' => $exercise->students_count ?? 0
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $chartData
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get exercise completion statistics (API endpoint)
     *
     * @return void Outputs JSON response
     */
    public function getCompletionStats(): void
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

        try {
            // Get all exercises for the resource
            $exercises = $this->exerciseRepository->findByResourceId($resourceId);

            // Format data for completion chart
            $chartData = [];
            foreach ($exercises as $exercise) {
                $chartData[] = [
                    'exercise_id' => $exercise->exercise_id,
                    'exo_name' => $exercise->exo_name ?? $exercise->title ?? 'Exercice',
                    'students_count' => $exercise->students_count ?? 0,
                    'completion_rate' => $exercise->completion_rate ?? 0
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $chartData
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques de complétion',
                'error' => $e->getMessage()
            ]);
        }
    }
}

