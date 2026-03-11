<?php

namespace App\Controller;

use Core\Controller\AbstractController;
use App\Model\UseCase\ListExercisesUseCase;
use App\Model\ExerciseRepository;
use App\Model\AuthenticationService;
use Core\Service\SessionService;

/**
 * Exercises Controller
 * Handles exercise listing
 */
class ExercisesController extends AbstractController
{
    private ListExercisesUseCase $listExercisesUseCase;
    private AuthenticationService $authService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $exerciseRepository = new ExerciseRepository();
        $this->listExercisesUseCase = new ListExercisesUseCase($exerciseRepository);
        $this->authService = new AuthenticationService(new SessionService());
    }

    /**
     * List exercises
     *
     * @return void
     */
    public function index(): void
    {
        $this->authService->requireAuth('/auth/login');

        $resourceId = $this->getQuery('resource_id');
        $result = $this->listExercisesUseCase->execute($resourceId);

        if ($this->isAjax()) {
            $this->jsonResponse($result);
        } else {
            $this->renderView('exercises/list', [
                'exercises' => $result['exercises'] ?? [],
                'total' => $result['total'] ?? 0,
            ]);
        }
    }

    /**
     * Show exercise details.
     *
     * @param int $exerciseId Exercise ID
     * @return void
     */
    public function show(int $exerciseId): void
    {
        $this->authService->requireAuth('/auth/login');

        try {
            $exerciseRepo = new ExerciseRepository();
            $exercise = $exerciseRepo->findById($exerciseId);
        } catch (\Throwable $e) {
            error_log('[ExercisesController::show] findById error: ' . $e->getMessage());
            $exercise = null;
        }

        if (!$exercise) {
            http_response_code(404);
            $this->renderView('errors/404');
            return;
        }

        // Rendre la vue dashboard avec le contexte exercice pour la vue Micro IA
        $resourceId = $exercise->getResourceId();

        $this->renderView('user/dashboard', [
            'resource_id'    => $resourceId > 0 ? $resourceId : null,
            'exercise_id'    => $exerciseId,
            'user_firstname' => $this->authService->getUserFirstName() ?? '',
            'user_lastname'  => $this->authService->getUserLastName() ?? '',
            'title'          => 'StudTraj - ' . htmlspecialchars($exercise->getExoName()),
        ]);
    }
}
