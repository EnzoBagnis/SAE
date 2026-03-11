<?php

namespace App\Controller;

use Core\Controller\AbstractController;
use App\Model\AuthenticationService;
use App\Model\AttemptRepository;
use App\Model\ExerciseRepository;
use Core\Service\SessionService;

/**
 * Dashboard API Controller
 * Provides JSON endpoints consumed by dashboard-main.js and its modules.
 * v2 — exercice_name used directly as display name (exo_name + funcname fields)
 *
 * Routes (all require authentication):
 *   GET /api/dashboard/students          — list unique students (optionally filtered by resource_id)
 *   GET /api/dashboard/student/{id}      — student details + attempts + stats
 *   GET /api/dashboard/exercises         — list exercises (optionally filtered by resource_id)
 *   GET /api/dashboard/students-stats    — global stats per student for a resource
 */
class DashboardApiController extends AbstractController
{
    private AuthenticationService $authService;
    private ?AttemptRepository $attemptRepository;
    private ?ExerciseRepository $exerciseRepository;
    private bool $dbAvailable;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->authService       = new AuthenticationService(new SessionService());
        $this->dbAvailable = true;
        try {
            $this->attemptRepository = new AttemptRepository();
            $this->exerciseRepository = new ExerciseRepository();
        } catch (\Throwable $e) {
            // DB not available — mark controller as degraded and log full error
            error_log('[DashboardApiController::__construct] DB init failed: ' . $e->__toString());
            $this->dbAvailable = false;
            $this->attemptRepository = null;
            $this->exerciseRepository = null;
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/dashboard/students
    // -------------------------------------------------------------------------

    /**
     * Return a paginated list of unique student identifiers (from attempts.user_id).
     * Optionally filtered by resource_id (via exercice → ressource join).
     *
     * @return void
     */
    public function students(): void
    {
        // For API endpoints prefer JSON error instead of redirect
        if (!$this->authService->isAuthenticated()) {
            $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            return;
        }

        if (!$this->dbAvailable) {
            $this->jsonResponse(['success' => false, 'message' => 'Base de données indisponible'], 503);
            return;
        }

        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;

        try {
            $pdo = $this->attemptRepository->getPdo();

            if ($resourceId !== null) {
                $stmt = $pdo->prepare(
                    "SELECT DISTINCT a.user_id
                     FROM attempts a
                     INNER JOIN exercices e ON a.exercice_id = e.exercice_id
                     WHERE e.ressource_id = :rid
                     ORDER BY a.user_id ASC"
                );
                $stmt->execute(['rid' => $resourceId]);
            } else {
                $stmt = $pdo->query(
                    "SELECT DISTINCT user_id FROM attempts ORDER BY user_id ASC"
                );
            }

            $users = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $students = array_map(fn($u) => [
                'id'         => $u,   // identifier used as ID
                'title'      => $u,
                'identifier' => $u,
            ], $users);

            $this->jsonResponse([
                'success' => true,
                'data'    => [
                    'students' => $students,
                    'total'    => count($students),
                ],
            ]);
        } catch (\Throwable $e) {
            error_log('[DashboardApiController::students] ' . $e->__toString());
            $this->jsonResponse(
                ['success' => false, 'message' => 'Erreur interne lors du chargement des étudiants.'],
                500
            );
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/dashboard/student/{identifier}
    // -------------------------------------------------------------------------

    /**
     * Return details, attempts and stats for a given student identifier.
     *
     * @param string $identifier Student identifier (value of attempts.user_id)
     * @return void
     */
    public function student(string $identifier): void
    {
        if (!$this->authService->isAuthenticated()) {
            $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            return;
        }

        if (!$this->dbAvailable) {
            $this->jsonResponse(['success' => false, 'message' => 'Base de données indisponible'], 503);
            return;
        }

        $identifier = urldecode($identifier);
        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;

        try {
            $pdo = $this->attemptRepository->getPdo();

            // Fetch attempts for this student (optionally scoped to a resource)
            if ($resourceId !== null) {
                $stmt = $pdo->prepare(
                    "SELECT a.*, COALESCE(c.funcname, e.exercice_name) AS exercice_name, e.ressource_id
                     FROM attempts a
                     INNER JOIN exercices e ON a.exercice_id = e.exercice_id
                     LEFT JOIN corrections c ON e.exercice_name = c.exercice_name
                     WHERE a.user_id = :user_id AND e.ressource_id = :rid
                     ORDER BY a.attempt_id DESC"
                );
                $stmt->execute(['user_id' => $identifier, 'rid' => $resourceId]);
            } else {
                $stmt = $pdo->prepare(
                    "SELECT a.*, COALESCE(c.funcname, e.exercice_name) AS exercice_name
                     FROM attempts a
                     LEFT JOIN exercices e ON a.exercice_id = e.exercice_id
                     LEFT JOIN corrections c ON e.exercice_name = c.exercice_name
                     WHERE a.user_id = :user_id
                     ORDER BY a.attempt_id DESC"
                );
                $stmt->execute(['user_id' => $identifier]);
            }

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $total   = count($rows);
            $correct = array_sum(array_column($rows, 'correct'));
            $rate    = $total > 0 ? round(($correct / $total) * 100, 1) : 0;

            $attempts = array_map(fn($r) => [
                'attempt_id'   => (int) $r['attempt_id'],
                'exercice_id'  => (int) $r['exercice_id'],
                'exercice_name' => $r['exercice_name'] ?? '',
                'correct'      => (bool) $r['correct'],
                'eval_set'     => $r['eval_set'] ?? '',
                'upload'       => $r['upload'] ?? '',
                'aes0'         => $r['aes0'] ?? '',
                'aes1'         => $r['aes1'] ?? '',
                'aes2'         => $r['aes2'] ?? '',
            ], $rows);

            $this->jsonResponse([
                'success' => true,
                'data'    => [
                    'student'  => [
                        'id'         => $identifier,
                        'identifier' => $identifier,
                        'title'      => $identifier,
                    ],
                    'attempts' => $attempts,
                    'stats'    => [
                        'total_attempts'   => $total,
                        'correct_attempts' => $correct,
                        'success_rate'     => $rate,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            error_log('[DashboardApiController::student] ' . $e->__toString());
            $this->jsonResponse(
                ['success' => false, 'message' => 'Erreur interne lors du chargement de l\'étudiant.'],
                500
            );
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/dashboard/exercises
    // -------------------------------------------------------------------------

    /**
     * Return exercises list, optionally filtered by resource_id.
     *
     * @return void
     */
    public function exercises(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        if (!$this->authService->isAuthenticated()) {
            $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            return;
        }

        if (!$this->dbAvailable) {
            $this->jsonResponse(['success' => false, 'message' => 'Base de données indisponible'], 503);
            return;
        }

        $resourceId  = isset($_GET['resource_id'])  ? (int)$_GET['resource_id']  : null;
        $exerciseId  = isset($_GET['exercise_id'])  ? (int)$_GET['exercise_id']  : null;

        try {
            $pdo = $this->exerciseRepository->getPdo();

            // Single exercise detail with per-student attempts
            if ($exerciseId !== null) {
                $stmt = $pdo->prepare(
                    "SELECT e.exercice_id, e.ressource_id,
                            COALESCE(c.funcname, e.exercice_name) AS exercice_name,
                            e.extention, e.`date`
                     FROM exercices e
                     LEFT JOIN corrections c ON e.exercice_name = c.exercice_name
                     WHERE e.exercice_id = :eid"
                );
                $stmt->execute(['eid' => $exerciseId]);
                $exRow = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$exRow) {
                    $this->jsonResponse(['success' => false, 'message' => 'Exercise not found'], 404);
                    return;
                }

                // Fetch all attempts for this exercise grouped by student
                $stmt2 = $pdo->prepare(
                    "SELECT user_id,
                            COUNT(*) AS total,
                            SUM(CASE WHEN correct=1 THEN 1 ELSE 0 END) AS correct_count
                     FROM attempts WHERE exercice_id = :eid GROUP BY user_id ORDER BY user_id ASC"
                );
                $stmt2->execute(['eid' => $exerciseId]);
                $studentRows = $stmt2->fetchAll(\PDO::FETCH_ASSOC);

                $students = array_map(fn($r) => [
                    'id'             => $r['user_id'],
                    'identifier'     => $r['user_id'],
                    'total_attempts' => (int)$r['total'],
                    'correct_count'  => (int)$r['correct_count'],
                    'success_rate'   => (int)$r['total'] > 0
                        ? round(((int)$r['correct_count'] / (int)$r['total']) * 100, 1) : 0,
                ], $studentRows);

                $this->jsonResponse([
                    'success' => true,
                    'data'    => [
                        'exercise' => [
                            'exercise_id' => (int)$exRow['exercice_id'],
                            'exo_name'    => $exRow['exercice_name'],
                            'funcname'    => $exRow['exercice_name'],
                        ],
                        'students' => $students,
                    ],
                ]);
                return;
            }

            // List of exercises
            if ($resourceId !== null) {
                $stmt = $pdo->prepare(
                    "SELECT e.exercice_id, e.ressource_id,
                            COALESCE(c.funcname, e.exercice_name) AS exercice_name,
                            e.extention, e.`date`,
                            COUNT(a.attempt_id)                              AS total_attempts,
                            SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END)  AS successful_attempts
                     FROM exercices e
                     LEFT JOIN corrections c ON e.exercice_name = c.exercice_name
                     LEFT JOIN attempts a ON e.exercice_id = a.exercice_id
                     WHERE e.ressource_id = :rid
                     GROUP BY e.exercice_id
                     ORDER BY COALESCE(c.funcname, e.exercice_name) ASC"
                );
                $stmt->execute(['rid' => $resourceId]);
            } else {
                $stmt = $pdo->query(
                    "SELECT e.exercice_id, e.ressource_id,
                            COALESCE(c.funcname, e.exercice_name) AS exercice_name,
                            e.extention, e.`date`,
                            COUNT(a.attempt_id)                              AS total_attempts,
                            SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END)  AS successful_attempts
                     FROM exercices e
                     LEFT JOIN corrections c ON e.exercice_name = c.exercice_name
                     LEFT JOIN attempts a ON e.exercice_id = a.exercice_id
                     GROUP BY e.exercice_id
                     ORDER BY COALESCE(c.funcname, e.exercice_name) ASC"
                );
            }

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $exercises = array_map(fn($r) => [
                'exercise_id'         => (int) $r['exercice_id'],
                'exo_name'            => $r['exercice_name'],
                'funcname'            => $r['exercice_name'],
                'extention'           => $r['extention'],
                'date'                => $r['date'],
                'total_attempts'      => (int) $r['total_attempts'],
                'successful_attempts' => (int) $r['successful_attempts'],
                'success_rate'        => (int)$r['total_attempts'] > 0
                    ? round(((int)$r['successful_attempts'] / (int)$r['total_attempts']) * 100, 1)
                    : null,
            ], $rows);

            $this->jsonResponse([
                'success' => true,
                'data'    => ['exercises' => $exercises, 'total' => count($exercises)],
            ]);
        } catch (\Throwable $e) {
            error_log('[DashboardApiController::exercises] ' . $e->__toString());
            $this->jsonResponse(
                ['success' => false, 'message' => 'Erreur interne lors du chargement des exercices.'],
                500
            );
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/dashboard/students-stats
    // -------------------------------------------------------------------------

    /**
     * Return global stats per student for charts (optionally filtered by resource_id).
     *
     * @return void
     */
    public function studentsStats(): void
    {
        if (!$this->authService->isAuthenticated()) {
            $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            return;
        }

        if (!$this->dbAvailable) {
            $this->jsonResponse(['success' => false, 'message' => 'Base de données indisponible'], 503);
            return;
        }

        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;

        try {
            $pdo = $this->attemptRepository->getPdo();

            if ($resourceId !== null) {
                $stmt = $pdo->prepare(
                    "SELECT a.user_id,
                            COUNT(a.attempt_id)                              AS total_attempts,
                            SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END)  AS correct_attempts
                     FROM attempts a
                     INNER JOIN exercices e ON a.exercice_id = e.exercice_id
                     WHERE e.ressource_id = :rid
                     GROUP BY a.user_id
                     ORDER BY a.user_id ASC"
                );
                $stmt->execute(['rid' => $resourceId]);
            } else {
                $stmt = $pdo->query(
                    "SELECT a.user_id,
                            COUNT(a.attempt_id)                              AS total_attempts,
                            SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END)  AS correct_attempts
                     FROM attempts a
                     GROUP BY a.user_id
                     ORDER BY a.user_id ASC"
                );
            }

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stats = array_map(fn($r) => [
                'student_id'       => $r['user_id'],
                'identifier'       => $r['user_id'],
                'total_attempts'   => (int) $r['total_attempts'],
                'correct_attempts' => (int) $r['correct_attempts'],
                'success_rate'     => (int)$r['total_attempts'] > 0
                    ? round(((int)$r['correct_attempts'] / (int)$r['total_attempts']) * 100, 1)
                    : 0,
            ], $rows);

            $this->jsonResponse(['success' => true, 'data' => $stats]);
        } catch (\Throwable $e) {
            error_log('[DashboardApiController::studentsStats] ' . $e->__toString());
            $this->jsonResponse(
                ['success' => false, 'message' => 'Erreur interne lors du calcul des statistiques.'],
                500
            );
        }
    }
}
