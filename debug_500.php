<?php
/**
 * Script de diagnostic temporaire - à supprimer après debug
 * Accéder via : http://localhost/SAE/debug_500.php
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Diagnostic SAE</h2>";

// 1. Version PHP
echo "<p><b>PHP Version:</b> " . PHP_VERSION . "</p>";

// 2. .env
$envPath = __DIR__ . '/../config/.env';
echo "<p><b>.env path:</b> " . realpath($envPath) . " — " . (file_exists($envPath) ? '✅ EXISTS' : '❌ NOT FOUND') . "</p>";

// 3. Bootstrap
echo "<p><b>Bootstrap:</b> ";
try {
    require_once __DIR__ . '/App/bootstrap.php';
    echo "✅ OK</p>";
} catch (\Throwable $e) {
    echo "❌ " . get_class($e) . ": " . $e->getMessage() . "</p>";
}

// 4. DB Connection
echo "<p><b>Database:</b> ";
try {
    $pdo = \Core\Config\DatabaseConnection::getInstance()->getConnection();
    echo "✅ Connected</p>";
} catch (\Throwable $e) {
    echo "❌ " . $e->getMessage() . "</p>";
    die();
}

// 5. Tables
$tables = ['attempts', 'exercices', 'ressources', 'students'];
foreach ($tables as $t) {
    echo "<p><b>Table '$t':</b> ";
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        echo "✅ $count rows</p>";
    } catch (\Throwable $e) {
        echo "❌ " . $e->getMessage() . "</p>";
    }
}

// 6. Colonne student_id dans attempts
echo "<p><b>Colonne student_id dans attempts:</b> ";
try {
    $pdo->query("SELECT student_id FROM attempts LIMIT 1")->fetchColumn();
    echo "✅ EXISTS</p>";
} catch (\Throwable $e) {
    echo "❌ " . $e->getMessage() . "</p>";
}

// 7. Colonne user_id dans attempts (l'ancienne ?)
echo "<p><b>Colonne user_id dans attempts:</b> ";
try {
    $pdo->query("SELECT user_id FROM attempts LIMIT 1")->fetchColumn();
    echo "⚠️ EXISTS (ancienne colonne ?)</p>";
} catch (\Throwable $e) {
    echo "— N'existe pas (normal)</p>";
}

// 8. Structure de la table attempts
echo "<p><b>Colonnes de attempts:</b> ";
try {
    $cols = $pdo->query("SHOW COLUMNS FROM attempts")->fetchAll(\PDO::FETCH_COLUMN);
    echo implode(', ', $cols) . "</p>";
} catch (\Throwable $e) {
    echo "❌ " . $e->getMessage() . "</p>";
}

// 9. Simuler la requête du IaController::index
echo "<p><b>Requête COUNT(DISTINCT student_id):</b> ";
try {
    $count = $pdo->query("SELECT COUNT(DISTINCT student_id) FROM attempts")->fetchColumn();
    echo "✅ $count</p>";
} catch (\Throwable $e) {
    echo "❌ " . $e->getMessage() . "</p>";
}

// 10. Session
echo "<p><b>Session:</b> ";
session_start();
echo "is_authenticated = " . var_export($_SESSION['is_authenticated'] ?? false, true) . "</p>";

echo "<hr><p><i>Supprimez ce fichier après diagnostic.</i></p>";

