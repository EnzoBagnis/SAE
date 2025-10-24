<?php
require_once __DIR__ . '/models/Database.php';

$pdo = Database::getConnection();

echo "=== STRUCTURE DE LA TABLE EXERCISES ===\n\n";

$stmt = $pdo->query("DESCRIBE exercises");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo "- {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
}

echo "\n=== STRUCTURE DE LA TABLE DATASETS ===\n\n";

$stmt = $pdo->query("DESCRIBE datasets");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo "- {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
}

echo "\n=== STRUCTURE DE LA TABLE RESOURCES ===\n\n";

try {
    $stmt = $pdo->query("DESCRIBE resources");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
    }
} catch (Exception $e) {
    echo "Table resources n'existe pas ou erreur: {$e->getMessage()}\n";
}

echo "\n=== STRUCTURE DE LA TABLE STUDENTS ===\n\n";

try {
    $stmt = $pdo->query("DESCRIBE students");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
    }
} catch (Exception $e) {
    echo "Table students n'existe pas ou erreur: {$e->getMessage()}\n";
}

echo "\n=== RELATION EXERCISES <-> DATASETS ===\n\n";

try {
    $stmt = $pdo->query("
        SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND (TABLE_NAME = 'exercises' OR REFERENCED_TABLE_NAME = 'exercises')
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($keys)) {
        echo "Aucune clé étrangère trouvée pour exercises\n";
    } else {
        foreach ($keys as $key) {
            echo "- {$key['TABLE_NAME']}.{$key['COLUMN_NAME']} -> {$key['REFERENCED_TABLE_NAME']}.{$key['REFERENCED_COLUMN_NAME']}\n";
        }
    }
} catch (Exception $e) {
    echo "Erreur: {$e->getMessage()}\n";
}
?>
