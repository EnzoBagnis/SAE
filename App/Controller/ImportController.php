<?php

namespace App\Controller;

use Core\Controller\AbstractController;
use App\Model\AttemptRepository;
use App\Model\ExerciseRepository;
use App\Model\AuthenticationService;
use App\Model\UseCase\ImportAttemptsUseCase;
use App\Model\UseCase\ImportExercisesUseCase;
use Core\Service\SessionService;

/**
 * ImportController
 * Handles JSON import endpoints for exercises and attempts.
 * Replaces the legacy api_import_attempts.php and api_import_exercises.php scripts.
 */
class ImportController extends AbstractController
{
    private AuthenticationService $authService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->authService = new AuthenticationService(new SessionService());
    }

    /**
     * POST /api/import/exercises[?resource_id={id}]
     *
     * Imports exercises from a JSON body into the `exercices` table.
     *
     * Expected body:
     * { "exercises": [ { "exercice_name": "...", "extention": "py", "date": "2025-01-01" }, ... ] }
     * or a flat array: [ { ... }, ... ]
     *
     * @return void
     */
    public function exercises(): void
    {
        ob_start();
        header('Content-Type: application/json; charset=utf-8');

        set_time_limit(300);
        ini_set('memory_limit', '512M');

        // Auth check
        if (!$this->authService->isAuthenticated()) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $this->jsonResponse(['success' => false, 'error' => 'Non authentifié'], 401);
            return;
        }

        // Parse resource_id from query string
        $ressourceId = isset($_GET['resource_id']) ? (int) $_GET['resource_id'] : null;

        // Read & decode JSON body
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $this->jsonResponse(['success' => false, 'error' => 'Aucune donnée reçue'], 400);
            return;
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $this->jsonResponse(['success' => false, 'error' => 'JSON invalide : ' . json_last_error_msg()], 400);
            return;
        }

        // Normalize to a list
        $exercises = [];
        if (isset($data['exercises']) && is_array($data['exercises'])) {
            $exercises = $data['exercises'];
        } elseif (isset($data[0])) {
            $exercises = $data;
        } else {
            $exercises = [$data];
        }

        if (empty($exercises)) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $this->jsonResponse(['success' => false, 'error' => 'Aucun exercice trouvé dans le JSON'], 400);
            return;
        }

        // If resource_id not in query string, try to find it in the payload
        if ($ressourceId === null && isset($data['resource_id'])) {
            $ressourceId = (int) $data['resource_id'];
        }
        if ($ressourceId === null && isset($exercises[0]['resource_id'])) {
            $ressourceId = (int) $exercises[0]['resource_id'];
        }

        if ($ressourceId === null || $ressourceId <= 0) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $this->jsonResponse(['success' => false, 'error' => 'resource_id manquant ou invalide'], 400);
            return;
        }

        try {
            $useCase = new ImportExercisesUseCase(new ExerciseRepository());
            $result  = $useCase->execute($ressourceId, $exercises);
        } catch (\Throwable $e) {
            error_log('[ImportController::exercises] ' . $e->getMessage());
            if (ob_get_length()) {
                ob_end_clean();
            }
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
            return;
        }

        if (ob_get_length()) {
            ob_end_clean();
        }

        $this->jsonResponse([
            'success'  => true,
            'message'  => 'Import exercices terminé',
            'inserted' => $result['inserted'],
            'updated'  => $result['updated'],
            'skipped'  => $result['skipped'] ?? 0,
            'errors'   => array_slice($result['errors'], 0, 50),
        ]);
    }

    /**
     * POST /api/import/attempts[?resource_id={id}]
     *
     * Imports student attempts from a JSON body into the `attempts` table.
     *
     * Expected body:
     * { "attempts": [ { "exercice_id": 1, "user": "...", "correct": 1, ... }, ... ] }
     * or a flat array: [ { ... }, ... ]
     *
     * @return void
     */
    public function attempts(): void
    {
        ob_start();
        header('Content-Type: application/json; charset=utf-8');

        set_time_limit(300);
        ini_set('memory_limit', '512M');

        // Auth check
        if (!$this->authService->isAuthenticated()) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $this->jsonResponse(['success' => false, 'error' => 'Non authentifié'], 401);
            return;
        }

        // Parse resource_id from query string
        $ressourceId = isset($_GET['resource_id']) ? (int) $_GET['resource_id'] : null;
        // Legacy compat: ?id=xxx used by old import.js
        if ($ressourceId === null && isset($_GET['id'])) {
            $ressourceId = (int) $_GET['id'];
        }

        // Read & decode JSON body
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $this->jsonResponse(['success' => false, 'error' => 'Aucune donnée reçue'], 400);
            return;
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $this->jsonResponse(['success' => false, 'error' => 'JSON invalide : ' . json_last_error_msg()], 400);
            return;
        }

        // Normalize to a list
        $attempts = [];
        if (isset($data['attempts']) && is_array($data['attempts'])) {
            $attempts = $data['attempts'];
        } elseif (isset($data[0])) {
            $attempts = $data;
        } elseif (is_array($data)) {
            $attempts = [$data];
        }

        if (empty($attempts)) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $this->jsonResponse(['success' => false, 'error' => 'Aucune tentative trouvée'], 400);
            return;
        }

        // If resource_id not in query string, try to find it in the payload
        if ($ressourceId === null && isset($data['resource_id'])) {
            $ressourceId = (int) $data['resource_id'];
        }
        if ($ressourceId !== null && $ressourceId <= 0) {
            $ressourceId = null;
        }

        try {
            $useCase = new ImportAttemptsUseCase(
                new AttemptRepository(),
                new ExerciseRepository()
            );
            $result = $useCase->execute($attempts, $ressourceId);
        } catch (\Throwable $e) {
            error_log('[ImportController::attempts] ' . $e->getMessage());
            if (ob_get_length()) {
                ob_end_clean();
            }
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
            return;
        }

        if (ob_get_length()) {
            ob_end_clean();
        }

        $this->jsonResponse([
            'success'     => true,
            'message'     => 'Import tentatives terminé',
            'inserted'    => $result['inserted'],
            'added_count' => $result['inserted'], // compat with import.js
            'errors'      => array_slice($result['errors'], 0, 100),
        ]);
    }
}
