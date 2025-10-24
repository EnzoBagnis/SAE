<?php
/**
 * SCRIPT D'IMPORTATION - Données Nouvelle-Calédonie
 *
 * Ce script importe les exercices de Nouvelle-Calédonie dans la base de données
 */

require_once __DIR__ . '/models/Database.php';

echo "🚀 Importation des données Nouvelle-Calédonie...\n\n";

$pdo = null;

try {
    $pdo = Database::getConnection();

    // 1. Vérifier qu'il y a au moins un enseignant
    $stmt = $pdo->query("SELECT id FROM utilisateurs WHERE verifie = 1 LIMIT 1");
    $enseignant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$enseignant) {
        echo "❌ Erreur: Aucun enseignant vérifié trouvé dans la base.\n";
        echo "Veuillez d'abord créer un enseignant.\n";
        exit;
    }

    $enseignantId = $enseignant['id'];
    echo "✓ Enseignant trouvé: ID {$enseignantId}\n\n";

    // 2. Charger les données JSON
    $jsonPath = __DIR__ . '/data/NewCaledonia_exercises.json';

    if (!file_exists($jsonPath)) {
        echo "❌ Erreur: Fichier {$jsonPath} introuvable\n";
        exit;
    }

    $jsonData = file_get_contents($jsonPath);
    $exercises = json_decode($jsonData, true);

    if (!$exercises) {
        echo "❌ Erreur lors de la lecture du fichier JSON\n";
        exit;
    }

    $nbExercices = count($exercises);
    echo "✓ {$nbExercices} exercices trouvés dans le fichier JSON\n\n";

    $pdo->beginTransaction();

    // 3. Vérifier/Créer la RESOURCE "Nouvelle-Calédonie"
    $stmt = $pdo->prepare("SELECT resource_id FROM resources WHERE resource_name = ? AND owner_user_id = ?");
    $stmt->execute(['Nouvelle-Calédonie', $enseignantId]);
    $existingResource = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingResource) {
        $resourceId = $existingResource['resource_id'];
        echo "✓ Resource 'Nouvelle-Calédonie' existe déjà (ID: {$resourceId})\n";
        echo "  Suppression des exercices existants...\n";

        // Supprimer les anciens exercices de cette resource
        $pdo->prepare("DELETE FROM test_cases WHERE exercise_id IN (SELECT exercise_id FROM exercises WHERE resource_id = ?)")
            ->execute([$resourceId]);
        $pdo->prepare("DELETE FROM attempts WHERE exercise_id IN (SELECT exercise_id FROM exercises WHERE resource_id = ?)")
            ->execute([$resourceId]);
        $pdo->prepare("DELETE FROM exercises WHERE resource_id = ?")
            ->execute([$resourceId]);
    } else {
        // Créer la nouvelle resource
        $stmt = $pdo->prepare("
            INSERT INTO resources (owner_user_id, resource_name, description)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $enseignantId,
            'Nouvelle-Calédonie',
            'Exercices de programmation Python provenant de Nouvelle-Calédonie'
        ]);
        $resourceId = $pdo->lastInsertId();
        echo "✓ Resource 'Nouvelle-Calédonie' créée (ID: {$resourceId})\n";
    }

    // 4. Vérifier/Créer le DATASET "Nouvelle-Calédonie"
    $stmt = $pdo->prepare("SELECT dataset_id FROM datasets WHERE nom_dataset = ?");
    $stmt->execute(['Nouvelle-Calédonie']);
    $existingDataset = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingDataset) {
        $datasetId = $existingDataset['dataset_id'];
        echo "✓ Dataset 'Nouvelle-Calédonie' existe déjà (ID: {$datasetId})\n";

        // Mettre à jour le dataset
        $stmt = $pdo->prepare("UPDATE datasets SET nb_exercices = ? WHERE dataset_id = ?");
        $stmt->execute([$nbExercices, $datasetId]);
    } else {
        // Créer le nouveau dataset
        $stmt = $pdo->prepare("
            INSERT INTO datasets (nom_dataset, enseignant_id, nb_exercices, nb_etudiants, nb_tentatives, pays, annee)
            VALUES (?, ?, ?, 0, 0, ?, ?)
        ");
        $stmt->execute(['Nouvelle-Calédonie', $enseignantId, $nbExercices, 'Nouvelle-Calédonie', 2024]);
        $datasetId = $pdo->lastInsertId();
        echo "✓ Dataset 'Nouvelle-Calédonie' créé (ID: {$datasetId})\n";
    }

    echo "\n📝 Importation des exercices...\n";

    $imported = 0;
    $errors = 0;

    foreach ($exercises as $index => $exoData) {
        try {
            $exoName = $exoData['exo_name'] ?? "Exercise_" . ($index + 1);
            $funcname = $exoData['funcname'] ?? '';
            $solution = $exoData['solution'] ?? '';
            $entries = $exoData['entries'] ?? [];

            // Créer l'exercice lié à la RESOURCE
            $stmt = $pdo->prepare("
                INSERT INTO exercises (resource_id, exo_name, funcname, solution)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$resourceId, $exoName, $funcname, $solution]);
            $exerciseId = $pdo->lastInsertId();

            // Ajouter les test cases
            if (!empty($entries)) {
                $stmt = $pdo->prepare("
                    INSERT INTO test_cases (exercise_id, input_data, expected_output, test_order)
                    VALUES (?, ?, NULL, ?)
                ");

                foreach ($entries as $testIndex => $entry) {
                    // Convertir l'entrée en JSON
                    $inputJson = json_encode($entry);
                    $stmt->execute([$exerciseId, $inputJson, $testIndex + 1]);
                }
            }

            $imported++;

            if (($imported % 10) == 0) {
                echo "  ✓ {$imported} exercices importés...\n";
            }

        } catch (Exception $e) {
            $errors++;
            echo "  ⚠️  Erreur pour l'exercice {$exoName}: {$e->getMessage()}\n";
        }
    }

    $pdo->commit();

    echo "\n✅ IMPORTATION TERMINÉE!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 Résumé:\n";
    echo "  • Resource: Nouvelle-Calédonie (ID: {$resourceId})\n";
    echo "  • Dataset: Nouvelle-Calédonie (ID: {$datasetId})\n";
    echo "  • Exercices importés: {$imported}\n";
    echo "  • Erreurs: {$errors}\n";
    echo "  • Enseignant: ID {$enseignantId}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

} catch (Exception $e) {
    if ($pdo !== null && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
