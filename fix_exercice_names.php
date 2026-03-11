<?php
/**
 * Script de migration :
 * 1. Agrandit la colonne attempts.upload de VARCHAR(20) → MEDIUMTEXT
 * 2. Met à jour les exercice_name qui sont des hash MD5 tronqués (20 hex chars)
 *    en extrayant le nom de la fonction Python depuis les tentatives associées.
 *
 * Usage: accéder via navigateur ou en CLI: php fix_exercice_names.php
 */

$env = parse_ini_file(__DIR__ . '/config/.env');
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4",
    $env['DB_USER'], $env['DB_PASS'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ─────────────────────────────────────────────────────────────────────────────
// ÉTAPE 1 : Agrandir attempts.upload si encore VARCHAR(20)
// ─────────────────────────────────────────────────────────────────────────────
$colInfo = $pdo->query(
    "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attempts' AND COLUMN_NAME = 'upload'"
)->fetchColumn();

if ($colInfo && stripos($colInfo, 'varchar') !== false) {
    echo "⚙  Agrandissement de attempts.upload ($colInfo → MEDIUMTEXT)...\n";
    $pdo->exec("ALTER TABLE attempts MODIFY COLUMN upload MEDIUMTEXT");
    echo "✓  attempts.upload est maintenant MEDIUMTEXT.\n\n";
} else {
    echo "✓  attempts.upload est déjà $colInfo, pas de migration de colonne nécessaire.\n\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// ÉTAPE 2 : Renommer les exercice_name qui sont des hash MD5 tronqués à 20 chars
// ─────────────────────────────────────────────────────────────────────────────
$stmt = $pdo->query(
    "SELECT exercice_id, exercice_name
     FROM exercices
     WHERE exercice_name REGEXP '^[0-9a-f]{20}$'"
);
$exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Exercices avec hash MD5 trouvés : " . count($exercises) . "\n";

$updated = 0;
$skipped = 0;

foreach ($exercises as $ex) {
    $exerciceId   = $ex['exercice_id'];
    $exerciceName = $ex['exercice_name'];

    // Chercher une tentative ayant du code avec une définition de fonction
    $stmt2 = $pdo->prepare(
        "SELECT upload FROM attempts
         WHERE exercice_id = :eid
           AND upload LIKE 'def %'
         LIMIT 1"
    );
    $stmt2->execute(['eid' => $exerciceId]);
    $upload = $stmt2->fetchColumn();

    if (!$upload) {
        echo "  SKIP  exercice_id=$exerciceId ($exerciceName) : aucune tentative avec 'def'\n";
        $skipped++;
        continue;
    }

    // Extraire le nom de la première fonction Python
    if (!preg_match('/def\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $upload, $matches)) {
        echo "  SKIP  exercice_id=$exerciceId ($exerciceName) : pas de def trouvé dans upload\n";
        $skipped++;
        continue;
    }

    $funcName = mb_substr($matches[1], 0, 20);

    // Vérifier qu'un exercice avec ce nom n'existe pas déjà pour la même ressource
    $stmt3 = $pdo->prepare(
        "SELECT exercice_id FROM exercices
         WHERE ressource_id = (SELECT ressource_id FROM exercices WHERE exercice_id = :eid)
           AND exercice_name = :name
           AND exercice_id != :eid
         LIMIT 1"
    );
    $stmt3->execute(['eid' => $exerciceId, 'name' => $funcName]);
    $conflict = $stmt3->fetchColumn();

    if ($conflict) {
        echo "  MERGE exercice_id=$exerciceId ($exerciceName) -> $funcName : fusion vers exercice_id=$conflict\n";
        $pdo->prepare("UPDATE attempts SET exercice_id = :target WHERE exercice_id = :src")
            ->execute(['target' => $conflict, 'src' => $exerciceId]);
        $pdo->prepare("DELETE FROM exercices WHERE exercice_id = :eid")
            ->execute(['eid' => $exerciceId]);
        $updated++;
        continue;
    }

    // Mettre à jour le nom
    $pdo->prepare("UPDATE exercices SET exercice_name = :name WHERE exercice_id = :eid")
        ->execute(['name' => $funcName, 'eid' => $exerciceId]);

    echo "  OK    exercice_id=$exerciceId : '$exerciceName' -> '$funcName'\n";
    $updated++;
}

echo "\nTerminé : $updated mis à jour / fusionnés, $skipped ignorés.\n";


$env = parse_ini_file(__DIR__ . '/config/.env');
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4",
    $env['DB_USER'], $env['DB_PASS'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Récupérer tous les exercices dont le nom ressemble à un hash MD5
$stmt = $pdo->query(
    "SELECT exercice_id, exercice_name
     FROM exercices
     WHERE exercice_name REGEXP '^[0-9a-f]{20}$'"
);
$exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Exercices avec hash MD5 trouvés : " . count($exercises) . "\n";

$updated = 0;
$skipped = 0;

foreach ($exercises as $ex) {
    $exerciceId   = $ex['exercice_id'];
    $exerciceName = $ex['exercice_name'];

    // Chercher une tentative ayant du code avec une définition de fonction
    $stmt2 = $pdo->prepare(
        "SELECT upload FROM attempts
         WHERE exercice_id = :eid
           AND upload LIKE '%def %'
         LIMIT 1"
    );
    $stmt2->execute(['eid' => $exerciceId]);
    $upload = $stmt2->fetchColumn();

    if (!$upload) {
        echo "  SKIP  exercice_id=$exerciceId ($exerciceName) : aucune tentative avec 'def'\n";
        $skipped++;
        continue;
    }

    // Extraire le nom de la première fonction Python
    if (!preg_match('/def\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $upload, $matches)) {
        echo "  SKIP  exercice_id=$exerciceId ($exerciceName) : pas de def trouvé dans upload\n";
        $skipped++;
        continue;
    }

    $funcName = mb_substr($matches[1], 0, 20);

    // Vérifier qu'un exercice avec ce nom n'existe pas déjà pour la même ressource
    $stmt3 = $pdo->prepare(
        "SELECT exercice_id FROM exercices
         WHERE ressource_id = (SELECT ressource_id FROM exercices WHERE exercice_id = :eid)
           AND exercice_name = :name
           AND exercice_id != :eid
         LIMIT 1"
    );
    $stmt3->execute(['eid' => $exerciceId, 'name' => $funcName]);
    $conflict = $stmt3->fetchColumn();

    if ($conflict) {
        echo "  MERGE exercice_id=$exerciceId ($exerciceName) -> $funcName : conflit avec exercice_id=$conflict, fusion des tentatives\n";
        // Réassigner les tentatives vers l'exercice existant
        $pdo->prepare("UPDATE attempts SET exercice_id = :target WHERE exercice_id = :src")
            ->execute(['target' => $conflict, 'src' => $exerciceId]);
        // Supprimer le doublon
        $pdo->prepare("DELETE FROM exercices WHERE exercice_id = :eid")
            ->execute(['eid' => $exerciceId]);
        $updated++;
        continue;
    }

    // Mettre à jour le nom
    $pdo->prepare("UPDATE exercices SET exercice_name = :name WHERE exercice_id = :eid")
        ->execute(['name' => $funcName, 'eid' => $exerciceId]);

    echo "  OK    exercice_id=$exerciceId : '$exerciceName' -> '$funcName'\n";
    $updated++;
}

echo "\nTerminé : $updated mis à jour / fusionnés, $skipped ignorés.\n";

