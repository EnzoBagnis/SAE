<?php

/**
 * Service pour interagir avec code2aes2vec (Python)
 * Génère les vecteurs de grande dimension à partir des programmes étudiants
 */
class Code2VecService {

    private $pythonPath;
    private $scriptsPath;
    private $dataPath;
    private $modelsPath;

    public function __construct() {
        $this->pythonPath = '/usr/bin/python3';
        $this->scriptsPath = __DIR__ . '/../python_scripts/';
        $this->dataPath = __DIR__ . '/../data/';
        $this->modelsPath = $this->dataPath . 'models/';
    }

    /**
     * Exporte un dataset au format JSON compatible avec code2aes2vec
     * @param int $datasetId ID du dataset dans la BD
     * @return array Résultat avec chemin du fichier exporté
     */
    public function exportDatasetToJSON($datasetId) {
        $pdo = Database::getConnection();

        // Récupérer toutes les tentatives du dataset avec les infos des exercices
        $stmt = $pdo->prepare("
            SELECT 
                a.attempt_id,
                s.student_identifier as user,
                e.exo_name as exercise_name,
                a.extension,
                a.submission_date as date,
                a.correct,
                a.upload,
                a.eval_set,
                a.aes0,
                a.aes1,
                a.aes2,
                e.funcname
            FROM attempts a
            JOIN students s ON a.student_id = s.student_id
            JOIN exercises e ON a.exercise_id = e.exercise_id
            WHERE s.dataset_id = ?
            ORDER BY a.submission_date ASC
        ");

        $stmt->execute([$datasetId]);
        $attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($attempts)) {
            return [
                'success' => false,
                'error' => 'Aucune tentative trouvée pour ce dataset'
            ];
        }

        // Formater pour code2aes2vec (compatible avec NewCaledonia format)
        $formattedAttempts = [];
        foreach ($attempts as $attempt) {
            $formattedAttempts[] = [
                'user' => $attempt['user'],
                'exercise_name' => $attempt['exercise_name'],
                'extension' => $attempt['extension'],
                'date' => $attempt['date'],
                'correct' => (int)$attempt['correct'],
                'upload' => $attempt['upload'],
                'eval_set' => $attempt['eval_set'] ?? 'training',
                'aes0' => $attempt['aes0'] ?? '',
                'aes1' => $attempt['aes1'] ?? '',
                'aes2' => $attempt['aes2'] ?? ''
            ];
        }

        // Sauvegarder le fichier JSON
        $outputFile = $this->dataPath . "uploads/dataset_{$datasetId}_export.json";

        if (!is_dir($this->dataPath . 'uploads/')) {
            mkdir($this->dataPath . 'uploads/', 0777, true);
        }

        file_put_contents($outputFile, json_encode($formattedAttempts, JSON_PRETTY_PRINT));

        return [
            'success' => true,
            'file' => $outputFile,
            'nb_attempts' => count($formattedAttempts)
        ];
    }

    /**
     * Exporte les exercices d'un dataset au format compatible code2aes2vec
     */
    public function exportExercisesToJSON($datasetId) {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT 
                e.exo_name,
                e.funcname,
                e.solution,
                e.description,
                GROUP_CONCAT(
                    CONCAT('[', tc.input_data, ']')
                    ORDER BY tc.test_order
                    SEPARATOR '|||'
                ) as entries
            FROM exercises e
            LEFT JOIN test_cases tc ON e.exercise_id = tc.exercise_id
            WHERE e.dataset_id = ?
            GROUP BY e.exercise_id, e.exo_name, e.funcname, e.solution, e.description
        ");

        $stmt->execute([$datasetId]);
        $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Formater pour code2aes2vec
        $formattedExercises = [];
        foreach ($exercises as $exercise) {
            $entries = [];
            if ($exercise['entries']) {
                $entriesParts = explode('|||', $exercise['entries']);
                foreach ($entriesParts as $entry) {
                    $entries[] = json_decode($entry, true);
                }
            }

            $formattedExercises[] = [
                'exo_name' => $exercise['exo_name'],
                'funcname' => $exercise['funcname'],
                'solution' => $exercise['solution'] ?? '',
                'description' => $exercise['description'] ?? '',
                'entries' => $entries
            ];
        }

        $outputFile = $this->dataPath . "uploads/dataset_{$datasetId}_exercises.json";
        file_put_contents($outputFile, json_encode($formattedExercises, JSON_PRETTY_PRINT));

        return [
            'success' => true,
            'file' => $outputFile,
            'nb_exercises' => count($formattedExercises)
        ];
    }

