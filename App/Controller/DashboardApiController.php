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
    private AttemptRepository $attemptRepository;
    private ExerciseRepository $exerciseRepository;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->authService       = new AuthenticationService(new SessionService());
        $this->attemptRepository = new AttemptRepository();
        $this->exerciseRepository = new ExerciseRepository();
    }

    // -------------------------------------------------------------------------
    // GET /api/dashboard/students
    // -------------------------------------------------------------------------

    /**
     * Return a paginated list of unique student identifiers (from attempts.user).
     * Optionally filtered by resource_id (via exercice → ressource join).
     *
     * @return void
     */
    public function students(): void
    {
        $this->authService->requireAuth('/auth/login');

        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;

        try {
            $pdo = $this->attemptRepository->getPdo();

            if ($resourceId !== null) {
                $stmt = $pdo->prepare(
                    "SELECT DISTINCT a.`user`
                     FROM attempts a
                     INNER JOIN exercices e ON a.exercice_id = e.exercice_id
                     WHERE e.ressource_id = :rid
                     ORDER BY a.`user` ASC"
                );
                $stmt->execute(['rid' => $resourceId]);
            } else {
                $stmt = $pdo->query(
                    "SELECT DISTINCT `user` FROM attempts ORDER BY `user` ASC"
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
            error_log('[DashboardApiController::students] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'data' => ['students' => [], 'total' => 0]], 500);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/dashboard/student/{identifier}
    // -------------------------------------------------------------------------

    /**
     * Return details, attempts and stats for a given student identifier.
     *
     * @param string $identifier Student identifier (value of attempts.user)
     * @return void
     */
    public function student(string $identifier): void
    {
        $this->authService->requireAuth('/auth/login');

        $identifier = urldecode($identifier);
        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;

        try {
            $pdo = $this->attemptRepository->getPdo();

            // Fetch attempts for this student (optionally scoped to a resource)
            if ($resourceId !== null) {
                $stmt = $pdo->prepare(
                    "SELECT a.*, e.exercice_name, e.ressource_id
                     FROM attempts a
                     INNER JOIN exercices e ON a.exercice_id = e.exercice_id
                     WHERE a.`user` = :user AND e.ressource_id = :rid
                     ORDER BY a.attempt_id DESC"
                );
                $stmt->execute(['user' => $identifier, 'rid' => $resourceId]);
            } else {
                $stmt = $pdo->prepare(
                    "SELECT a.*, e.exercice_name
                     FROM attempts a
                     LEFT JOIN exercices e ON a.exercice_id = e.exercice_id
                     WHERE a.`user` = :user
                     ORDER BY a.attempt_id DESC"
                );
                $stmt->execute(['user' => $identifier]);
            }

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $total   = count($rows);
            $correct = array_sum(array_column($rows, 'correct'));
            $rate    = $total > 0 ? round(($correct / $total) * 100, 1) : 0;

            $attempts = array_map(fn($r) => [
                'attempt_id'   => (int) $r['attempt_id'],
                'exercice_id'  => (int) $r['exercice_id'],
                'exercice_name'=> $r['exercice_name'] ?? '',
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
            error_log('[DashboardApiController::student] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
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
        $this->authService->requireAuth('/auth/login');

        $resourceId  = isset($_GET['resource_id'])  ? (int)$_GET['resource_id']  : null;
        $exerciseId  = isset($_GET['exercise_id'])  ? (int)$_GET['exercise_id']  : null;

        try {
            $pdo = $this->exerciseRepository->getPdo();

            // Single exercise detail with per-student attempts
            if ($exerciseId !== null) {
                $stmt = $pdo->prepare(
                    "SELECT e.exercice_id, e.ressource_id, e.exercice_name, e.extention, e.`date`,
                            COALESCE(c.funcname, e.exercice_name) AS display_name
                     FROM exercices e
                     LEFT JOIN corrections c ON e.exercice_name = c.exo_name
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
                    "SELECT `user`,
                            COUNT(*) AS total,
                            SUM(CASE WHEN correct=1 THEN 1 ELSE 0 END) AS correct_count
                     FROM attempts WHERE exercice_id = :eid GROUP BY `user` ORDER BY `user` ASC"
                );
                $stmt2->execute(['eid' => $exerciseId]);
                $studentRows = $stmt2->fetchAll(\PDO::FETCH_ASSOC);

                $students = array_map(fn($r) => [
                    'id'             => $r['user'],
                    'identifier'     => $r['user'],
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
                            'funcname'    => $exRow['display_name'],  // readable name
                        ],
                        'students' => $students,
                    ],
                ]);
                return;
            }

            // List of exercises
            if ($resourceId !== null) {
                $stmt = $pdo->prepare(
                    "SELECT e.exercice_id, e.ressource_id, e.exercice_name, e.extention, e.`date`,
                            COALESCE(c.funcname, e.exercice_name) AS display_name,
                            COUNT(a.attempt_id)                              AS total_attempts,
                            SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END)  AS successful_attempts
                     FROM exercices e
                     LEFT JOIN corrections c ON e.exercice_name = c.exo_name
                     LEFT JOIN attempts a ON e.exercice_id = a.exercice_id
                     WHERE e.ressource_id = :rid
                     GROUP BY e.exercice_id
                     ORDER BY display_name ASC"
                );
                $stmt->execute(['rid' => $resourceId]);
            } else {
                $stmt = $pdo->query(
                    "SELECT e.exercice_id, e.ressource_id, e.exercice_name, e.extention, e.`date`,
                            COALESCE(c.funcname, e.exercice_name) AS display_name,
                            COUNT(a.attempt_id)                              AS total_attempts,
                            SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END)  AS successful_attempts
                     FROM exercices e
                     LEFT JOIN corrections c ON e.exercice_name = c.exo_name
                     LEFT JOIN attempts a ON e.exercice_id = a.exercice_id
                     GROUP BY e.exercice_id
                     ORDER BY display_name ASC"
                );
            }

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $exercises = array_map(fn($r) => [
                'exercise_id'         => (int) $r['exercice_id'],
                'exo_name'            => $r['exercice_name'],        // raw hash (for internal use)
                'funcname'            => $r['display_name'],         // readable name for display
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
            error_log('[DashboardApiController::exercises] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'data' => ['exercises' => [], 'total' => 0]], 500);
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
        $this->authService->requireAuth('/auth/login');

        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;

        try {
            $pdo = $this->attemptRepository->getPdo();

            if ($resourceId !== null) {
                $stmt = $pdo->prepare(
                    "SELECT a.`user`,
                            COUNT(a.attempt_id)                              AS total_attempts,
                            SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END)  AS correct_attempts
                     FROM attempts a
                     INNER JOIN exercices e ON a.exercice_id = e.exercice_id
                     WHERE e.ressource_id = :rid
                     GROUP BY a.`user`
                     ORDER BY a.`user` ASC"
                );
                $stmt->execute(['rid' => $resourceId]);
            } else {
                $stmt = $pdo->query(
                    "SELECT a.`user`,
                            COUNT(a.attempt_id)                              AS total_attempts,
                            SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END)  AS correct_attempts
                     FROM attempts a
                     GROUP BY a.`user`
                     ORDER BY a.`user` ASC"
                );
            }

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stats = array_map(fn($r) => [
                'student_id'       => $r['user'],
                'identifier'       => $r['user'],
                'total_attempts'   => (int) $r['total_attempts'],
                'correct_attempts' => (int) $r['correct_attempts'],
                'success_rate'     => (int)$r['total_attempts'] > 0
                    ? round(((int)$r['correct_attempts'] / (int)$r['total_attempts']) * 100, 1)
                    : 0,
            ], $rows);

            $this->jsonResponse(['success' => true, 'data' => $stats]);
        } catch (\Throwable $e) {
            error_log('[DashboardApiController::studentsStats] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'data' => []], 500);
        }
    }
}
