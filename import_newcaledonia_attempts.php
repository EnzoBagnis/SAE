<?php
/**
 * SCRIPT D'IMPORTATION - Tentatives des étudiants Nouvelle-Calédonie
 *
 * Ce script importe les tentatives d'étudiants à partir d'un fichier JSON
 * Format attendu : voir README ou exemple dans le script
 */

require_once __DIR__ . '/models/Database.php';

$pdo = null;

function get_student_id($pdo, $datasetId, $studentIdentifier) {
    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE dataset_id = ? AND student_identifier = ?");
    $stmt->execute([$datasetId, $studentIdentifier]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['student_id'] : null;
}

function get_exercise_id($pdo, $resourceId, $exoName) {
    $stmt = $pdo->prepare("SELECT exercise_id FROM exercises WHERE resource_id = ? AND exo_name = ?");
    $stmt->execute([$resourceId, $exoName]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['exercise_id'] : null;
}

try {
    $pdo = Database::getConnection();

    // 1. Trouver le dataset et la resource "Nouvelle-Calédonie"
    $stmt = $pdo->prepare("SELECT dataset_id FROM datasets WHERE nom_dataset = ?");
    $stmt->execute(['Nouvelle-Calédonie']);
    $dataset = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$dataset) {
        echo "❌ Dataset 'Nouvelle-Calédonie' introuvable.\n";
        exit;
    }
    $datasetId = $dataset['dataset_id'];

    $stmt = $pdo->prepare("SELECT resource_id FROM resources WHERE resource_name = ?");
    $stmt->execute(['Nouvelle-Calédonie']);
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$resource) {
        echo "❌ Resource 'Nouvelle-Calédonie' introuvable.\n";
        exit;
    }
    $resourceId = $resource['resource_id'];

    // 2. Charger les données JSON
    $jsonPath = __DIR__ . '/data/NewCaledonia_attempts.json';
    if (!file_exists($jsonPath)) {
        echo "❌ Erreur: Fichier {$jsonPath} introuvable\n";
        exit;
    }
    $jsonData = file_get_contents($jsonPath);
    $attempts = json_decode($jsonData, true);
    if (!$attempts) {
        echo "❌ Erreur lors de la lecture du fichier JSON\n";
        exit;
    }
    $nbAttempts = count($attempts);
    echo "✓ {$nbAttempts} tentatives trouvées dans le fichier JSON\n\n";

    $pdo->beginTransaction();
    $imported = 0;
    $errors = 0;

    foreach ($attempts as $index => $att) {
        $studentIdentifier = $att['student_identifier'] ?? null;
        $exoName = $att['exo_name'] ?? null;
        if (!$studentIdentifier || !$exoName) {
            $errors++;
            echo "  ⚠️  Ligne $index: student_identifier ou exo_name manquant\n";
            continue;
        }
        $studentId = get_student_id($pdo, $datasetId, $studentIdentifier);
        $exerciseId = get_exercise_id($pdo, $resourceId, $exoName);
        if (!$studentId || !$exerciseId) {
            $errors++;
            echo "  ⚠️  Ligne $index: student_id ou exercise_id introuvable (identifiant: $studentIdentifier, exo: $exoName)\n";
            continue;
        }
        try {
            $stmt = $pdo->prepare("
                INSERT INTO attempts (student_id, exercise_id, submission_date, extension, correct, upload, eval_set)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $studentId,
                $exerciseId,
                $att['submission_date'] ?? date('Y-m-d H:i:s'),
                $att['extension'] ?? 'py',
                $att['correct'] ?? 0,
                $att['upload'] ?? '',
                $att['eval_set'] ?? null
            ]);
            $imported++;
        } catch (Exception $e) {
            $errors++;
            echo "  ⚠️  Ligne $index: erreur SQL: {$e->getMessage()}\n";
        }
    }
    $pdo->commit();
    echo "\n✅ IMPORTATION TERMINÉE!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 Résumé:\n";
    echo "  • Tentatives importées: {$imported}\n";
    echo "  • Erreurs: {$errors}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

} catch (Exception $e) {
    if ($pdo !== null && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>

