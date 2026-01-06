<?php
// Fichier : test_db.php
require_once __DIR__ . '/models/Database.php';

try {
    $db = Database::getConnection();
    echo "<h1>Diagnostic Base de Données</h1>";
    echo "<p style='color:green'>✅ Connexion réussie à la BDD.</p>";

    // 1. Lister toutes les tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "<p style='color:red'>Aucune table trouvée dans la base !</p>";
    } else {
        echo "<h2>Tables trouvées :</h2><ul>";
        foreach ($tables as $table) {
            echo "<li><strong>$table</strong>";

            // 2. Pour chaque table, lister les colonnes
            echo "<ul style='font-size:0.9em; color:#555;'>";
            $stmtCols = $db->query("DESCRIBE $table");
            $columns = $stmtCols->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
            }
            echo "</ul></li><br>";
        }
        echo "</ul>";
    }

} catch (Exception $e) {
    echo "<h1 style='color:red'>Erreur critique</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>