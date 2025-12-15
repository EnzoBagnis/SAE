<?php
// Test de connexion à la base de données
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Connexion DB</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h3 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        ul { margin: 10px 0; padding-left: 30px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .status { font-size: 24px; margin-right: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test de Configuration</h1>

<?php
echo "<h3>📊 Test de connexion à la base de données</h3>";

try {
    require_once __DIR__ . '/models/Database.php';

    echo "<p><span class='status success'>✓</span> Fichier Database.php chargé</p>";

    $db = Database::getConnection();

    echo "<p><span class='status success'>✓</span> <strong>Connexion PDO réussie</strong></p>";

    // Test de requête simple
    $stmt = $db->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<p><span class='status success'>✓</span> Base de données active: <strong>" . $result['db_name'] . "</strong></p>";

    // Vérifier les tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<p><span class='status success'>✓</span> Tables trouvées (" . count($tables) . "):</p>";
    echo "<ul>";
    $requiredTables = ['utilisateurs', 'resources', 'exercises', 'test_cases', 'students', 'attempts', 'datasets'];
    foreach ($requiredTables as $table) {
        if (in_array($table, $tables)) {
            echo "<li class='success'>✓ $table</li>";
        } else {
            echo "<li class='error'>✗ $table (MANQUANTE !)</li>";
        }
    }
    echo "</ul>";

    // Vérifier session
    echo "<h3>🔐 Test de session</h3>";
    if (isset($_SESSION['id'])) {
        echo "<p><span class='status success'>✓</span> <strong>Session active</strong> - User ID: " . $_SESSION['id'] . "</p>";
        echo "<p style='color: green; font-weight: bold;'>→ Vous pouvez importer des exercices !</p>";
    } else {
        echo "<p><span class='status warning'>⚠</span> <strong>Pas de session active</strong></p>";
        echo "<p style='color: orange;'>→ Vous devez vous connecter pour importer</p>";
        echo "<p><a href='index.php?action=login' style='color: #007bff; font-weight: bold;'>→ Aller à la page de connexion</a></p>";
    }

    echo "<h3 class='success'>✅ TOUS LES TESTS SONT PASSÉS !</h3>";
    echo "<p style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>";
    echo "<strong>Votre système est prêt pour l'import !</strong><br>";
    echo "→ <a href='test_import_final.html'>Aller à la page d'import</a>";
    echo "</p>";

} catch (Exception $e) {
    echo "<h3 class='error'>❌ Erreur détectée</h3>";
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Fichier:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Ligne:</strong> " . $e->getLine() . "</p>";
    echo "<details><summary>Voir le trace complet</summary><pre>" . $e->getTraceAsString() . "</pre></details>";
    echo "</div>";
}

// Vérifier le fichier .env
echo "<h3>⚙️ Configuration .env</h3>";
$envPath = __DIR__ . '/config/.env';
if (file_exists($envPath)) {
    echo "<p><span class='status success'>✓</span> Fichier .env trouvé</p>";
    $env = parse_ini_file($envPath);
    echo "<pre>";
    echo "DB_HOST = " . ($env['DB_HOST'] ?? '<span class="error">NON DÉFINI</span>') . "\n";
    echo "DB_USER = " . ($env['DB_USER'] ?? '<span class="error">NON DÉFINI</span>') . "\n";
    echo "DB_NAME = " . ($env['DB_NAME'] ?? '<span class="error">NON DÉFINI</span>') . "\n";
    echo "DB_PASS = " . (isset($env['DB_PASS']) ? '[défini]' : '<span class="warning">vide</span>') . "\n";
    echo "</pre>";
} else {
    echo "<p><span class='status error'>✗</span> <strong>Fichier .env introuvable</strong> à: <code>$envPath</code></p>";
    echo "<p style='color: red;'>→ Créez ce fichier avec la configuration DB !</p>";
}

// Vérifier les fichiers API
echo "<h3>📁 Fichiers API d'import</h3>";
$apiFiles = [
    'api_import_exercises.php' => 'Import exercices',
    'api_import_attempts.php' => 'Import tentatives',
    'example_exercises_import.json' => 'Fichier exemple'
];

foreach ($apiFiles as $file => $desc) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p><span class='status success'>✓</span> $desc : <code>$file</code></p>";
    } else {
        echo "<p><span class='status error'>✗</span> $desc : <code>$file</code> <strong>MANQUANT</strong></p>";
    }
}
?>

    </div>
</body>
</html>

