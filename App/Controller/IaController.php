<?php

namespace App\Controller;

use Core\Controller\AbstractController;
use App\Model\AuthenticationService;
use Core\Service\SessionService;
use Core\Config\DatabaseConnection;

/**
 * IA Controller
 * Handles the AI/ML clustering pipeline (Doc2Vec → KMeans → t-SNE)
 */
class IaController extends AbstractController
{
    private AuthenticationService $authService;

    public function __construct()
    {
        $this->authService = new AuthenticationService(new SessionService());
    }

    /**
     * Show the IA page with "Cartographie des codes" tab
     */
    public function index(): void
    {
        $this->authService->requireAuth('/auth/login');

        $pdo = DatabaseConnection::getInstance()->getConnection();

        // Stats globales
        $totalAttempts  = (int)$pdo->query("SELECT COUNT(*) FROM attempts")->fetchColumn();
        $totalExercises = (int)$pdo->query("SELECT COUNT(*) FROM exercices")->fetchColumn();
        $totalStudents  = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM attempts")->fetchColumn();

        // Répartition par eval_set
        $evalSets = $pdo->query(
            "SELECT eval_set, COUNT(*) AS count FROM attempts"
            . " WHERE eval_set IS NOT NULL GROUP BY eval_set ORDER BY eval_set"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Ressources
        $resources = $pdo->query(
            "SELECT ressource_id, ressource_name FROM ressources ORDER BY ressource_name ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Exercices (tous, pour le sélecteur dynamique côté JS)
        $exercises = $pdo->query(
            "SELECT e.exercice_id, e.exercice_name AS exercise_name, e.ressource_id, r.ressource_name,
                    COUNT(a.attempt_id) AS nb_attempts
             FROM exercices e
             LEFT JOIN ressources r ON e.ressource_id = r.ressource_id
             LEFT JOIN attempts a   ON a.exercice_id = e.exercice_id AND a.aes2 IS NOT NULL AND a.aes2 != ''
             GROUP BY e.exercice_id
             ORDER BY r.ressource_name ASC, e.exercice_name ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $this->renderView('user/ia', [
            'stats' => [
                'total_attempts'  => $totalAttempts,
                'total_exercises' => $totalExercises,
                'total_students'  => $totalStudents,
                'eval_sets'       => $evalSets,
            ],
            'resources' => $resources,
            'exercises' => $exercises,
        ]);
    }

    /**
     * API endpoint : GET /api/ia/status?resource_id=X
     * Vérifie si des tentatives avec AES existent pour la ressource (prêtes pour l'IA).
     */
    public function status(): void
    {
        if (!$this->authService->isAuthenticated()) {
            $this->jsonError('Non authentifié', 401);
            return;
        }

        try {
            $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;
            $exerciseId = isset($_GET['exercise_id']) ? (int)$_GET['exercise_id'] : null;

            $pdo = DatabaseConnection::getInstance()->getConnection();

            if ($exerciseId) {
                // Statut micro : nombre de tentatives AES pour un exercice
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM attempts a
                    JOIN exercices e ON a.exercice_id = e.exercice_id
                    WHERE a.exercice_id = :eid
                      AND a.aes2 IS NOT NULL AND a.aes2 != ''
                ");
                $stmt->execute(['eid' => $exerciseId]);
                $count = (int)$stmt->fetchColumn();

                $this->jsonResponse([
                    'success' => true,
                    'available' => $count >= 5,
                    'aes_count' => $count,
                    'exercise_id' => $exerciseId,
                    'message' => $count >= 5
                        ? "Données IA disponibles ($count tentatives avec AES)."
                        : "Pas assez de données AES ($count/5 minimum).",
                ]);
            } elseif ($resourceId) {
                // Statut macro : nombre de tentatives AES pour la ressource
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM attempts a
                    JOIN exercices e ON a.exercice_id = e.exercice_id
                    WHERE e.ressource_id = :rid
                      AND a.aes2 IS NOT NULL AND a.aes2 != ''
                ");
                $stmt->execute(['rid' => $resourceId]);
                $count = (int)$stmt->fetchColumn();

                // Nombre d'exercices avec AES
                $stmt2 = $pdo->prepare("
                    SELECT COUNT(DISTINCT e.exercice_id) FROM attempts a
                    JOIN exercices e ON a.exercice_id = e.exercice_id
                    WHERE e.ressource_id = :rid
                      AND a.aes2 IS NOT NULL AND a.aes2 != ''
                ");
                $stmt2->execute(['rid' => $resourceId]);
                $exCount = (int)$stmt2->fetchColumn();

                $this->jsonResponse([
                    'success' => true,
                    'available' => $count >= 5,
                    'aes_count' => $count,
                    'exercise_count' => $exCount,
                    'resource_id' => $resourceId,
                    'message' => $count >= 5
                        ? "Données IA disponibles ($count tentatives AES, $exCount exercices)."
                        : "Pas assez de données AES ($count/5 minimum).",
                ]);
            } else {
                $this->jsonError('resource_id ou exercise_id requis.');
            }
        } catch (\Throwable $e) {
            $this->jsonError('Erreur serveur : ' . $e->getMessage(), 500);
        }
    }

    /**
     * API endpoint : POST /api/ia/macro
     * Vue Macro : t-SNE global sur TOUTES les tentatives, centroïdes par TD.
     */
    public function macro(): void
    {
        if (!$this->authService->isAuthenticated()) {
            $this->jsonError('Non authentifié', 401);
            return;
        }

        if (!$this->isPost()) {
            $this->jsonError('Méthode non autorisée', 405);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $perplexity = (int)($input['perplexity'] ?? 30);
            $resourceId = isset($input['resource_id']) ? (int)$input['resource_id'] : null;

            $pdo = DatabaseConnection::getInstance()->getConnection();

            // Extraire TOUTES les tentatives avec AES (filtrées éventuellement par ressource)
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
                $this->jsonError(
                    'Pas assez de tentatives avec AES pour la vue globale ('
                    . count($attempts) . ' trouvées, minimum 5).'
                );
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
            $this->jsonError('Erreur serveur : ' . $e->getMessage(), 500);
        }
    }

    /**
     * API endpoint : POST /api/ia/micro
     * Vue Micro : clustering K-Means + t-SNE pour UN exercice, avec trajectoires.
     */
    public function micro(): void
    {
        if (!$this->authService->isAuthenticated()) {
            $this->jsonError('Non authentifié', 401);
            return;
        }

        if (!$this->isPost()) {
            $this->jsonError('Méthode non autorisée', 405);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $exerciseId = (int)($input['exercise_id'] ?? 0);
            $nClusters  = (int)($input['n_clusters']  ?? 8);
            $perplexity = (int)($input['perplexity']  ?? 30);

            if ($exerciseId <= 0) {
                $this->jsonError('exercise_id invalide');
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
                $this->jsonError(
                    'Pas assez de tentatives avec AES pour cet exercice ('
                    . count($attempts) . ' trouvées, minimum 5).'
                );
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
            $this->jsonError('Erreur serveur : ' . $e->getMessage(), 500);
        }
    }

    /**
     * Exécute le script Python clustering_pipeline.py avec un payload JSON via stdin.
     * Retourne le résultat décodé (array).
     */
    private function runPythonPipeline(string $payload): array
    {
        $projectRoot = realpath(__DIR__ . '/../../');
        $scriptPath  = $projectRoot . '/scripts/clustering_pipeline.py';

        $pythonPath = $this->findPython($projectRoot);

        if ($pythonPath === null) {
            return [
                'success' => false,
                'message' => 'Aucun interpréteur Python avec gensim trouvé. '
                    . 'Vérifiez que gensim est installé : python3 -m pip install --user gensim',
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
                'message' => 'Impossible de lancer le script Python (commande: ' . $cmd . ')',
            ];
        }

        fwrite($pipes[0], $payload);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        // Chercher le JSON dans stdout puis stderr
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
                'message' => 'Le script Python n\'a pas renvoyé de JSON valide (exit code: '
                    . $exitCode . '). Sortie: ' . substr($rawOutput, 0, 800),
            ];
        }

        return json_decode($jsonStr, true);
    }

    /**
     * Trouve un exécutable Python capable d'importer gensim.
     * Teste les venv locaux, puis /usr/bin/python3, puis python3/python du PATH.
     * Passe PYTHONUSERBASE pour que les packages pip --user soient visibles.
     */
    private function findPython(string $projectRoot): ?string
    {
        // Candidats avec chemin absolu (file_exists testable)
        $absoluteCandidates = [
            // Venv dans scripts/
            $projectRoot . '/scripts/venv/bin/python3',
            $projectRoot . '/scripts/venv/bin/python',
            $projectRoot . '/scripts/venv/Scripts/python.exe',
            // Venv à la racine du projet
            $projectRoot . '/venv/bin/python3',
            $projectRoot . '/venv/bin/python',
            // Venv dans le home (Alwaysdata)
            '/home/studtraj/venv/bin/python3',
            '/home/studtraj/www/venv/bin/python3',
            '/home/studtraj/www/SAE/scripts/venv/bin/python3',
            // Python système (chemin absolu)
            '/usr/bin/python3',
            // Windows
            'C:\\xampp\\htdocs\\BUT3\\venv\\Scripts\\python.exe',
        ];

        // D'abord tester les chemins absolus qui existent sur le disque
        foreach ($absoluteCandidates as $candidate) {
            if (file_exists($candidate) && $this->pythonHasGensim($candidate)) {
                return $candidate;
            }
        }

        // Ensuite tester les commandes du PATH (pas testables avec file_exists)
        foreach (['python3', 'python'] as $candidate) {
            if ($this->pythonHasGensim($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Vérifie qu'un exécutable Python donné peut importer gensim.
     * Injecte PYTHONUSERBASE pour couvrir les installations pip --user.
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
