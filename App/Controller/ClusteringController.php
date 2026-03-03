<?php

namespace App\Controller;

use Core\Controller\AbstractController;
use App\Model\AuthenticationService;
use Core\Config\EnvLoader;
use Core\Service\SessionService;

/**
 * Clustering Controller
 * Provides an API endpoint to trigger the Python clustering pipeline
 * (Doc2Vec + KMeans + t-SNE) and return the result as JSON.
 */
class ClusteringController extends AbstractController
{
    private AuthenticationService $authService;

    public function __construct()
    {
        $this->authService = new AuthenticationService(new SessionService());
    }

    /**
     * POST /api/clustering/generate
     *
     * Expects JSON body: { "exercise_id": int|null, "resource_id": int|null, "n_clusters": int }
     * Calls the Python pipeline script and returns the result (image base64 + metadata).
     *
     * @return void
     */
    public function generate(): void
    {
        $this->authService->requireAuth('/auth/login');

        // Parse JSON body
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $exerciseId = isset($input['exercise_id']) ? (int)$input['exercise_id'] : null;
        $resourceId = isset($input['resource_id']) ? (int)$input['resource_id'] : null;
        $nClusters  = isset($input['n_clusters'])  ? (int)$input['n_clusters']  : 8;

        if ($exerciseId === null && $resourceId === null) {
            $this->jsonError('Veuillez spécifier exercise_id ou resource_id.', 400);
            return;
        }

        // Clamp clusters
        $nClusters = max(2, min($nClusters, 20));

        // Database credentials from .env
        $dbHost = EnvLoader::get('DB_HOST', 'localhost');
        $dbName = EnvLoader::get('DB_NAME', '');
        $dbUser = EnvLoader::get('DB_USER', '');
        $dbPass = EnvLoader::get('DB_PASS', '');

        // Build command
        $pythonPath = $this->findPython();
        $scriptPath = realpath(__DIR__ . '/../../scripts/clustering_pipeline.py');

        if (!$scriptPath || !file_exists($scriptPath)) {
            $this->jsonError('Script de clustering introuvable.', 500);
            return;
        }

        $cmd = sprintf(
            '%s %s --db_host %s --db_name %s --db_user %s --db_pass %s --n_clusters %d',
            escapeshellarg($pythonPath),
            escapeshellarg($scriptPath),
            escapeshellarg($dbHost),
            escapeshellarg($dbName),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            $nClusters
        );

        if ($exerciseId !== null) {
            $cmd .= sprintf(' --exercise_id %d', $exerciseId);
        }
        if ($resourceId !== null) {
            $cmd .= sprintf(' --resource_id %d', $resourceId);
        }

        // Redirect stderr to a temp file for logging
        $stderrFile = sys_get_temp_dir() . '/clustering_stderr_' . uniqid() . '.txt';
        $cmd .= ' 2>' . escapeshellarg($stderrFile);

        // Execute
        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        $stdout = implode("\n", $output);
        $stderr = file_exists($stderrFile) ? file_get_contents($stderrFile) : '';
        @unlink($stderrFile);

        if ($returnCode !== 0) {
            error_log('[ClusteringController] Python script failed (code ' . $returnCode . '): ' . $stderr);
            $this->jsonError(
                'Erreur lors de l\'exécution du pipeline de clustering. ' .
                ($stderr ? 'Détails: ' . substr($stderr, 0, 500) : ''),
                500
            );
            return;
        }

        // Parse JSON output
        $result = json_decode($stdout, true);

        if ($result === null) {
            error_log('[ClusteringController] Invalid JSON output: ' . substr($stdout, 0, 1000));
            $this->jsonError('Réponse invalide du script de clustering.', 500);
            return;
        }

        if (!($result['success'] ?? false)) {
            $this->jsonError($result['error'] ?? 'Erreur inconnue du pipeline.', 422);
            return;
        }

        $this->jsonSuccess($result, 'Clustering généré avec succès.');
    }

    /**
     * Find the Python executable path.
     *
     * @return string Python executable path
     */
    private function findPython(): string
    {
        // Check .env first
        $envPython = EnvLoader::get('PYTHON_PATH', '');
        if ($envPython !== '' && (file_exists($envPython) || $this->commandExists($envPython))) {
            return $envPython;
        }

        // Sur Linux : python3 système en priorité (évite le virtualenv lourd)
        if (PHP_OS_FAMILY !== 'Windows' && $this->commandExists('python3')) {
            return 'python3';
        }

        // Virtualenv du projet (Windows XAMPP)
        $venvPython = realpath(__DIR__ . '/../../scripts/venv/Scripts/python.exe');
        if ($venvPython && file_exists($venvPython)) {
            return $venvPython;
        }
        $venvPythonLinux = realpath(__DIR__ . '/../../scripts/venv/bin/python');
        if ($venvPythonLinux && file_exists($venvPythonLinux)) {
            return $venvPythonLinux;
        }

        // Try common paths on Windows (XAMPP context)
        $candidates = ['python', 'python3', 'py'];

        if (PHP_OS_FAMILY === 'Windows') {
            $candidates = array_merge($candidates, [
                'C:\\Python312\\python.exe',
                'C:\\Python311\\python.exe',
                'C:\\Python310\\python.exe',
                'C:\\Python39\\python.exe',
            ]);
        }

        foreach ($candidates as $candidate) {
            if ($this->commandExists($candidate)) {
                return $candidate;
            }
        }

        // Fallback
        return PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
    }

    /**
     * Check if a command exists/is accessible.
     *
     * @param string $command
     * @return bool
     */
    private function commandExists(string $command): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $check = shell_exec('where ' . escapeshellarg($command) . ' 2>NUL');
        } else {
            $check = shell_exec('which ' . escapeshellarg($command) . ' 2>/dev/null');
        }
        return !empty(trim($check ?? ''));
    }
}

