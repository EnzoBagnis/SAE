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
 */
class DashboardApiController extends AbstractController
{
    private AuthenticationService $authService;
    private AttemptRepository $attemptRepository;
    private ExerciseRepository $exerciseRepository;

    public function __construct()
    {
        $this->authService       = new AuthenticationService(new SessionService());
        $this->attemptRepository = new AttemptRepository();
        $this->exerciseRepository = new ExerciseRepository();
    }

    // GET /api/dashboard/students
    public function students(): void
    {
        $this->authService->requireAuth('/auth/login');
        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;

        try {
            $pdo = $this->attemptRepository->getPdo();

            if ($resourceId !== null) {
                $stmt = $pdo->prepare(
                    "SELECT DISTINCT s.student_id, s.student_identifier
                     FROM attempts a
                     INNER JOIN exercises e ON a.exercise_id = e.exercise_id
                     INNER JOIN students s ON a.student_id = s.student_id
                     WHERE e.resource_id = :rid
                     ORDER BY s.student_identifier ASC"
                );
                $stmt->execute(['rid' => $resourceId]);
            } else {
                $stmt = $pdo->query(
                    "SELECT DISTINCT s.student_id, s.student_identifier
                     FROM attempts a
                     INNER JOIN students s ON a.student_id = s.student_id
                     ORDER BY s.student_identifier ASC"
                );
            }

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $students = array_map(fn($r) => [
                'id'         => (int) $r['student_id'],
                'title'      => $r['student_identifier'],
                'identifier' => $r['student_identifier'],
            ], $rows);

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

    // GET /api/dashboard/student/{identifier}
    public function student(string $identifier): void
    {
        $this->authService->requireAuth('/auth/login');
        $identifier = urldecode($identifier);
        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;

        try {
            $pdo = $this->attemptRepository->getPdo();

            if ($resourceId !== null) {
                $stmt = $pdo->prepare(
                    "SELECT a.*, e.exo_name, e.resource_id, s.student_identifier
                     FROM attempts a
                     INNER JOIN exercises e ON a.exercise_id = e.exercise_id
                     INNER JOIN students s ON a.student_id = s.student_id
                     WHERE s.student_id = :sid AND e.resource_id = :rid
                     ORDER BY a.attempt_id DESC"
                );
                $stmt->execute(['sid' => (int) $identifier, 'rid' => $resourceId]);
            } else {
                $stmt = $pdo->prepare(
                    "SELECT a.*, e.exo_name, s.student_identifier
                     FROM attempts a
                     LEFT JOIN exercises e ON a.exercise_id = e.exercise_id
                     LEFT JOIN students s ON a.student_id = s.student_id
                     WHERE a.student_id = :sid
                     ORDER BY a.attempt_id DESC"
                );
                $stmt->execute(['sid' => (int) $identifier]);
            }

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $total   = count($rows);
            $correct = array_sum(array_column($rows, 'correct'));
            $rate    = $total > 0 ? round(($correct / $total) * 100, 1) : 0;
            $studentIdentifier = $rows[0]['student_identifier'] ?? $identifier;

            $attempts = array_map(fn($r) => [
                'attempt_id'    => (int) $r['attempt_id'],
                'exercise_id'   => (int) $r['exercise_id'],
                'exercise_name' => $r['exo_name'] ?? '',
                'correct'       => (bool) $r['correct'],
                'eval_set'      => $r['eval_set'] ?? '',
                'upload'        => $r['upload'] ?? '',
                'aes0'          => $r['aes0'] ?? '',
                'aes1'          => $r['aes1'] ?? '',
                'aes2'          => $r['aes2'] ?? '',
            ], $rows);

            $this->jsonResponse([
                'success' => true,
                'data'    => [
                    'student'  => [
                        'id'         => (int) $identifier,
                        'identifier' => $studentIdentifier,
                        'title'      => $studentIdentifier,
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

    // GET /api/dashboard/exercises
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
                    "SELECT e.exercise_id, e.resource_id, e.exo_name,
                            COALESCE(e.funcname, e.exo_name) AS display_name
                     FROM exercises e
                     WHERE e.exercise_id = :eid"
                );
                $stmt->execute(['eid' => $exerciseId]);
                $exRow = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$exRow) {
                    $this->jsonResponse(['success' => false, 'message' => 'Exercise not found'], 404);
                    return;
                }

                $stmt2 = $pdo->prepare(
                    "SELECT a.student_id,
                            s.student_identifier,
                            COUNT(*) AS total,
                            SUM(CASE WHEN a.correct=1 THEN 1 ELSE 0 END) AS correct_count
                     FROM attempts a
                     LEFT JOIN students s ON a.student_id = s.student_id
                     WHERE a.exercise_id = :eid
                     GROUP BY a.student_id
                     ORDER BY s.student_identifier ASC"
                );
                $stmt2->execute(['eid' => $exerciseId]);
                $studentRows = $stmt2->fetchAll(\PDO::FETCH_ASSOC);

                $students = array_map(fn($r) => [
                    'id'             => (int) $r['student_id'],
                    'identifier'     => $r['student_identifier'] ?? $r['student_id'],
                    'total_attempts' => (int)$r['total'],
                    'correct_count'  => (int)$r['correct_count'],
                    'success_rate'   => (int)$r['total'] > 0
                        ? round(((int)$r['correct_count'] / (int)$r['total']) * 100, 1) : 0,
                ], $studentRows);

                $this->jsonResponse([
                    'success' => true,
                    'data'    => [
                        'exercise' => [
                            'exercise_id' => (int)$exRow['exercise_id'],
                            'exo_name'    => $exRow['exo_name'],
                            'funcname'    => $exRow['display_name'],
                        ],
                        'students' => $students,
                    ],
                ]);
                return;
            }

            // List of exercises
            if ($resourceId !== null) {
                $stmt = $pdo->prepare(
                    "SELECT e.exercise_id, e.resource_id, e.exo_name,
                            COALESCE(e.funcname, e.exo_name) AS display_name,
                            COUNT(a.attempt_id)                              AS total_attempts,
                            SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END)  AS successful_attempts
                     FROM exercises e
                     LEFT JOIN attempts a ON e.exercise_id = a.exercise_id
                     WHERE e.resource_id = :rid
                     GROUP BY e.exercise_id
                     ORDER BY display_name ASC"
                );
                $stmt->execute(['rid' => $resourceId]);
            } else {
                $stmt = $pdo->query(
                    "SELECT e.exercise_id, e.resource_id, e.exo_name,
                            COALESCE(e.funcname, e.exo_name) AS display_name,
                            COUNT(a.attempt_id)                              AS total_attempts,
                            SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END)  AS successful_attempts
                     FROM exercises e
                     LEFT JOIN attempts a ON e.exercise_id = a.exercise_id
                     GROUP BY e.exercise_id
                     ORDER BY display_name ASC"
                );
            }

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $exercises = array_map(fn($r) => [
                'exercise_id'         => (int) $r['exercise_id'],
                'exo_name'            => $r['exo_name'],
                'funcname'            => $r['display_name'],
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

    // GET /api/dashboard/students-stats
    public function studentsStats(): void
    {
        $this->authService->requireAuth('/auth/login');
        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;

        try {
            $pdo = $this->attemptRepository->getPdo();

            if ($resourceId !== null) {
                $stmt = $pdo->prepare(
                    "SELECT a.student_id,
                            s.student_identifier,
                            COUNT(a.attempt_id)                              AS total_attempts,
                            SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END)  AS correct_attempts
                     FROM attempts a
                     INNER JOIN exercises e ON a.exercise_id = e.exercise_id
                     LEFT JOIN students s ON a.student_id = s.student_id
                     WHERE e.resource_id = :rid
                     GROUP BY a.student_id
                     ORDER BY s.student_identifier ASC"
                );
                $stmt->execute(['rid' => $resourceId]);
            } else {
                $stmt = $pdo->query(
                    "SELECT a.student_id,
                            s.student_identifier,
                            COUNT(a.attempt_id)                              AS total_attempts,
                            SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END)  AS correct_attempts
                     FROM attempts a
                     LEFT JOIN students s ON a.student_id = s.student_id
                     GROUP BY a.student_id
                     ORDER BY s.student_identifier ASC"
                );
            }

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stats = array_map(fn($r) => [
                'student_id'       => (int) $r['student_id'],
                'identifier'       => $r['student_identifier'] ?? $r['student_id'],
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
