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
?>