    /**
     * Génère les AES pour toutes les tentatives d'un dataset
     * Met à jour la colonne aes2 dans la table attempts
     */
    public function generateAES($datasetId) {
        // Exporter les données
        $attemptsExport = $this->exportDatasetToJSON($datasetId);
        $exercisesExport = $this->exportExercisesToJSON($datasetId);

        if (!$attemptsExport['success'] || !$exercisesExport['success']) {
            return [
                'success' => false,
                'error' => 'Erreur lors de l\'export des données'
            ];
        }

        $outputFile = $this->dataPath . "uploads/dataset_{$datasetId}_with_aes.json";

        // Appel au script Python pour générer les AES
        $command = sprintf(
            '%s %s %s %s %s 2>&1',
            escapeshellcmd($this->pythonPath),
            escapeshellarg($this->scriptsPath . 'generate_aes.py'),
            escapeshellarg($attemptsExport['file']),
            escapeshellarg($exercisesExport['file']),
            escapeshellarg($outputFile)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la génération des AES',
                'details' => implode("\n", $output)
            ];
        }

        // Importer les AES dans la base de données
        $this->importAESToDB($datasetId, $outputFile);

        return [
            'success' => true,
            'aes_file' => $outputFile,
            'message' => 'AES générés avec succès'
        ];
    }

    /**
     * Importe les AES depuis le fichier JSON vers la base de données
     */
    private function importAESToDB($datasetId, $jsonFile) {
        $data = json_decode(file_get_contents($jsonFile), true);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            UPDATE attempts a
            JOIN students s ON a.student_id = s.student_id
            JOIN exercises e ON a.exercise_id = e.exercise_id
            SET a.aes2 = ?
            WHERE s.dataset_id = ?
              AND s.student_identifier = ?
              AND e.exo_name = ?
              AND a.submission_date = ?
        ");

        foreach ($data as $attempt) {
            if (isset($attempt['aes2']) && !empty($attempt['aes2'])) {
                $stmt->execute([
                    $attempt['aes2'],
                    $datasetId,
                    $attempt['user'],
                    $attempt['exercise_name'],
                    $attempt['date']
                ]);
            }
        }
    }

    /**
     * Génère les vecteurs de grande dimension à partir des AES
     */
    public function inferVectors($datasetId) {
        $modelFile = $this->modelsPath . 'pretrained_code2vec.model';
        $aesFile = $this->dataPath . "uploads/dataset_{$datasetId}_with_aes.json";
        $outputFile = $this->dataPath . "vectors/dataset_{$datasetId}_vectors.json";

        if (!file_exists($modelFile)) {
            return [
                'success' => false,
                'error' => 'Modèle pré-entraîné non trouvé'
            ];
        }

        if (!file_exists($aesFile)) {
            return [
                'success' => false,
                'error' => 'Fichier AES non trouvé. Générez d\'abord les AES.'
            ];
        }

        if (!is_dir($this->dataPath . 'vectors/')) {
            mkdir($this->dataPath . 'vectors/', 0777, true);
        }

        // Appel au script Python pour inférer les vecteurs
        $command = sprintf(
            '%s %s %s %s %s 2>&1',
            escapeshellcmd($this->pythonPath),
            escapeshellarg($this->scriptsPath . 'infer_vectors.py'),
            escapeshellarg($modelFile),
            escapeshellarg($aesFile),
            escapeshellarg($outputFile)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            return [
                'success' => false,
                'error' => 'Erreur lors de l\'inférence des vecteurs',
                'details' => implode("\n", $output)
            ];
        }

        $vectors = json_decode(file_get_contents($outputFile), true);

        return [
            'success' => true,
            'vectors_file' => $outputFile,
            'vectors' => $vectors,
            'dimension' => count($vectors[0] ?? []),
            'count' => count($vectors)
        ];
    }

    /**
     * Vérifie l'état du traitement
     */
    public function getProcessingStatus($datasetId) {
        $statusFile = $this->dataPath . "uploads/dataset_{$datasetId}_status.json";

        if (file_exists($statusFile)) {
            return json_decode(file_get_contents($statusFile), true);
        }

        return [
            'status' => 'not_started',
            'progress' => 0
        ];
    }

    /**
     * Lance le traitement complet en arrière-plan
     */
    public function processInBackground($datasetId) {
        $statusFile = $this->dataPath . "uploads/dataset_{$datasetId}_status.json";
        file_put_contents($statusFile, json_encode([
            'status' => 'processing',
            'progress' => 0,
            'step' => 'export',
            'started_at' => date('Y-m-d H:i:s')
        ]));

        $command = sprintf(
            '%s %s %d > /dev/null 2>&1 &',
            escapeshellcmd($this->pythonPath),
            escapeshellarg($this->scriptsPath . 'process_complete.py'),
            $datasetId
        );

        exec($command);

        return [
            'success' => true,
            'message' => 'Traitement lancé en arrière-plan'
        ];
    }
}