<?php
/**
 * SCRIPT D'IMPORTATION COMPLET - Nouvelle-Calédonie
 * Importe étudiants et tentatives (les exercices doivent déjà être importés)
 */

require_once __DIR__ . '/models/Database.php';

$pdo = null;

try {
    $pdo = Database::getConnection();

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🚀 IMPORTATION TENTATIVES NOUVELLE-CALÉDONIE\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // ========================================
    // 1. VÉRIFIER DATASET ET RESOURCE
    // ========================================
    echo "📂 Étape 1/4 : Vérification du dataset et de la resource...\n";

    $stmt = $pdo->prepare("SELECT dataset_id FROM datasets WHERE nom_dataset = ?");
    $stmt->execute(['Nouvelle-Calédonie']);
    $dataset = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$dataset) {
        echo "❌ Dataset 'Nouvelle-Calédonie' introuvable.\n";
        echo "💡 Veuillez d'abord exécuter: php import_newcaledonia_data.php\n";
        exit;
    }
    $datasetId = $dataset['dataset_id'];
    echo "  ✓ Dataset trouvé (ID: {$datasetId})\n";

    $stmt = $pdo->prepare("SELECT resource_id FROM resources WHERE resource_name = ?");
    $stmt->execute(['Nouvelle-Calédonie']);
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$resource) {
        echo "❌ Resource 'Nouvelle-Calédonie' introuvable.\n";
        echo "💡 Veuillez d'abord exécuter: php import_newcaledonia_data.php\n";
        exit;
    }
    $resourceId = $resource['resource_id'];
    echo "  ✓ Resource trouvée (ID: {$resourceId})\n";

    // Vérifier qu'il y a des exercices
    $stmt = $pdo->prepare("SELECT COUNT(*) as nb FROM exercises WHERE resource_id = ?");
    $stmt->execute([$resourceId]);
    $nbExercises = $stmt->fetch(PDO::FETCH_ASSOC)['nb'];
    if ($nbExercises == 0) {
        echo "❌ Aucun exercice trouvé pour cette resource.\n";
        echo "💡 Veuillez d'abord exécuter: php import_newcaledonia_data.php\n";
        exit;
    }
    echo "  ✓ {$nbExercises} exercices trouvés\n\n";

    // ========================================
    // 2. CHARGER LE FICHIER JSON DES TENTATIVES
    // ========================================
    echo "📄 Étape 2/4 : Chargement du fichier JSON des tentatives...\n";

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
    echo "  ✓ " . count($attempts) . " tentatives chargées\n\n";

    // ========================================
    // 3. EXTRAIRE ET CRÉER LES ÉTUDIANTS
    // ========================================
    echo "👥 Étape 3/4 : Importation des étudiants...\n";

    $studentIdentifiers = [];
    foreach ($attempts as $att) {
        if (isset($att['user'])) {
            $studentIdentifiers[$att['user']] = true;
        }
    }
    $studentIdentifiers = array_keys($studentIdentifiers);
    sort($studentIdentifiers);

    echo "  📊 " . count($studentIdentifiers) . " étudiants uniques trouvés\n";

    $pdo->beginTransaction();

    $studentsImported = 0;
    $studentsSkipped = 0;

    $stmtCheckStudent = $pdo->prepare("SELECT student_id FROM students WHERE dataset_id = ? AND student_identifier = ?");
    $stmtInsertStudent = $pdo->prepare("INSERT INTO students (dataset_id, student_identifier) VALUES (?, ?)");

    foreach ($studentIdentifiers as $identifier) {
        $stmtCheckStudent->execute([$datasetId, $identifier]);
        if ($stmtCheckStudent->fetch()) {
            $studentsSkipped++;
        } else {
            $stmtInsertStudent->execute([$datasetId, $identifier]);
            $studentsImported++;
        }
    }

    echo "  ✓ Étudiants créés: {$studentsImported}\n";
    if ($studentsSkipped > 0) {
        echo "  ⏭️  Étudiants existants: {$studentsSkipped}\n";
    }

    // Générer les noms fictifs
    if ($studentsImported > 0) {
        echo "  🎭 Génération des noms fictifs...\n";
        $stmt = $pdo->prepare("CALL generate_fake_names(?)");
        $stmt->execute([$datasetId]);
        echo "  ✓ Noms fictifs générés\n";
    }
    echo "\n";

    $pdo->commit();

    // ========================================
    // 4. IMPORTER LES TENTATIVES
    // ========================================
    echo "💾 Étape 4/4 : Importation des tentatives...\n";

    // Créer un cache des exercices pour optimiser les requêtes
    echo "  🔄 Chargement du cache des exercices...\n";
    $stmt = $pdo->prepare("SELECT exercise_id, exo_name FROM exercises WHERE resource_id = ?");
    $stmt->execute([$resourceId]);
    $exerciseCache = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $exerciseCache[$row['exo_name']] = $row['exercise_id'];
    }
    echo "  ✓ " . count($exerciseCache) . " exercices en cache\n";

    // Créer un cache des étudiants
    echo "  🔄 Chargement du cache des étudiants...\n";
    $stmt = $pdo->prepare("SELECT student_id, student_identifier FROM students WHERE dataset_id = ?");
    $stmt->execute([$datasetId]);
    $studentCache = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $studentCache[$row['student_identifier']] = $row['student_id'];
    }
    echo "  ✓ " . count($studentCache) . " étudiants en cache\n\n";

    $pdo->beginTransaction();

    $attemptsImported = 0;
    $attemptsErrors = 0;
    $missingExercises = [];
    $missingStudents = [];

    $stmtInsertAttempt = $pdo->prepare("
        INSERT INTO attempts (student_id, exercise_id, submission_date, extension, correct, upload, eval_set, aes0, aes1, aes2)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($attempts as $index => $att) {
        $studentIdentifier = $att['user'] ?? null;
        $exoName = $att['exercise_name'] ?? null;

        if (!$studentIdentifier || !$exoName) {
            $attemptsErrors++;
            continue;
        }

        // Récupérer depuis le cache
        $studentId = $studentCache[$studentIdentifier] ?? null;
        $exerciseId = $exerciseCache[$exoName] ?? null;

        if (!$studentId) {
            if (!in_array($studentIdentifier, $missingStudents)) {
                $missingStudents[] = $studentIdentifier;
            }
            $attemptsErrors++;
            continue;
        }

        if (!$exerciseId) {
            if (!in_array($exoName, $missingExercises)) {
                $missingExercises[] = $exoName;
            }
            $attemptsErrors++;
            continue;
        }

        // Insérer la tentative
        try {
            $stmtInsertAttempt->execute([
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
            $attemptsImported++;

            if ($attemptsImported % 200 == 0) {
                echo "  ⏳ {$attemptsImported} tentatives importées...\n";
            }
        } catch (Exception $e) {
            $attemptsErrors++;
            if ($attemptsErrors <= 5) {
                echo "  ⚠️  Erreur SQL: {$e->getMessage()}\n";
            }
        }
    }

    $pdo->commit();

    // ========================================
    // RÉSUMÉ FINAL
    // ========================================
    echo "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ IMPORTATION TERMINÉE AVEC SUCCÈS!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 Résumé global:\n\n";
    echo "  👥 Étudiants:\n";
    echo "     • Créés: {$studentsImported}\n";
    echo "     • Existants: {$studentsSkipped}\n";
    echo "     • Total: " . count($studentIdentifiers) . "\n\n";
    echo "  💾 Tentatives:\n";
    echo "     • Importées: {$attemptsImported}\n";
    echo "     • Erreurs: {$attemptsErrors}\n";
    echo "     • Total: " . count($attempts) . "\n";

    if (!empty($missingExercises)) {
        echo "\n  ⚠️  Exercices introuvables (" . count($missingExercises) . "):\n";
        foreach (array_slice($missingExercises, 0, 10) as $exo) {
            echo "     - {$exo}\n";
        }
        if (count($missingExercises) > 10) {
            echo "     ... et " . (count($missingExercises) - 10) . " autres\n";
        }
    }

    if (!empty($missingStudents)) {
        echo "\n  ⚠️  Étudiants introuvables (" . count($missingStudents) . "):\n";
        foreach (array_slice($missingStudents, 0, 5) as $student) {
            echo "     - {$student}\n";
        }
        if (count($missingStudents) > 5) {
            echo "     ... et " . (count($missingStudents) - 5) . " autres\n";
        }
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Mettre à jour les statistiques du dataset
    echo "📈 Mise à jour des statistiques du dataset...\n";
    $stmt = $pdo->prepare("CALL update_dataset_stats(?)");
    $stmt->execute([$datasetId]);
    echo "✓ Statistiques mises à jour\n\n";

} catch (Exception $e) {
    if ($pdo !== null && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>