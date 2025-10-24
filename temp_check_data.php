<?php
require_once __DIR__ . '/models/Database.php';

$pdo = Database::getConnection();

echo "=== VÉRIFICATION DES DONNÉES GÉNÉRÉES ===\n\n";

// Datasets
$stmt = $pdo->query("SELECT dataset_id, nom_dataset, enseignant_id, nb_exercices, nb_etudiants, nb_tentatives FROM datasets");
$datasets = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "📊 DATASETS (" . count($datasets) . "):\n";
foreach ($datasets as $ds) {
    echo "  - ID {$ds['dataset_id']}: {$ds['nom_dataset']} (Enseignant: {$ds['enseignant_id']})\n";
    echo "    Exercices: {$ds['nb_exercices']}, Étudiants: {$ds['nb_etudiants']}, Tentatives: {$ds['nb_tentatives']}\n";
}

echo "\n";

// Exercices
$stmt = $pdo->query("SELECT COUNT(*) FROM exercises");
$nbExercises = $stmt->fetchColumn();
echo "💻 EXERCICES: {$nbExercises}\n";

// Étudiants
$stmt = $pdo->query("SELECT COUNT(*) FROM students");
$nbStudents = $stmt->fetchColumn();
echo "👥 ÉTUDIANTS: {$nbStudents}\n";

// Tentatives
$stmt = $pdo->query("SELECT COUNT(*) FROM attempts");
$nbAttempts = $stmt->fetchColumn();
echo "🎯 TENTATIVES: {$nbAttempts}\n";

echo "\n";

// Vérifier la structure des exercices
$stmt = $pdo->query("SELECT exercise_id, resource_id, exo_name FROM exercises LIMIT 5");
$exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "💻 EXEMPLES D'EXERCICES:\n";
foreach ($exercises as $ex) {
    echo "  - ID {$ex['exercise_id']}: {$ex['exo_name']} (Resource: {$ex['resource_id']})\n";
}

echo "\n✅ Vérification terminée!\n";
?>

