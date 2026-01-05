<?php
/**
 * API d'import simple - Exercices
 * Endpoint direct sans routeur complexe
 */

// Démarrer la bufferisation de sortie pour éviter que des erreurs PHP ne cassent le JSON
ob_start();

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Pas d'affichage pour ne pas casser le JSON
ini_set('log_errors', 1);

header('Content-Type: application/json');

// Vérifier authentification
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié - Connectez-vous d\'abord']);
    exit;
}

$user_id = $_SESSION['id'];

try {
    // Connexion DB
    require_once __DIR__ . '/models/Database.php';
    $db = Database::getConnection();

    error_log("=== Import Exercises Started ===");
    error_log("User ID: $user_id");

    // Lire les données JSON
    $json_data = file_get_contents('php://input');
    error_log("Raw data length: " . strlen($json_data));

    if (empty($json_data)) {
        throw new Exception('Aucune donnée reçue');
    }

    $data = json_decode($json_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON invalide: ' . json_last_error_msg());
    }

    // Supporter plusieurs formats
    $exercises = [];

    if (is_array($data)) {
        if (isset($data['exercises']) && is_array($data['exercises'])) {
            // Format: { "exercises": [...] }
            $exercises = $data['exercises'];
        } elseif (isset($data[0])) {
            // Format: [...] (tableau direct)
            $exercises = $data;
        } else {
            // Format: { exercise unique }
            $exercises = [$data];
        }
    } else {
        throw new Exception('Format JSON invalide');
    }

    error_log("Found " . count($exercises) . " exercises to import");

    if (empty($exercises)) {
        throw new Exception('Aucun exercice trouvé dans le JSON');
    }

    $db->beginTransaction();

    $success_count = 0;
    $error_count = 0;
    $errors = [];

    foreach ($exercises as $index => $exercise) {
        try {
            error_log("Processing exercise #$index: " . ($exercise['title'] ?? $exercise['name'] ?? 'unnamed'));

            // 1. Créer/récupérer la ressource (TP)
            $resource_id = null;

            // Option A: ID fourni directement
            if (!empty($exercise['resource_id'])) {
                $check_id = $exercise['resource_id'];
                $stmt = $db->prepare("SELECT resource_id FROM resources WHERE resource_id = ? AND owner_user_id = ?");
                $stmt->execute([$check_id, $user_id]);
                $resource_id = $stmt->fetchColumn();

                if ($resource_id) {
                    error_log("Using provided resource_id: $resource_id");
                } else {
                    error_log("Provided resource_id $check_id not found or not owned by user. Falling back to name.");
                }
            }

            // Option B: Recherche par nom (tp_id / tp)
            if (!$resource_id) {
                $tp_id = $exercise['tp_id'] ?? $exercise['tp'] ?? 'TP_Import_' . date('Y-m-d');
                $tp_description = $exercise['tp_description'] ?? null;

                // Chercher ressource existante
                $stmt = $db->prepare("SELECT resource_id FROM resources WHERE resource_name = ? AND owner_user_id = ?");
                $stmt->execute([$tp_id, $user_id]);
                $resource_id = $stmt->fetchColumn();

                if (!$resource_id) {
                    // Créer nouvelle ressource
                    $stmt = $db->prepare("INSERT INTO resources (owner_user_id, resource_name, description) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $tp_id, $tp_description]);
                    $resource_id = $db->lastInsertId();
                    error_log("Created resource: $resource_id ($tp_id)");

                    // Add professor access to the new resource
                    $stmt_access = $db->prepare("INSERT INTO resource_professors_access (resource_id, user_id) VALUES (?, ?)");
                    $stmt_access->execute([$resource_id, $user_id]);
                }
            }

            // 2. Insérer l'exercice
            $exo_name = $exercise['title'] ?? $exercise['name'] ?? $exercise['exo_name'] ?? $exercise['question_name'] ?? 'Exercise_' . ($index + 1);
            $funcname = $exercise['funcname'] ?? null;
            $solution = $exercise['solution'] ?? null;
            $description = $exercise['description'] ?? null;

            // Mapper difficulté
            $difficulty = strtolower($exercise['difficulty'] ?? $exercise['difficulte'] ?? 'moyen');
            $difficulty_map = [
                'easy' => 'facile',
                'facile' => 'facile',
                'medium' => 'moyen',
                'moyen' => 'moyen',
                'hard' => 'difficile',
                'difficile' => 'difficile'
            ];
            $difficulty = $difficulty_map[$difficulty] ?? 'moyen';

            // Vérifier si existe déjà
            $stmt = $db->prepare("SELECT exercise_id FROM exercises WHERE resource_id = ? AND exo_name = ?");
            $stmt->execute([$resource_id, $exo_name]);
            $exercise_id = $stmt->fetchColumn();

            if ($exercise_id) {
                // Mettre à jour
                $stmt = $db->prepare("
                    UPDATE exercises 
                    SET funcname = ?, solution = ?, description = ?, difficulte = ?
                    WHERE exercise_id = ?
                ");
                $stmt->execute([$funcname, $solution, $description, $difficulty, $exercise_id]);
                error_log("Updated exercise: $exercise_id ($exo_name)");
            } else {
                // Insérer
                $stmt = $db->prepare("
                    INSERT INTO exercises (resource_id, exo_name, funcname, solution, description, difficulte)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$resource_id, $exo_name, $funcname, $solution, $description, $difficulty]);
                $exercise_id = $db->lastInsertId();
                error_log("Inserted exercise: $exercise_id ($exo_name)");
            }

            // 3. Insérer les test cases
            $test_cases = $exercise['test_cases'] ?? $exercise['entries'] ?? [];
            if (!empty($test_cases) && is_array($test_cases)) {
                // Supprimer anciens test cases
                $stmt = $db->prepare("DELETE FROM test_cases WHERE exercise_id = ?");
                $stmt->execute([$exercise_id]);

                foreach ($test_cases as $tc_index => $test) {
                    // Déterminer si c'est un objet de test structuré (avec clés input/expected) ou une donnée brute
                    $is_structured_test = is_array($test) && (
                        array_key_exists('input', $test) ||
                        array_key_exists('input_data', $test) ||
                        array_key_exists('expected', $test) ||
                        array_key_exists('expected_output', $test)
                    );

                    if ($is_structured_test) {
                        // Format standard test_cases
                        $input = isset($test['input']) ? json_encode($test['input']) : (isset($test['input_data']) ? $test['input_data'] : '[]');
                        $expected = isset($test['expected']) ? json_encode($test['expected']) :
                                    (isset($test['expected_output']) ? $test['expected_output'] : null);
                    } else {
                        // C'est un input direct (valeur simple, tableau d'arguments, ou tuple encodé)
                        // Ex: "entries": [[5, 15], [-6, -4]] -> chaque élément est un input
                        $input = json_encode($test);
                        $expected = null;
                    }

                    $order = $test['order'] ?? $test['test_order'] ?? ($tc_index + 1);

                    $stmt = $db->prepare("
                        INSERT INTO test_cases (exercise_id, input_data, expected_output, test_order)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$exercise_id, $input, $expected, $order]);
                }
                error_log("Inserted " . count($test_cases) . " test cases");
            }

            $success_count++;

        } catch (Exception $e) {
            $error_count++;
            $error_msg = "Exercice #$index: " . $e->getMessage();
            $errors[] = $error_msg;
            error_log("ERROR: $error_msg");
        }
    }

    // Mettre à jour le compteur d'exercices dans resources
    $stmt = $db->prepare("
        UPDATE resources r
        SET r.resource_name = r.resource_name
        WHERE r.resource_id IN (
            SELECT DISTINCT resource_id FROM exercises WHERE resource_id IN (
                SELECT resource_id FROM resources WHERE owner_user_id = ?
            )
        )
    ");
    $stmt->execute([$user_id]);

    $db->commit();

    error_log("=== Import Completed: Success=$success_count, Errors=$error_count ===");

    // Nettoyer le tampon de sortie avant d'envoyer le JSON
    ob_end_clean();

    echo json_encode([
        'success' => true,
        'message' => "Import terminé !",
        'total' => count($exercises),
        'success_count' => $success_count,
        'error_count' => $error_count,
        'errors' => $errors
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    error_log("FATAL ERROR: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());

    // Nettoyer le tampon de sortie avant d'envoyer l'erreur JSON
    if (ob_get_length()) ob_end_clean();

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
