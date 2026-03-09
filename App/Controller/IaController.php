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
        $totalExercises = (int)$pdo->query("SELECT COUNT(*) FROM exercises")->fetchColumn();
        $totalStudents  = (int)$pdo->query("SELECT COUNT(DISTINCT student_id) FROM attempts")->fetchColumn();

        // Répartition par eval_set
        $evalSets = $pdo->query(
            "SELECT eval_set, COUNT(*) AS count FROM attempts WHERE eval_set IS NOT NULL GROUP BY eval_set ORDER BY eval_set"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Ressources
        $resources = $pdo->query(
            "SELECT resource_id, resource_name FROM resources ORDER BY resource_name ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Exercices (tous, pour le sélecteur dynamique côté JS)
        $exercises = $pdo->query(
            "SELECT e.exercise_id, e.exo_name, e.resource_id, r.resource_name,
                    COUNT(a.attempt_id) AS nb_attempts
             FROM exercises e
             LEFT JOIN resources r ON e.resource_id = r.resource_id
             LEFT JOIN attempts a  ON a.exercise_id = e.exercise_id AND a.aes2 IS NOT NULL AND a.aes2 != ''
             GROUP BY e.exercise_id
             ORDER BY r.resource_name ASC, e.exo_name ASC"
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
     * API endpoint : POST /api/ia/clustering
     * Appelle le script Python clustering_pipeline.py et renvoie le JSON résultat.
     */
    public function clustering(): void
    {
        $this->authService->requireAuth('/auth/login');

        if (!$this->isPost()) {
            $this->jsonError('Méthode non autorisée', 405);
            return;
        }

        // Lire le body JSON
        $input = json_decode(file_get_contents('php://input'), true);
        $exerciseId = (int)($input['exercise_id'] ?? 0);
        $nClusters  = (int)($input['n_clusters']  ?? 8);
        $perplexity = (int)($input['perplexity']  ?? 30);

        if ($exerciseId <= 0) {
            $this->jsonError('exercise_id invalide');
            return;
        }

        // Chemin du script Python et du venv
        $projectRoot = realpath(__DIR__ . '/../../');
        $scriptPath  = $projectRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'clustering_pipeline.py';
        $pythonPath  = $projectRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe';

        // Fallback si pas sur Windows
        if (!file_exists($pythonPath)) {
            $pythonPath = $projectRoot . '/scripts/venv/bin/python3';
        }
        // Fallback système
        if (!file_exists($pythonPath)) {
            $pythonPath = 'python';
        }

        if (!file_exists($scriptPath)) {
            $this->jsonError('Script clustering_pipeline.py introuvable', 500);
            return;
        }

        // Construire la commande
        $cmd = sprintf(
            '%s %s --exercise_id %d --n_clusters %d --perplexity %d 2>&1',
            escapeshellarg($pythonPath),
            escapeshellarg($scriptPath),
            $exerciseId,
            $nClusters,
            $perplexity
        );

        // Exécuter
        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        $rawOutput = implode("\n", $output);

        // Chercher le JSON dans la sortie (ignorer les warnings éventuels)
        $jsonStart = strpos($rawOutput, '{');
        if ($jsonStart === false) {
            $this->jsonError('Le script Python n\'a pas renvoyé de JSON valide. Sortie: ' . substr($rawOutput, 0, 500), 500);
            return;
        }

        $jsonStr = substr($rawOutput, $jsonStart);
        $result  = json_decode($jsonStr, true);

        if ($result === null) {
            $this->jsonError('JSON invalide du script Python. Sortie: ' . substr($rawOutput, 0, 500), 500);
            return;
        }

        $this->jsonResponse($result);
    }
}
