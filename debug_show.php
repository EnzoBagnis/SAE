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

require_once __DIR__ . '/App/bootstrap.php';

$resourceId = (int)($_GET['id'] ?? 1);

echo '<h2>Debug resources/show(' . $resourceId . ')</h2>';
echo '<pre>';

// Step 1: DB connection
echo "1. Testing DB connection...\n";
try {
    $pdo = \Core\Config\DatabaseConnection::getInstance()->getConnection();
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    echo "   OK - Database: $db\n";
} catch (\Throwable $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
    die('</pre>');
}

// Step 2: ResourceRepository
echo "\n2. Testing ResourceRepository::findById($resourceId)...\n";
$resource = null;
try {
    $repo = new \App\Model\ResourceRepository();
    $resource = $repo->findById($resourceId);
    if ($resource) {
        echo "   OK - Resource found: " . $resource->getResourceName() . "\n";
        echo "   getResourceId()    : " . var_export($resource->getResourceId(), true) . "\n";
        echo "   getOwnerMail()     : " . $resource->getOwnerMail() . "\n";
        echo "   getOwnerFirstname(): " . var_export($resource->getOwnerFirstname(), true) . "\n";
        echo "   getOwnerLastname() : " . var_export($resource->getOwnerLastname(), true) . "\n";
        echo "   getOwnerFullName() : " . $resource->getOwnerFullName() . "\n";
        echo "   getDescription()   : " . var_export($resource->getDescription(), true) . "\n";
        echo "   getImagePath()     : " . var_export($resource->getImagePath(), true) . "\n";
    } else {
        echo "   Resource #$resourceId not found (null returned)\n";
    }
} catch (\Throwable $e) {
    echo "   ERROR: " . $e->getMessage() . "\n   " . $e->getTraceAsString() . "\n";
}

// Step 3: ExerciseRepository
echo "\n3. Testing ExerciseRepository::findByResourceIdWithStats($resourceId)...\n";
$exercises = [];
try {
    $exerciseRepo = new \App\Model\ExerciseRepository();
    $exercises = $exerciseRepo->findByResourceIdWithStats($resourceId);
    echo "   OK - Found " . count($exercises) . " exercise(s)\n";
    foreach ($exercises as $ex) {
        echo "   - " . ($ex['exercice_name'] ?? '?') . " (id=" . ($ex['exercice_id'] ?? '?') . ")\n";
    }
} catch (\Throwable $e) {
    echo "   ERROR CLASS  : " . get_class($e) . "\n";
    echo "   ERROR MSG    : " . $e->getMessage() . "\n";
    echo "   FILE:LINE    : " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   TRACE:\n" . $e->getTraceAsString() . "\n";
}

// Step 3b: Raw SQL test directly on PDO
echo "\n3b. Testing raw SQL query directly...\n";
try {
    // Test 1: simple SELECT without GROUP BY
    $stmt = $pdo->prepare("SELECT exercice_id, ressource_id, exercice_name, extention, `date` FROM exercices WHERE ressource_id = :id");
    $stmt->execute(['id' => $resourceId]);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    echo "   Simple SELECT: OK - " . count($rows) . " row(s)\n";
    foreach ($rows as $r) {
        echo "   - id={$r['exercice_id']} name={$r['exercice_name']}\n";
    }
} catch (\Throwable $e) {
    echo "   Simple SELECT ERROR: " . $e->getMessage() . "\n";
}

try {
    // Test 2: with GROUP BY on exercice_id only
    $stmt = $pdo->prepare(
        "SELECT e.exercice_id, e.ressource_id, e.exercice_name, e.extention, e.`date`,
                COUNT(a.attempt_id) AS total_attempts,
                SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END) AS successful_attempts
         FROM exercices e
         LEFT JOIN attempts a ON e.exercice_id = a.exercice_id
         WHERE e.ressource_id = :id
         GROUP BY e.exercice_id"
    );
    $stmt->execute(['id' => $resourceId]);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    echo "   GROUP BY exercice_id: OK - " . count($rows) . " row(s)\n";
} catch (\Throwable $e) {
    echo "   GROUP BY exercice_id ERROR: " . $e->getMessage() . "\n";
}

