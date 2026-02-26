<?php
/**
 * TEMPORARY DEBUG FILE - DELETE AFTER USE
 * Simulates resources/show() with full error display.
 */

// Basic auth protection
$token = $_GET['token'] ?? '';
if ($token !== 'studtraj_debug_2026') {
    http_response_code(403);
    die('Forbidden');
}

ini_set('display_errors', '1');
error_reporting(E_ALL);

// Flush output after each step so we see exactly where it stops
ob_implicit_flush(true);
if (ob_get_level()) ob_end_flush();

require_once __DIR__ . '/App/bootstrap.php';

$resourceId = (int)($_GET['id'] ?? 4);
echo "<h2>Debug show($resourceId)</h2><pre>";
flush();

// Step 1 - DB
echo "1. DB...\n"; flush();
try {
    $pdo = \Core\Config\DatabaseConnection::getInstance()->getConnection();
    echo "   OK: " . $pdo->query('SELECT DATABASE()')->fetchColumn() . "\n"; flush();
} catch (\Throwable $e) { echo "   ERR: " . $e->getMessage() . "\n"; die(); }

// Step 2 - sql_mode
echo "\n2. sql_mode:\n"; flush();
echo "   " . $pdo->query("SELECT @@sql_mode")->fetchColumn() . "\n"; flush();

// Step 3 - exercices table exists?
echo "\n3. Tables check:\n"; flush();
$tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
echo "   Tables: " . implode(', ', $tables) . "\n"; flush();

// Step 4 - Raw SELECT simple
echo "\n4. Raw SELECT from exercices WHERE ressource_id=$resourceId:\n"; flush();
try {
    $stmt = $pdo->prepare("SELECT * FROM exercices WHERE ressource_id = :id");
    $stmt->execute(['id' => $resourceId]);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    echo "   Rows found: " . count($rows) . "\n";
    foreach ($rows as $r) {
        echo "   -> id={$r['exercice_id']} name={$r['exercice_name']}\n";
    }
    flush();
} catch (\Throwable $e) { echo "   ERR: " . $e->getMessage() . "\n"; flush(); }

// Step 5 - Raw query with backtick date
echo "\n5. Raw SELECT with backtick date:\n"; flush();
try {
    $stmt = $pdo->prepare("SELECT exercice_id, exercice_name, `date` FROM exercices WHERE ressource_id = :id");
    $stmt->execute(['id' => $resourceId]);
    echo "   OK: " . count($stmt->fetchAll()) . " rows\n"; flush();
} catch (\Throwable $e) { echo "   ERR: " . $e->getMessage() . "\n"; flush(); }

// Step 6 - GROUP BY exercice_id only
echo "\n6. GROUP BY exercice_id:\n"; flush();
try {
    $stmt = $pdo->prepare(
        "SELECT e.exercice_id,
                e.ressource_id,
                e.exercice_name,
                e.extention,
                e.`date`,
                COUNT(a.attempt_id) AS total_attempts,
                SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END) AS successful_attempts
         FROM exercices e
         LEFT JOIN attempts a ON e.exercice_id = a.exercice_id
         WHERE e.ressource_id = :id
         GROUP BY e.exercice_id"
    );
    $stmt->execute(['id' => $resourceId]);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    echo "   OK: " . count($rows) . " rows\n"; flush();
} catch (\Throwable $e) { echo "   ERR: " . $e->getMessage() . "\n"; flush(); }

// Step 7 - ORDER BY exercice_name (TEXT column)
echo "\n7. ORDER BY exercice_name (TEXT):\n"; flush();
try {
    $stmt = $pdo->prepare(
        "SELECT e.exercice_id,
                e.exercice_name,
                e.extention,
                e.`date`,
                COUNT(a.attempt_id) AS total_attempts,
                SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END) AS successful_attempts
         FROM exercices e
         LEFT JOIN attempts a ON e.exercice_id = a.exercice_id
         WHERE e.ressource_id = :id
         GROUP BY e.exercice_id
         ORDER BY e.exercice_name ASC"
    );
    $stmt->execute(['id' => $resourceId]);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    echo "   OK: " . count($rows) . " rows\n"; flush();
} catch (\Throwable $e) { echo "   ERR: " . $e->getMessage() . "\n"; flush(); }

// Step 8 - Check deployed ExerciseRepository file hash
echo "\n8. ExerciseRepository.php deployed version:\n"; flush();
$repoFile = __DIR__ . '/App/Model/ExerciseRepository.php';
echo "   MD5  : " . md5_file($repoFile) . "\n";
echo "   Size : " . filesize($repoFile) . " bytes\n";
// Show line containing GROUP BY
$lines = file($repoFile);
foreach ($lines as $i => $line) {
    if (stripos($line, 'GROUP BY') !== false) {
        echo "   Line " . ($i+1) . ": " . trim($line) . "\n";
    }
}
flush();

// Step 9 - Try ExerciseRepository class directly
echo "\n9. ExerciseRepository::findByResourceIdWithStats:\n"; flush();
try {
    echo "   Instantiating...\n"; flush();
    $repo = new \App\Model\ExerciseRepository();
    echo "   Calling findByResourceIdWithStats...\n"; flush();
    $result = $repo->findByResourceIdWithStats($resourceId);
    echo "   OK: " . count($result) . " rows\n"; flush();
} catch (\Throwable $e) {
    echo "   ERR class: " . get_class($e) . "\n";
    echo "   ERR msg  : " . $e->getMessage() . "\n";
    echo "   ERR file : " . $e->getFile() . ":" . $e->getLine() . "\n";
    flush();
}

echo "\nDONE</pre>";
