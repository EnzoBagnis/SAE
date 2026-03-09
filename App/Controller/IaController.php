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
            "SELECT eval_set, COUNT(*) AS count FROM attempts WHERE eval_set IS NOT NULL GROUP BY eval_set ORDER BY eval_set"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Ressources
        $resources = $pdo->query(
            "SELECT ressource_id, ressource_name FROM ressources ORDER BY ressource_name ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Exercices (tous, pour le sélecteur dynamique côté JS)
        $exercises = $pdo->query(
            "SELECT e.exercice_id, e.exercice_name, e.ressource_id, r.ressource_name,
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
     * TEMPORAIRE — diagnostic Python sur le serveur.
     * GET /api/ia/debug-python → JSON avec les chemins testés et le Python utilisé.
     * À SUPPRIMER après résolution du problème.
     */
    public function debugPython(): void
    {
        $projectRoot = realpath(__DIR__ . '/../../');

        $possiblePythonPaths = [
            $projectRoot . '/scripts/venv/bin/python3',
            $projectRoot . '/scripts/venv/bin/python',
            $projectRoot . '/venv/bin/python3',
            $projectRoot . '/venv/bin/python',
            '/home/studtraj/venv/bin/python3',
            '/home/studtraj/www/venv/bin/python3',
            '/home/studtraj/www/SAE/scripts/venv/bin/python3',
            $projectRoot . '/scripts/venv/Scripts/python.exe',
        ];

        $results = [];
        $chosenPath = 'python';
        foreach ($possiblePythonPaths as $p) {
            $exists = file_exists($p);
            $results[] = ['path' => $p, 'exists' => $exists];
            if ($exists && $chosenPath === 'python') {
                $chosenPath = $p;
            }
        }

        // Tester which python3 / which python
        $whichPython3 = trim(shell_exec('which python3 2>&1') ?? '');
        $whichPython  = trim(shell_exec('which python 2>&1') ?? '');

        // Tester si gensim est disponible avec le python choisi
        $testCmd = escapeshellarg($chosenPath) . ' -c "import gensim; print(gensim.__version__)" 2>&1';
        $gensimTest = trim(shell_exec($testCmd) ?? '');

        // Lister le contenu de scripts/venv/ s'il existe
        $venvDir = $projectRoot . '/scripts/venv';
        $venvContents = is_dir($venvDir) ? scandir($venvDir) : 'DOSSIER INEXISTANT';
        $venvBinDir = $venvDir . '/bin';
        $venvBinContents = is_dir($venvBinDir) ? scandir($venvBinDir) : 'DOSSIER bin/ INEXISTANT';

        $this->jsonResponse([
            'project_root'      => $projectRoot,
            'script_exists'     => file_exists($projectRoot . '/scripts/clustering_pipeline.py'),
            'candidates'        => $results,
            'chosen_python'     => $chosenPath,
            'which_python3'     => $whichPython3,
            'which_python'      => $whichPython,
            'gensim_test'       => $gensimTest,
            'venv_contents'     => $venvContents,
            'venv_bin_contents' => $venvBinContents,
        ]);
    }

    /**
     * API endpoint : POST /api/ia/clustering
     * PHP extrait les données de la BD, les passe au script Python via stdin.
     * Le script Python fait Doc2Vec → KMeans → t-SNE → image base64.
     */
    public function clustering(): void
    {
        // Pour les endpoints API, renvoyer du JSON au lieu de rediriger
        if (!$this->authService->isAuthenticated()) {
            $this->jsonError('Non authentifié', 401);
            return;
        }

        if (!$this->isPost()) {
            $this->jsonError('Méthode non autorisée', 405);
            return;
        }

        try {
            // Lire le body JSON
            $input = json_decode(file_get_contents('php://input'), true);
            $exerciseId = (int)($input['exercise_id'] ?? 0);
            $nClusters  = (int)($input['n_clusters']  ?? 8);
            $perplexity = (int)($input['perplexity']  ?? 30);

            if ($exerciseId <= 0) {
                $this->jsonError('exercise_id invalide');
                return;
            }

            // ── Extraire les données depuis la BD ──
            $pdo = DatabaseConnection::getInstance()->getConnection();

            $stmt = $pdo->prepare("
                SELECT
                    a.attempt_id,
                    a.aes2,
                    a.eval_set,
                    a.correct,
                    a.user_id,
                    a.exercice_id,
                    e.exercice_name AS exercise_name
                FROM attempts a
                JOIN exercices e ON a.exercice_id = e.exercice_id
                WHERE a.exercice_id = :eid
                  AND a.aes2 IS NOT NULL
                  AND a.aes2 != ''
                ORDER BY a.attempt_id
            ");
            $stmt->execute(['eid' => $exerciseId]);
            $attempts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (count($attempts) < 5) {
                $this->jsonError('Pas assez de tentatives avec AES pour cet exercice (' . count($attempts) . ' trouvées, minimum 5).');
                return;
            }

            // Préparer le payload JSON à envoyer au script Python via stdin
            $payload = json_encode([
                'attempts'    => $attempts,
                'n_clusters'  => $nClusters,
                'perplexity'  => $perplexity,
                'exercise_id' => $exerciseId,
            ], JSON_UNESCAPED_UNICODE);

            // ── Chemins Python ──
            $projectRoot = realpath(__DIR__ . '/../../');
            $scriptPath  = $projectRoot . '/scripts/clustering_pipeline.py';

            // Chercher le venv Python dans plusieurs emplacements possibles
            $possiblePythonPaths = [
                // Linux : venv dans scripts/
                $projectRoot . '/scripts/venv/bin/python3',
                $projectRoot . '/scripts/venv/bin/python',
                // Linux : venv à la racine du projet
                $projectRoot . '/venv/bin/python3',
                $projectRoot . '/venv/bin/python',
                // Linux : venv dans le home (Alwaysdata / hébergeur)
                '/home/studtraj/venv/bin/python3',
                '/home/studtraj/www/venv/bin/python3',
                '/home/studtraj/www/SAE/scripts/venv/bin/python3',
                // Windows : venv dans scripts/
                $projectRoot . '/scripts/venv/Scripts/python.exe',
                // Windows : venv externe
                'C:\\xampp\\htdocs\\BUT3\\venv\\Scripts\\python.exe',
                // python3 système (peut avoir les modules)
                'python3',
            ];

            $pythonPath = 'python'; // fallback ultime
            foreach ($possiblePythonPaths as $candidate) {
                if (file_exists($candidate)) {
                    $pythonPath = $candidate;
                    break;
                }
            }

            if (!file_exists($scriptPath)) {
                $this->jsonError('Script clustering_pipeline.py introuvable : ' . $scriptPath, 500);
                return;
            }

            // ── Exécuter via proc_open pour pouvoir écrire sur stdin ──
            $cmd = sprintf(
                '%s %s --from-stdin',
                escapeshellarg($pythonPath),
                escapeshellarg($scriptPath)
            );

            $descriptors = [
                0 => ['pipe', 'r'],  // stdin
                1 => ['pipe', 'w'],  // stdout
                2 => ['pipe', 'w'],  // stderr
            ];

            $process = proc_open($cmd, $descriptors, $pipes);

            if (!is_resource($process)) {
                $this->jsonError('Impossible de lancer le script Python (commande: ' . $cmd . ')', 500);
                return;
            }

            // Écrire les données sur stdin et fermer
            fwrite($pipes[0], $payload);
            fclose($pipes[0]);

            // Lire stdout
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // Lire stderr
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $exitCode = proc_close($process);

            // Chercher le JSON dans stdout d'abord, puis dans stderr
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
                $this->jsonError(
                    'Le script Python n\'a pas renvoyé de JSON valide (exit code: ' . $exitCode . '). Sortie: ' . substr($rawOutput, 0, 800),
                    500
                );
                return;
            }

            $result = json_decode($jsonStr, true);
            $this->jsonResponse($result);

        } catch (\Throwable $e) {
            $this->jsonError('Erreur serveur : ' . $e->getMessage(), 500);
        }
    }
}
