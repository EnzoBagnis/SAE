<?php

namespace App\Controller;

use Core\Controller\AbstractController;
use App\Model\AuthenticationService;
use App\Model\AttemptRepository;
use App\Model\ExerciseRepository;
use Core\Service\SessionService;
use Core\Config\DatabaseConnection;

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
     * Return a paginated list of unique student identifiers (from attempts.user_id).
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
     * @param string $identifier Student identifier (value of attempts.user_id)
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
                     WHERE a.user_id = :user_id AND e.ressource_id = :rid
                     ORDER BY a.attempt_id DESC"
                );
                $stmt->execute(['user_id' => $identifier, 'rid' => $resourceId]);
            } else {
                $stmt = $pdo->prepare(
                    "SELECT a.*, e.exercice_name
                     FROM attempts a
                     LEFT JOIN exercices e ON a.exercice_id = e.exercice_id
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
                     LEFT JOIN corrections c ON e.exercice_name = c.exercice_name
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
                     LEFT JOIN corrections c ON e.exercice_name = c.exercice_name
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
            error_log('[DashboardApiController::studentsStats] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'data' => []], 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/dashboard/ia/macro
    // -------------------------------------------------------------------------

    /**
     * IA Macro : t-SNE global sur toutes les tentatives, centroïdes par exercice.
     * Réutilise le pipeline Python clustering_pipeline.py en mode "global".
     *
     * @return void
     */
    public function iaMacro(): void
    {
        $this->authService->requireAuth('/auth/login');

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $perplexity = (int)($input['perplexity'] ?? 30);
            $resourceId = isset($input['resource_id']) ? (int)$input['resource_id'] : null;

            // Fallback: resource_id peut venir de la query string (GET)
            if ($resourceId === null && isset($_GET['resource_id'])) {
                $resourceId = (int)$_GET['resource_id'];
            }

            $pdo = DatabaseConnection::getInstance()->getConnection();

            $sql = "
                SELECT
                    a.attempt_id,
                    a.aes2,
                    a.eval_set,
                    a.correct,
                    a.user_id AS user_id,
                    a.exercice_id AS exercice_id,
                    e.exercice_name AS exercise_name
                FROM attempts a
                JOIN exercices e ON a.exercice_id = e.exercice_id
                WHERE a.aes2 IS NOT NULL AND a.aes2 != ''
            ";
            $params = [];

            if ($resourceId) {
                $sql .= " AND e.ressource_id = :rid";
                $params['rid'] = $resourceId;
            }

            $sql .= " ORDER BY a.attempt_id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $attempts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (count($attempts) < 5) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Pas assez de tentatives avec AES pour la vue globale (' . count($attempts) . ' trouvées, minimum 5).',
                ]);
                return;
            }

            $payload = json_encode([
                'mode'       => 'global',
                'attempts'   => $attempts,
                'perplexity' => $perplexity,
            ], JSON_UNESCAPED_UNICODE);

            $result = $this->runPythonPipeline($payload);
            $this->jsonResponse($result);

        } catch (\Throwable $e) {
            error_log('[DashboardApiController::iaMacro] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()], 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/dashboard/ia/micro
    // -------------------------------------------------------------------------

    /**
     * IA Micro : clustering K-Means + t-SNE pour UN exercice, avec trajectoires.
     *
     * @return void
     */
    public function iaMicro(): void
    {
        $this->authService->requireAuth('/auth/login');

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $exerciseId = (int)($input['exercise_id'] ?? 0);
            $nClusters  = (int)($input['n_clusters']  ?? 8);
            $perplexity = (int)($input['perplexity']  ?? 30);

            if ($exerciseId <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'exercise_id invalide']);
                return;
            }

            $pdo = DatabaseConnection::getInstance()->getConnection();

            $stmt = $pdo->prepare("
                SELECT
                    a.attempt_id,
                    a.aes2,
                    a.eval_set,
                    a.correct,
                    a.user_id AS user_id,
                    a.exercice_id AS exercice_id,
                    NULL AS submission_date,
                    e.exercice_name AS exercise_name
                FROM attempts a
                JOIN exercices e ON a.exercice_id = e.exercice_id
                WHERE a.exercice_id = :eid
                  AND a.aes2 IS NOT NULL
                  AND a.aes2 != ''
                ORDER BY a.user_id, a.attempt_id
            ");
            $stmt->execute(['eid' => $exerciseId]);
            $attempts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (count($attempts) < 5) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Pas assez de tentatives avec AES pour cet exercice (' . count($attempts) . ' trouvées, minimum 5).',
                ]);
                return;
            }

            $payload = json_encode([
                'mode'        => 'micro',
                'attempts'    => $attempts,
                'n_clusters'  => $nClusters,
                'perplexity'  => $perplexity,
                'exercise_id' => $exerciseId,
            ], JSON_UNESCAPED_UNICODE);

            $result = $this->runPythonPipeline($payload);
            $this->jsonResponse($result);

        } catch (\Throwable $e) {
            error_log('[DashboardApiController::iaMicro] ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Private: Python pipeline runner (shared with iaMacro / iaMicro)
    // -------------------------------------------------------------------------

    /**
     * Execute clustering_pipeline.py with JSON payload on stdin.
     *
     * @param string $payload JSON string
     * @return array Decoded result
     */
    private function runPythonPipeline(string $payload): array
    {
        $projectRoot = realpath(__DIR__ . '/../../');
        $scriptPath  = $projectRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'clustering_pipeline.py';

        $pythonPath = $this->findPython($projectRoot);

        if ($pythonPath === null) {
            return [
                'success' => false,
                'message' => 'Aucun interpréteur Python avec gensim trouvé.',
            ];
        }

        if (!file_exists($scriptPath)) {
            return [
                'success' => false,
                'message' => 'Script clustering_pipeline.py introuvable : ' . $scriptPath,
            ];
        }

        $cmd = sprintf(
            '%s %s --from-stdin',
            escapeshellarg($pythonPath),
            escapeshellarg($scriptPath)
        );

        $env = null;
        if (PHP_OS_FAMILY !== 'Windows') {
            $home = getenv('HOME') ?: '/home/studtraj';
            $env = [
                'HOME'            => $home,
                'PYTHONUSERBASE'  => $home . '/.local',
                'PATH'            => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin',
                'PYTHONDONTWRITEBYTECODE' => '1',
            ];
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, null, $env);

        if (!is_resource($process)) {
            return [
                'success' => false,
                'message' => 'Impossible de lancer le script Python.',
            ];
        }

        fwrite($pipes[0], $payload);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        $jsonStr = null;
        foreach ([$stdout, $stderr, $stdout . $stderr] as $output) {
            $jsonStart = strpos($output, '{');
            if ($jsonStart !== false) {
                $candidate = substr($output, $jsonStart);
                $decoded = json_decode($candidate, true);
                if ($decoded !== null) {
                    $jsonStr = $candidate;
                    break;
                }
            }
        }

        if ($jsonStr === null) {
            $rawOutput = trim($stdout . "\n" . $stderr);
            return [
                'success' => false,
                'message' => 'Le script Python n\'a pas renvoyé de JSON valide (exit code: ' . $exitCode . '). Sortie: ' . substr($rawOutput, 0, 800),
            ];
        }

        return json_decode($jsonStr, true);
    }

    /**
     * Find a Python executable that can import gensim.
     */
    private function findPython(string $projectRoot): ?string
    {
        $absoluteCandidates = [
            $projectRoot . '/scripts/venv/bin/python3',
            $projectRoot . '/scripts/venv/bin/python',
            $projectRoot . '/scripts/venv/Scripts/python.exe',
            $projectRoot . '/venv/bin/python3',
            $projectRoot . '/venv/bin/python',
            '/home/studtraj/venv/bin/python3',
            '/home/studtraj/www/venv/bin/python3',
            '/usr/bin/python3',
            'C:\\xampp\\htdocs\\BUT3\\venv\\Scripts\\python.exe',
        ];

        foreach ($absoluteCandidates as $candidate) {
            if (file_exists($candidate) && $this->pythonHasGensim($candidate)) {
                return $candidate;
            }
        }

        foreach (['python3', 'python'] as $candidate) {
            if ($this->pythonHasGensim($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Check if a Python binary can import gensim.
     */
    private function pythonHasGensim(string $pythonBin): bool
    {
        $home = getenv('HOME') ?: '/home/studtraj';
        $envPrefix = '';
        if (PHP_OS_FAMILY !== 'Windows') {
            $envPrefix = sprintf(
                'HOME=%s PYTHONUSERBASE=%s ',
                escapeshellarg($home),
                escapeshellarg($home . '/.local')
            );
        }

        $cmd = sprintf(
            '%s%s -c %s 2>&1',
            $envPrefix,
            escapeshellarg($pythonBin),
            escapeshellarg('import gensim')
        );

        $output = [];
        $exitCode = -1;
        exec($cmd, $output, $exitCode);

        return $exitCode === 0;
    }
}
