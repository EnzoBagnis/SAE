<?php
/**
 * SCRIPT DE NETTOYAGE - Supprime toutes les données de test
 * À UTILISER AVEC PRÉCAUTION!
 *
 * Ce script supprime TOUTES les données générées pour les tests
 */

require_once __DIR__ . '/models/Database.php';

echo "⚠️  ATTENTION: NETTOYAGE DE LA BASE DE DONNÉES\n";
echo str_repeat("=", 60) . "\n\n";

echo "Ce script va supprimer TOUTES les données de la base.\n";
echo "Êtes-vous sûr de vouloir continuer? (yes/no): ";

// Pour l'exécution en ligne de commande
if (php_sapi_name() === 'cli') {
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);

    if (strtolower($line) !== 'yes') {
        echo "\n❌ Opération annulée.\n";
        exit;
    }
} else {
    echo "\n⚠️  Script doit être exécuté en ligne de commande pour confirmation.\n";
    echo "Ou modifiez le script pour supprimer la confirmation.\n\n";

    // Décommentez la ligne suivante pour permettre l'exécution sans confirmation
    // $line = 'yes';

    if (!isset($line) || strtolower($line) !== 'yes') {
        exit;
    }
}

echo "\n🗑️  Démarrage du nettoyage...\n\n";

$pdo = null;

try {
    $pdo = Database::getConnection();

    // Pas besoin de transaction car ALTER TABLE fait un commit implicite
    // $pdo->beginTransaction();

    // Désactiver temporairement les contraintes de clés étrangères
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    echo "📝 Suppression des données...\n";

    // Ordre de suppression (du plus dépendant au moins dépendant)
    $tables = [
        'attempts' => 'Tentatives',
        'test_cases' => 'Test cases',
        'students' => 'Étudiants',
        'exercises' => 'Exercices',
        'datasets' => 'Datasets',
        'resource_professors_access' => 'Accès partagés',
        'resources' => 'Ressources',
        'inscriptions_en_attente' => 'Inscriptions en attente',
        'utilisateurs' => 'Utilisateurs'
    ];

    foreach ($tables as $table => $description) {
        // Compter les enregistrements avant suppression
        $stmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
        $count = $stmt->fetchColumn();

        // Supprimer
        $pdo->exec("DELETE FROM `{$table}`");

        // Réinitialiser l'auto-increment
        $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");

        echo "  ✓ {$description}: {$count} enregistrements supprimés\n";
    }

    // Réactiver les contraintes de clés étrangères
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Pas besoin de commit car ALTER TABLE l'a déjà fait
    // $pdo->commit();

    echo "\n✅ NETTOYAGE TERMINÉ AVEC SUCCÈS!\n";
    echo "\n📊 La base de données est maintenant vide.\n";
    echo "Vous pouvez relancer temp_generate_fake_data.php pour générer de nouvelles données.\n\n";

} catch (Exception $e) {
    // Réactiver les contraintes en cas d'erreur
    if ($pdo !== null) {
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        } catch (Exception $e2) {
            // Ignorer
        }
    }

    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
