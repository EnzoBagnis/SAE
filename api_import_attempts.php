<?php
/**
 * API d'import simple - Tentatives
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

// Vérifier authentification
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$user_id = $_SESSION['id'];

try {
    require_once __DIR__ . '/models/Database.php';
    $db = Database::getConnection();

    error_log("=== Import Attempts Started ===");

    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON invalide: ' . json_last_error_msg());
    }

    // Supporter plusieurs formats
    $attempts = [];
    $dataset_info = [];

    if (is_array($data)) {
        if (isset($data['attempts']) && is_array($data['attempts'])) {
            $attempts = $data['attempts'];
            $dataset_info = $data['dataset_info'] ?? [];
        } elseif (isset($data[0])) {
            $attempts = $data;
        } else {
            $attempts = [$data];
        }
    }

    if (empty($attempts)) {
        throw new Exception('Aucune tentative trouvée');
    }

    error_log("Found " . count($attempts) . " attempts to import");

    // Créer/récupérer dataset
    $dataset_name = $dataset_info['nom_dataset'] ?? 'Dataset_' . date('Y-m-d_H-i-s');

    $stmt = $db->prepare("SELECT dataset_id FROM datasets WHERE nom_dataset = ?");
    $stmt->execute([$dataset_name]);
    $dataset_id = $stmt->fetchColumn();

    if (!$dataset_id) {
        $stmt = $db->prepare("
            INSERT INTO datasets (enseignant_id, nom_dataset, description, pays, annee)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $dataset_name,
            $dataset_info['description'] ?? null,
            $dataset_info['pays'] ?? null,
            $dataset_info['annee'] ?? date('Y')
        ]);
        $dataset_id = $db->lastInsertId();
        error_log("Created dataset: $dataset_id");
    }

    $db->beginTransaction();

    $success_count = 0;
    $error_count = 0;
    $errors = [];

    foreach ($attempts as $index => $attempt) {
        try {
            // Créer/récupérer étudiant
            $student_identifier = $attempt['student_identifier'] ?? $attempt['student_id'] ?? $attempt['eleve_id'] ?? 'student_' . $index;

            $stmt = $db->prepare("SELECT student_id FROM students WHERE dataset_id = ? AND student_identifier = ?");
            $stmt->execute([$dataset_id, $student_identifier]);
            $student_id = $stmt->fetchColumn();

            if (!$student_id) {
                $stmt = $db->prepare("INSERT INTO students (dataset_id, student_identifier) VALUES (?, ?)");
                $stmt->execute([$dataset_id, $student_identifier]);
                $student_id = $db->lastInsertId();
            }

            // Trouver exercice
            $exercise_name = $attempt['exercise_name'] ?? $attempt['exo_name'] ?? null;
            if (!$exercise_name) {
                throw new Exception("exercise_name manquant");
            }

            $stmt = $db->prepare("SELECT exercise_id FROM exercises WHERE exo_name = ? LIMIT 1");
            $stmt->execute([$exercise_name]);
            $exercise_id = $stmt->fetchColumn();

            if (!$exercise_id) {
                throw new Exception("Exercice '$exercise_name' non trouvé en DB");
            }

            // Insérer tentative
            $stmt = $db->prepare("
                INSERT INTO attempts (student_id, exercise_id, submission_date, extension, correct, upload, eval_set)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $student_id,
                $exercise_id,
                $attempt['submission_date'] ?? date('Y-m-d H:i:s'),
                $attempt['extension'] ?? 'py',
                ($attempt['correct'] ?? false) ? 1 : 0,
                $attempt['code'] ?? $attempt['upload'] ?? '',
                $attempt['eval_set'] ?? null
            ]);

            $success_count++;

        } catch (Exception $e) {
            $error_count++;
            $errors[] = "Tentative #$index: " . $e->getMessage();
            error_log("ERROR: Tentative #$index: " . $e->getMessage());
        }
    }

    // Mettre à jour stats dataset
    $stmt = $db->prepare("CALL update_dataset_stats(?)");
    $stmt->execute([$dataset_id]);

    $db->commit();

    error_log("=== Import Attempts Completed: Success=$success_count, Errors=$error_count ===");

    echo json_encode([
        'success' => true,
        'message' => "Import terminé !",
        'dataset_id' => $dataset_id,
        'total' => count($attempts),
        'success_count' => $success_count,
        'error_count' => $error_count,
        'errors' => $errors
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    error_log("FATAL ERROR: " . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

