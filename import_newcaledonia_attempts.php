<?php
/**
 * SCRIPT D'IMPORTATION - Tentatives des étudiants Nouvelle-Calédonie
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
    $jsonPath = __DIR__ . '/data/NewCaledonia_1014.json';
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
    $notFound = ['students' => [], 'exercises' => []];

    foreach ($attempts as $index => $att) {
        // ⚠️ CORRECTION ICI : utiliser 'user' et 'exercise_name' au lieu de 'student_identifier' et 'exo_name'
        $studentIdentifier = $att['user'] ?? null;
        $exoName = $att['exercise_name'] ?? null;

        if (!$studentIdentifier || !$exoName) {
            $errors++;
            echo "  ⚠️  Ligne $index: user ou exercise_name manquant\n";
            continue;
        }

        $studentId = get_student_id($pdo, $datasetId, $studentIdentifier);
        $exerciseId = get_exercise_id($pdo, $resourceId, $exoName);

        if (!$studentId) {
            if (!in_array($studentIdentifier, $notFound['students'])) {
                $notFound['students'][] = $studentIdentifier;
            }
        }
        if (!$exerciseId) {
            if (!in_array($exoName, $notFound['exercises'])) {
                $notFound['exercises'][] = $exoName;
            }
        }

        if (!$studentId || !$exerciseId) {
            $errors++;
            if ($index < 10 || $errors % 100 == 0) { // Afficher seulement les 10 premières erreurs + tous les 100
                echo "  ⚠️  Ligne $index: " .
                    (!$studentId ? "étudiant '$studentIdentifier' introuvable " : "") .
                    (!$exerciseId ? "exercice '$exoName' introuvable" : "") . "\n";
            }
            continue;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO attempts (student_id, exercise_id, submission_date, extension, correct, upload, eval_set, aes0, aes1, aes2)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $studentId,
                $exerciseId,
                $att['date'] ?? date('Y-m-d H:i:s'),
                $att['extension'] ?? 'py',
                $att['correct'] ?? 0,
                $att['upload'] ?? '',
                $att['eval_set'] ?? null,
                isset($att['aes0']) ? json_encode($att['aes0']) : null,
                isset($att['aes1']) ? json_encode($att['aes1']) : null,
                isset($att['aes2']) ? json_encode($att['aes2']) : null
            ]);
            $imported++;

            if ($imported % 100 == 0) {
                echo "  ✓ {$imported} tentatives importées...\n";
            }
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

    if (!empty($notFound['students'])) {
        echo "\n⚠️  Étudiants introuvables (" . count($notFound['students']) . "):\n";
        foreach (array_slice($notFound['students'], 0, 10) as $student) {
            echo "  - $student\n";
        }
        if (count($notFound['students']) > 10) {
            echo "  ... et " . (count($notFound['students']) - 10) . " autres\n";
        }
    }

    if (!empty($notFound['exercises'])) {
        echo "\n⚠️  Exercices introuvables (" . count($notFound['exercises']) . "):\n";
        foreach (array_slice($notFound['exercises'], 0, 10) as $exercise) {
            echo "  - $exercise\n";
        }
        if (count($notFound['exercises']) > 10) {
            echo "  ... et " . (count($notFound['exercises']) - 10) . " autres\n";
        }
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

} catch (Exception $e) {
    if ($pdo !== null && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>