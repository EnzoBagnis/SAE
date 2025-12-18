<?php
/**
 * API d'import simple - Tentatives
 */

// Démarrer la bufferisation de sortie pour éviter que des erreurs PHP ne cassent le JSON
ob_start();

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
    error_log("Received data length: " . strlen($json_data));

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
    $dataset_name = !empty($dataset_info['nom_dataset']) ? $dataset_info['nom_dataset'] : null;

    if (!$dataset_name) {
        // Si pas de nom de dataset, essayer de le baser sur la ressource cible pour regrouper les imports
        $target_resource_id = $_GET['id'] ?? $data['resource_id'] ?? null;
        if ($target_resource_id) {
            $stmt = $db->prepare("SELECT resource_name FROM resources WHERE resource_id = ?");
            $stmt->execute([$target_resource_id]);
            $r_name = $stmt->fetchColumn();
            if ($r_name) {
                // Nom unique et stable basé sur la ressource
                $dataset_name = 'Dataset_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $r_name) . '_' . $target_resource_id;
            }
        }
    }

    if (!$dataset_name) {
        $dataset_name = 'Dataset_' . date('Y-m-d_H-i-s');
    }

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

            $exercise_id = null;

            // Si resource_id est fourni (via URL, payload global ou dans l'objet attempt), essayer de restreindre la recherche
            $resource_id = $_GET['id'] ?? $data['resource_id'] ?? $attempt['resource_id'] ?? null;

            if ($resource_id) {
                $stmt = $db->prepare("SELECT exercise_id FROM exercises WHERE exo_name = ? AND resource_id = ? LIMIT 1");
                $stmt->execute([$exercise_name, $resource_id]);
                $exercise_id = $stmt->fetchColumn();
            }

            // Fallback: recherche globale par nom si non trouvé ou pas de resource_id
            if (!$exercise_id) {
                $stmt = $db->prepare("SELECT exercise_id FROM exercises WHERE exo_name = ? LIMIT 1");
                $stmt->execute([$exercise_name]);
                $exercise_id = $stmt->fetchColumn();
            }

            if (!$exercise_id) {
                throw new Exception("Exercice '$exercise_name' non trouvé en DB");
            }

            // Formater la date pour MySQL
            $submission_date = $attempt['submission_date'] ?? date('Y-m-d H:i:s');
            // Si format ISO 8601 (avec T et Z), convertir
            if (strpos($submission_date, 'T') !== false) {
                $ts = strtotime($submission_date);
                if ($ts) {
                    $submission_date = date('Y-m-d H:i:s', $ts);
                }
            }

            // Vérifier si la tentative existe déjà pour éviter les doublons
            $stmt = $db->prepare("
                SELECT attempt_id FROM attempts 
                WHERE student_id = ? AND exercise_id = ? AND submission_date = ?
            ");
            $stmt->execute([
                $student_id,
                $exercise_id,
                $submission_date
            ]);

            if ($stmt->fetchColumn()) {
                // Déjà importé, on ignore silencieusement ou on compte comme succès
                $success_count++;
                continue;
            }

            // Insérer tentative
            $stmt = $db->prepare("
                INSERT INTO attempts (student_id, exercise_id, submission_date, extension, correct, upload, eval_set)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $student_id,
                $exercise_id,
                $submission_date,
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

    // Nettoyer le tampon de sortie avant d'envoyer le JSON
    ob_end_clean();

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

    // Nettoyer le tampon de sortie avant d'envoyer l'erreur JSON
    if (ob_get_length()) ob_end_clean();

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

