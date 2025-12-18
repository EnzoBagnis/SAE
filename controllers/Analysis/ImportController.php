<?php
namespace Controllers\Analysis;

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../models/Database.php';

/**
 * ImportController - Gère l'import de données JSON
 * Exercices et tentatives d'élèves
 */
class ImportController extends \BaseController {

    private $db;

    public function __construct() {
        // Connexion à la base de données
        $this->db = Database::getConnection();
    }

    /**
     * Import d'exercices depuis JSON
     * POST /api/exercises/import
     */
    public function importExercises() {
        header('Content-Type: application/json');

        // Vérifier la session
        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }

        $user_id = $_SESSION['id'];

        try {
            // Récupérer les données JSON
            $json_data = file_get_contents('php://input');

            // Log pour debug
            error_log("Import exercises - Raw data length: " . strlen($json_data));

            $data = json_decode($json_data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON decode error: " . json_last_error_msg());
                throw new \Exception('JSON invalide: ' . json_last_error_msg());
            }

            // Valider la structure
            if (!isset($data['exercises']) || !is_array($data['exercises'])) {
                error_log("Invalid structure - keys: " . implode(', ', array_keys($data)));
                throw new \Exception('Format JSON invalide - "exercises" array requis');
            }

            $exercises = $data['exercises'];
            error_log("Processing " . count($exercises) . " exercises");

            // Récupérer l'ID de la ressource depuis l'URL
            $url_resource_id = $_GET['id'] ?? null;

            $success_count = 0;
            $error_count = 0;
            $errors = [];

            $this->db->beginTransaction();

            foreach ($exercises as $index => $exercise) {
                try {
                    if ($url_resource_id) {
                        $resource_id = $url_resource_id;
                    } else {
                        // Créer ou récupérer la ressource
                        $resource_id = $this->getOrCreateResource(
                            $exercise['tp_id'] ?? 'TP_Unknown',
                            $user_id,
                            $exercise['tp_description'] ?? null
                        );
                    }

                    // Insérer l'exercice
                    $stmt = $this->db->prepare("
                        INSERT INTO exercises (
                            resource_id, exo_name, funcname, solution, description, difficulte
                        ) VALUES (?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            funcname = VALUES(funcname),
                            solution = VALUES(solution),
                            description = VALUES(description),
                            difficulte = VALUES(difficulte)
                    ");

                    $stmt->execute([
                        $resource_id,
                        $exercise['title'] ?? $exercise['name'] ?? 'Exercise_' . $index,
                        $exercise['funcname'] ?? null,
                        $exercise['solution'] ?? null,
                        $exercise['description'] ?? null,
                        $this->mapDifficulty($exercise['difficulty'] ?? 'moyen')
                    ]);

                    $exercise_id = $this->db->lastInsertId();
                    if ($exercise_id == 0) {
                        // Récupérer l'ID si UPDATE
                        $stmt = $this->db->prepare("
                            SELECT exercise_id FROM exercises 
                            WHERE resource_id = ? AND exo_name = ?
                        ");
                        $stmt->execute([$resource_id, $exercise['title'] ?? $exercise['name']]);
                        $exercise_id = $stmt->fetchColumn();
                    }

                    // Insérer les test cases si présents
                    if (isset($exercise['test_cases']) && is_array($exercise['test_cases'])) {
                        $this->insertTestCases($exercise_id, $exercise['test_cases']);
                    }

                    $success_count++;

                } catch (\Exception $e) {
                    $error_count++;
                    $errors[] = "Exercice #$index: " . $e->getMessage();
                }
            }

            // Mettre à jour les statistiques du dataset si nécessaire
            if (isset($data['dataset_id'])) {
                $this->updateDatasetStats($data['dataset_id']);
            }

            $this->db->commit();

            error_log("Import completed - Success: $success_count, Errors: $error_count");

            echo json_encode([
                'success' => true,
                'count' => count($exercises),
                'success_count' => $success_count,
                'error_count' => $error_count,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Import error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage(), 'trace' => $e->getFile() . ':' . $e->getLine()]);
        }
    }

    /**
     * Import de tentatives depuis JSON
     * POST /api/attempts/import
     */
    public function importAttempts() {
        header('Content-Type: application/json');

        // Vérifier la session
        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }

        $user_id = $_SESSION['id'];

        try {
            // Récupérer les données JSON
            $json_data = file_get_contents('php://input');
            $data = json_decode($json_data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON invalide');
            }

            // Valider la structure
            if (!isset($data['attempts']) || !is_array($data['attempts'])) {
                throw new \Exception('Format JSON invalide - "attempts" array requis');
            }

            // Récupérer l'ID de la ressource depuis l'URL
            $url_resource_id = $_GET['id'] ?? null;

            // Créer ou récupérer le dataset
            $dataset_id = $this->getOrCreateDataset(
                $data['dataset_info'] ?? [],
                $user_id
            );

            $attempts = $data['attempts'];
            $success_count = 0;
            $error_count = 0;
            $errors = [];

            $this->db->beginTransaction();

            foreach ($attempts as $index => $attempt) {
                try {
                    // Créer ou récupérer l'étudiant
                    $student_id = $this->getOrCreateStudent(
                        $dataset_id,
                        $attempt['student_identifier'] ?? 'student_' . $index
                    );

                    // Trouver l'exercice
                    $exercise_id = $this->findExerciseByName(
                        $attempt['exercise_name'] ?? $attempt['exo_name'] ?? null,
                        $url_resource_id
                    );

                    if (!$exercise_id) {
                        throw new \Exception('Exercice non trouvé: ' . ($attempt['exercise_name'] ?? 'N/A'));
                    }

                    // Insérer la tentative
                    $stmt = $this->db->prepare("
                        INSERT INTO attempts (
                            student_id, exercise_id, submission_date, extension,
                            correct, upload, eval_set
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $student_id,
                        $exercise_id,
                        $attempt['submission_date'] ?? date('Y-m-d H:i:s'),
                        $attempt['extension'] ?? 'py',
                        $attempt['correct'] ? 1 : 0,
                        $attempt['code'] ?? $attempt['upload'] ?? '',
                        $attempt['eval_set'] ?? null
                    ]);

                    $success_count++;

                } catch (\Exception $e) {
                    $error_count++;
                    $errors[] = "Tentative #$index: " . $e->getMessage();
                }
            }

            // Mettre à jour les statistiques du dataset
            $this->updateDatasetStats($dataset_id);

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'dataset_id' => $dataset_id,
                'count' => count($attempts),
                'success_count' => $success_count,
                'error_count' => $error_count,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // ========== MÉTHODES UTILITAIRES ==========

    /**
     * Créer ou récupérer une ressource
     */
    private function getOrCreateResource($resource_name, $user_id, $description = null) {
        // Chercher si existe
        $stmt = $this->db->prepare("
            SELECT resource_id FROM resources 
            WHERE resource_name = ? AND owner_user_id = ?
        ");
        $stmt->execute([$resource_name, $user_id]);
        $resource_id = $stmt->fetchColumn();

        if ($resource_id) {
            return $resource_id;
        }

        // Créer
        $stmt = $this->db->prepare("
            INSERT INTO resources (owner_user_id, resource_name, description)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $resource_name, $description]);

        return $this->db->lastInsertId();
    }

    /**
     * Créer ou récupérer un dataset
     */
    private function getOrCreateDataset($dataset_info, $user_id) {
        $nom_dataset = $dataset_info['nom_dataset'] ?? 'Dataset_' . date('Y-m-d_H-i-s');

        // Chercher si existe
        $stmt = $this->db->prepare("
            SELECT dataset_id FROM datasets WHERE nom_dataset = ?
        ");
        $stmt->execute([$nom_dataset]);
        $dataset_id = $stmt->fetchColumn();

        if ($dataset_id) {
            return $dataset_id;
        }

        // Créer
        $stmt = $this->db->prepare("
            INSERT INTO datasets (enseignant_id, nom_dataset, description, pays, annee)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $nom_dataset,
            $dataset_info['description'] ?? null,
            $dataset_info['pays'] ?? null,
            $dataset_info['annee'] ?? date('Y')
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Créer ou récupérer un étudiant
     */
    private function getOrCreateStudent($dataset_id, $student_identifier) {
        // Chercher si existe
        $stmt = $this->db->prepare("
            SELECT student_id FROM students 
            WHERE dataset_id = ? AND student_identifier = ?
        ");
        $stmt->execute([$dataset_id, $student_identifier]);
        $student_id = $stmt->fetchColumn();

        if ($student_id) {
            return $student_id;
        }

        // Créer
        $stmt = $this->db->prepare("
            INSERT INTO students (dataset_id, student_identifier)
            VALUES (?, ?)
        ");
        $stmt->execute([$dataset_id, $student_identifier]);

        return $this->db->lastInsertId();
    }

    /**
     * Trouver un exercice par son nom
     */
    private function findExerciseByName($exo_name, $resource_id = null) {
        if (!$exo_name) return null;

        if ($resource_id) {
            $stmt = $this->db->prepare("
                SELECT exercise_id FROM exercises 
                WHERE exo_name = ? AND resource_id = ? 
                LIMIT 1
            ");
            $stmt->execute([$exo_name, $resource_id]);
        } else {
            $stmt = $this->db->prepare("
                SELECT exercise_id FROM exercises WHERE exo_name = ? LIMIT 1
            ");
            $stmt->execute([$exo_name]);
        }
        return $stmt->fetchColumn();
    }

    /**
     * Insérer les test cases
     */
    private function insertTestCases($exercise_id, $test_cases) {
        // Supprimer les anciens test cases
        $stmt = $this->db->prepare("DELETE FROM test_cases WHERE exercise_id = ?");
        $stmt->execute([$exercise_id]);

        // Insérer les nouveaux
        $stmt = $this->db->prepare("
            INSERT INTO test_cases (exercise_id, input_data, expected_output, test_order)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($test_cases as $test) {
            $stmt->execute([
                $exercise_id,
                json_encode($test['input'] ?? []),
                json_encode($test['expected'] ?? $test['expected_output'] ?? null),
                $test['order'] ?? $test['test_order'] ?? 1
            ]);
        }
    }

    /**
     * Mettre à jour les statistiques d'un dataset
     */
    private function updateDatasetStats($dataset_id) {
        $stmt = $this->db->prepare("CALL update_dataset_stats(?)");
        $stmt->execute([$dataset_id]);
    }

    /**
     * Mapper la difficulté
     */
    private function mapDifficulty($difficulty) {
        $difficulty = strtolower($difficulty);
        $map = [
            'easy' => 'facile',
            'facile' => 'facile',
            'medium' => 'moyen',
            'moyen' => 'moyen',
            'hard' => 'difficile',
            'difficile' => 'difficile'
        ];
        return $map[$difficulty] ?? 'moyen';
    }
}

