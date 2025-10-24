<?php
/**
 * SCRIPT TEMPORAIRE - Génération de données de test
 * À SUPPRIMER après les tests
 *
 * Ce script génère des données fictives pour tester les 7 nouvelles classes:
 * - User, PendingRegistration, Dataset, Exercise, Student, EmailService, Code2VecService
 */

require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/PendingRegistration.php';
require_once __DIR__ . '/models/Dataset.php';
require_once __DIR__ . '/models/Exercise.php';

// Configuration
$NB_ENSEIGNANTS = 5;
$NB_DATASETS = 3;
$NB_EXERCISES_PER_DATASET = 8;
$NB_STUDENTS_PER_DATASET = 25;
$NB_ATTEMPTS_PER_STUDENT = 15;

echo "🚀 Démarrage de la génération de données fictives...\n\n";

try {
    $pdo = Database::getConnection();
    $pdo->beginTransaction();

    // ============================================
    // 1. GÉNÉRATION DES ENSEIGNANTS (utilisateurs)
    // ============================================
    echo "📝 Génération de {$NB_ENSEIGNANTS} enseignants...\n";
    $userModel = new User();
    $enseignantIds = [];

    $noms = ['Dupont', 'Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Petit', 'Richard'];
    $prenoms = ['Jean', 'Marie', 'Pierre', 'Sophie', 'Luc', 'Anne', 'Paul', 'Julie'];

    for ($i = 1; $i <= $NB_ENSEIGNANTS; $i++) {
        $nom = $noms[array_rand($noms)];
        $prenom = $prenoms[array_rand($prenoms)];
        $email = strtolower($prenom . '.' . $nom . $i . '@university.edu');
        $password = 'Test123!';
        $code = rand(100000, 999999); // Génération d'un code même si déjà vérifié

        $userModel->create($nom, $prenom, $email, $password, $code, 1);
        $user = $userModel->findByEmail($email);
        $enseignantIds[] = $user['id'];

        echo "  ✓ Enseignant créé: {$prenom} {$nom} ({$email})\n";
    }

    // ============================================
    // 2. GÉNÉRATION DES INSCRIPTIONS EN ATTENTE
    // ============================================
    echo "\n📋 Génération d'inscriptions en attente...\n";
    $pendingModel = new PendingRegistration();

    for ($i = 1; $i <= 3; $i++) {
        $nom = $noms[array_rand($noms)];
        $prenom = $prenoms[array_rand($prenoms)];
        $email = strtolower('pending.' . $prenom . $i . '@test.com');
        $code = rand(100000, 999999);

        $pendingModel->create($nom, $prenom, $email, 'TempPass123!', $code);
        echo "  ✓ Inscription en attente: {$prenom} {$nom} (code: {$code})\n";
    }

    // ============================================
    // 3. GÉNÉRATION DES RESOURCES
    // ============================================
    echo "\n📚 Génération des ressources pédagogiques...\n";
    $resourceIds = [];
    $resourceNames = [
        'Introduction à Python',
        'Structures de données',
        'Algorithmes de tri',
        'Programmation orientée objet',
        'Bases de données'
    ];

    foreach ($enseignantIds as $index => $enseignantId) {
        if ($index < count($resourceNames)) {
            $resourceName = $resourceNames[$index];
            $stmt = $pdo->prepare(
                "INSERT INTO resources (owner_user_id, resource_name, description, date_creation) 
                 VALUES (?, ?, ?, NOW())"
            );
            $stmt->execute([
                $enseignantId,
                $resourceName,
                "Ressource pédagogique pour " . $resourceName
            ]);
            $resourceIds[$enseignantId] = $pdo->lastInsertId();
            echo "  ✓ Ressource créée: {$resourceName}\n";
        }
    }

    // ============================================
    // 4. GÉNÉRATION DES DATASETS
    // ============================================
    echo "\n🗂️  Génération de {$NB_DATASETS} datasets...\n";
    $datasetIds = [];
    $pays = ['France', 'Nouvelle-Calédonie', 'Belgique', 'Suisse', 'Canada'];
    $annees = [2023, 2024, 2025];

    for ($i = 1; $i <= $NB_DATASETS; $i++) {
        $enseignantId = $enseignantIds[array_rand($enseignantIds)];
        $nomDataset = "Dataset_" . $pays[array_rand($pays)] . "_" . $annees[array_rand($annees)] . "_" . $i;

        $stmt = $pdo->prepare(
            "INSERT INTO datasets (enseignant_id, nom_dataset, description, pays, annee, date_import) 
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $enseignantId,
            $nomDataset,
            "Dataset de test pour les étudiants",
            $pays[array_rand($pays)],
            $annees[array_rand($annees)]
        ]);

        $datasetIds[] = $pdo->lastInsertId();
        echo "  ✓ Dataset créé: {$nomDataset}\n";
    }

    // ============================================
    // 5. GÉNÉRATION DES EXERCICES
    // ============================================
    echo "\n💻 Génération des exercices...\n";
    $exerciseNames = [
        ['name' => 'HelloWorld', 'func' => 'hello', 'difficulte' => 'facile'],
        ['name' => 'Addition', 'func' => 'add', 'difficulte' => 'facile'],
        ['name' => 'Fibonacci', 'func' => 'fib', 'difficulte' => 'moyen'],
        ['name' => 'TriBulles', 'func' => 'bubble_sort', 'difficulte' => 'moyen'],
        ['name' => 'Factorielle', 'func' => 'factorial', 'difficulte' => 'facile'],
        ['name' => 'Palindrome', 'func' => 'is_palindrome', 'difficulte' => 'moyen'],
        ['name' => 'RechercheBinaire', 'func' => 'binary_search', 'difficulte' => 'difficile'],
        ['name' => 'PlusGrandCommun', 'func' => 'gcd', 'difficulte' => 'moyen'],
        ['name' => 'InversionChaine', 'func' => 'reverse_string', 'difficulte' => 'facile'],
        ['name' => 'TriRapide', 'func' => 'quick_sort', 'difficulte' => 'difficile']
    ];

    $exerciseIds = [];
    foreach ($datasetIds as $datasetId) {
        $exerciseIds[$datasetId] = [];

        for ($i = 0; $i < $NB_EXERCISES_PER_DATASET; $i++) {
            $exo = $exerciseNames[$i % count($exerciseNames)];

            // Trouver une resource_id pour cet exercice
            $resourceId = !empty($resourceIds) ? array_values($resourceIds)[0] : 1;

            $stmt = $pdo->prepare(
                "INSERT INTO exercises (resource_id, exo_name, funcname, solution, description, difficulte, date_creation) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $resourceId,
                $exo['name'] . '_D' . $datasetId,
                $exo['func'],
                "def {$exo['func']}():\n    pass",
                "Exercice de test: " . $exo['name'],
                $exo['difficulte']
            ]);

            $exerciseId = $pdo->lastInsertId();
            $exerciseIds[$datasetId][] = $exerciseId;

            // Créer des test cases pour chaque exercice
            createTestCases($pdo, $exerciseId, $exo['name']);
        }
        echo "  ✓ {$NB_EXERCISES_PER_DATASET} exercices créés pour dataset {$datasetId}\n";
    }

    // ============================================
    // 6. GÉNÉRATION DES ÉTUDIANTS
    // ============================================
    echo "\n👥 Génération des étudiants...\n";
    $studentIds = [];

    foreach ($datasetIds as $datasetId) {
        $studentIds[$datasetId] = [];

        for ($i = 1; $i <= $NB_STUDENTS_PER_DATASET; $i++) {
            $identifier = "STU_" . str_pad($i, 5, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare(
                "INSERT INTO students (dataset_id, student_identifier, nom_fictif, prenom_fictif, date_creation) 
                 VALUES (?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $datasetId,
                $identifier,
                "Etudiant_" . str_pad($i, 4, '0', STR_PAD_LEFT),
                "Prenom_" . str_pad($i, 4, '0', STR_PAD_LEFT)
            ]);

            $studentIds[$datasetId][] = $pdo->lastInsertId();
        }
        echo "  ✓ {$NB_STUDENTS_PER_DATASET} étudiants créés pour dataset {$datasetId}\n";
    }

    // ============================================
    // 7. GÉNÉRATION DES TENTATIVES (ATTEMPTS)
    // ============================================
    echo "\n🎯 Génération des tentatives...\n";
    $totalAttempts = 0;

    foreach ($datasetIds as $datasetId) {
        foreach ($studentIds[$datasetId] as $studentId) {
            // Chaque étudiant fait des tentatives sur des exercices aléatoires
            $nbAttempts = rand(5, $NB_ATTEMPTS_PER_STUDENT);

            for ($i = 0; $i < $nbAttempts; $i++) {
                $exerciseId = $exerciseIds[$datasetId][array_rand($exerciseIds[$datasetId])];
                $correct = rand(0, 100) > 30 ? 1 : 0; // 70% de réussite

                // Date de soumission aléatoire dans les 30 derniers jours
                $daysAgo = rand(0, 30);
                $hoursAgo = rand(0, 23);
                $submissionDate = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -{$hoursAgo} hours"));

                // Code Python simple
                $code = generateRandomPythonCode($correct);

                // Vecteur AES2 (simplifié pour les tests)
                $aes2 = generateRandomVector();

                $evalSet = ['training', 'validation', 'test'][rand(0, 2)];

                $stmt = $pdo->prepare(
                    "INSERT INTO attempts (student_id, exercise_id, submission_date, extension, correct, upload, eval_set, aes2, date_creation) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
                );
                $stmt->execute([
                    $studentId,
                    $exerciseId,
                    $submissionDate,
                    'py',
                    $correct,
                    $code,
                    $evalSet,
                    $aes2
                ]);

                $totalAttempts++;
            }
        }
        echo "  ✓ Tentatives créées pour dataset {$datasetId}\n";
    }

    echo "\n📊 Total de tentatives créées: {$totalAttempts}\n";

    // ============================================
    // 8. MISE À JOUR DES STATISTIQUES
    // ============================================
    echo "\n🔄 Mise à jour des statistiques des datasets...\n";
    foreach ($datasetIds as $datasetId) {
        // Mise à jour manuelle des statistiques au lieu d'utiliser la procédure stockée
        $stmt = $pdo->prepare("
            UPDATE datasets d
            SET 
                nb_exercices = (SELECT COUNT(*) FROM exercises WHERE resource_id IN (SELECT resource_id FROM resources WHERE owner_user_id = d.enseignant_id)),
                nb_etudiants = (SELECT COUNT(*) FROM students WHERE dataset_id = d.dataset_id),
                nb_tentatives = (SELECT COUNT(*) FROM attempts a JOIN students s ON a.student_id = s.student_id WHERE s.dataset_id = d.dataset_id)
            WHERE dataset_id = ?
        ");
        $stmt->execute([$datasetId]);
        echo "  ✓ Statistiques mises à jour pour dataset {$datasetId}\n";
    }

    $pdo->commit();

    echo "\n✅ GÉNÉRATION TERMINÉE AVEC SUCCÈS!\n\n";
    echo "📈 Résumé:\n";
    echo "  - Enseignants: {$NB_ENSEIGNANTS}\n";
    echo "  - Datasets: {$NB_DATASETS}\n";
    echo "  - Exercices par dataset: {$NB_EXERCISES_PER_DATASET}\n";
    echo "  - Étudiants par dataset: {$NB_STUDENTS_PER_DATASET}\n";
    echo "  - Tentatives totales: {$totalAttempts}\n";
    echo "\n🧪 Vous pouvez maintenant tester vos classes!\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

// ============================================
// FONCTIONS AUXILIAIRES
// ============================================

/**
 * Crée des test cases pour un exercice
 */
function createTestCases($pdo, $exerciseId, $exerciseName) {
    $testCases = [
        [
            'input' => json_encode(['args' => []]),
            'output' => json_encode(['result' => 'Hello, World!']),
            'order' => 1
        ],
        [
            'input' => json_encode(['args' => [5]]),
            'output' => json_encode(['result' => 120]),
            'order' => 2
        ],
        [
            'input' => json_encode(['args' => [10]]),
            'output' => json_encode(['result' => 55]),
            'order' => 3
        ]
    ];

    foreach ($testCases as $tc) {
        $stmt = $pdo->prepare(
            "INSERT INTO test_cases (exercise_id, input_data, expected_output, test_order) 
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $exerciseId,
            $tc['input'],
            $tc['output'],
            $tc['order']
        ]);
    }
}

/**
 * Génère du code Python aléatoire
 */
function generateRandomPythonCode($correct) {
    $correctCode = [
        "def solution(n):\n    return n * 2",
        "def solution(arr):\n    return sorted(arr)",
        "def solution(x, y):\n    return x + y",
        "def solution():\n    return 'Hello, World!'",
        "def solution(n):\n    if n <= 1:\n        return n\n    return solution(n-1) + solution(n-2)"
    ];

    $incorrectCode = [
        "def solution(n):\n    return n * 3  # Erreur logique",
        "def solution(arr):\n    return arr  # Oubli du tri",
        "def solution(x, y):\n    return x - y  # Mauvaise opération",
        "def solution():\n    return 'Goodbye'  # Mauvais résultat",
        "def solution(n):\n    return 0  # Implémentation incomplète"
    ];

    if ($correct) {
        return $correctCode[array_rand($correctCode)];
    } else {
        return $incorrectCode[array_rand($incorrectCode)];
    }
}

/**
 * Génère un vecteur aléatoire pour AES2
 */
function generateRandomVector() {
    $vector = [];
    for ($i = 0; $i < 384; $i++) {
        $vector[] = round(rand(-100, 100) / 100, 4);
    }
    return json_encode($vector);
}
?>