try {
    // Test 3: MariaDB sql_mode
    $mode = $pdo->query("SELECT @@sql_mode")->fetchColumn();
    echo "   @@sql_mode: $mode\n";
} catch (\Throwable $e) {
    echo "   sql_mode ERROR: " . $e->getMessage() . "\n";
}

try {
    // Test 4: ORDER BY exercice_name on TEXT column
    $stmt = $pdo->prepare("SELECT exercice_id, exercice_name FROM exercices WHERE ressource_id = :id ORDER BY exercice_name ASC");
    $stmt->execute(['id' => $resourceId]);
    echo "   ORDER BY exercice_name (TEXT): OK\n";
} catch (\Throwable $e) {
    echo "   ORDER BY TEXT ERROR: " . $e->getMessage() . "\n";
}

// Step 4: Session check
echo "\n4. Session variables:\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$sessionKeys = ['user_id', 'user_email', 'user_firstname', 'user_lastname', 'is_authenticated'];
foreach ($sessionKeys as $key) {
    echo "   \$_SESSION['$key'] = " . var_export($_SESSION[$key] ?? 'NOT SET', true) . "\n";
}

// Step 5: Check view file exists
echo "\n5. Checking view file...\n";
$viewPath = __DIR__ . '/App/View/resources/details.php';
echo "   Path: $viewPath\n";
echo "   Exists: " . (file_exists($viewPath) ? 'YES' : 'NO') . "\n";

// Step 6: Check BASE_URL
echo "\n6. BASE_URL = " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "\n";

// Step 7: Test view rendering in output buffer
echo "\n7. Testing view render (output buffering)...\n";
if ($resource !== null) {
    try {
        ob_start();
        extract(['resource' => $resource, 'exercises' => $exercises]);
        include $viewPath;
        $viewOutput = ob_get_clean();
        echo "   OK - View rendered successfully (" . strlen($viewOutput) . " bytes)\n";
    } catch (\Throwable $e) {
        ob_end_clean();
        echo "   ERROR in view render: " . $e->getMessage() . "\n";
        echo "   File : " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "   Trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "   SKIP - resource is null\n";
}

// Step 8: Router regex test
echo "\n8. Testing router regex for /resources/{id}...\n";
$routerFile = __DIR__ . '/Core/Router/Router.php';
if (file_exists($routerFile)) {
    $path = '/resources/{id}';
    $escaped = preg_replace('/([.+?^\$\[\](){}\\\\|])/', '\\\\$1', $path);
    $withParams = preg_replace('/\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}/', '([^/]+)', $escaped);
    $regex = '#^' . $withParams . '$#';
    echo "   Pattern after escape : $escaped\n";
    echo "   Pattern after params : $withParams\n";
    echo "   Final regex          : $regex\n";
    $matched = preg_match($regex, '/resources/' . $resourceId, $matches);
    echo "   Match /resources/$resourceId : " . ($matched ? 'YES - param=' . ($matches[1] ?? '?') : 'NO') . "\n";
} else {
    echo "   Router file not found\n";
}

// Step 9: Check logs
echo "\n9. Last 30 lines of php_errors.log:\n";
$logFile = __DIR__ . '/logs/php_errors.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $last = array_slice($lines, -30);
    foreach ($last as $line) {
        echo '   ' . htmlspecialchars(trim($line)) . "\n";
    }
} else {
    echo "   Log file not found: $logFile\n";
}

// Step 10: Check all resources in DB
echo "\n10. All resources in DB:\n";
try {
    $stmt = $pdo->query("SELECT ressource_id, owner_mail, ressource_name FROM ressources ORDER BY ressource_id DESC LIMIT 10");
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "   No resources found in DB\n";
    }
    foreach ($rows as $row) {
        echo "   id={$row['ressource_id']} owner={$row['owner_mail']} name={$row['ressource_name']}\n";
    }
} catch (\Throwable $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

echo '</pre>';
echo '<p style="color:red;font-weight:bold">DELETE debug_show.php after diagnosis!</p>';

