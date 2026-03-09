<?php
/**
 * Diagnostic — SUPPRIMER après débogage
 * Accès: http://localhost/SAE/diag.php
 */
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

ob_start();
try {
    require_once __DIR__ . '/App/bootstrap.php';
} catch (\Throwable $e) {
    echo '<b>BOOTSTRAP FAILED:</b> ' . htmlspecialchars($e->getMessage()) . '<br>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    exit;
}

echo '<h3>Bootstrap OK</h3>';
echo 'BASE_URL = ' . htmlspecialchars(BASE_URL) . '<br>';
echo 'APP_ENV  = ' . htmlspecialchars(\Core\Config\EnvLoader::get('APP_ENV', '?')) . '<br>';

// Test DB
try {
    $pdo = \Core\Config\DatabaseConnection::getInstance()->getConnection();
    $cnt = $pdo->query('SELECT COUNT(*) FROM attempts')->fetchColumn();
    echo '<b style="color:green">DB OK</b> — attempts: ' . $cnt . '<br>';

    $users = $pdo->query('SELECT DISTINCT user_id FROM attempts ORDER BY user_id LIMIT 10')->fetchAll(\PDO::FETCH_COLUMN);
    echo 'Users: ' . implode(', ', $users) . '<br>';

    $exo = $pdo->query('SELECT COUNT(*) FROM exercices')->fetchColumn();
    echo 'Exercices: ' . $exo . '<br>';
} catch (\Throwable $e) {
    echo '<b style="color:red">DB ERROR:</b> ' . htmlspecialchars($e->getMessage()) . '<br>';
}

// Test session
session_start();
echo 'Session status: ' . session_status() . ' (2=active)<br>';
echo 'is_authenticated: ' . (empty($_SESSION['is_authenticated']) ? 'false' : 'true') . '<br>';

// Test instanciation du contrôleur
try {
    $ctrl = new \App\Controller\DashboardApiController();
    echo '<b style="color:green">Controller instancié OK</b><br>';
} catch (\Throwable $e) {
    echo '<b style="color:red">Controller ERREUR:</b> ' . htmlspecialchars($e->getMessage()) . '<br>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

