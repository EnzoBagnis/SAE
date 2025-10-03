<?php
echo "<h2>Diagnostic du problème</h2>";

// Vérifier le chemin actuel
echo "<strong>Répertoire actuel :</strong> " . __DIR__ . "<br><br>";

// Chemin vers le controller
$controllerFile = __DIR__ . '/controllers/accueilController.php';
echo "<strong>Chemin testé :</strong> $controllerFile<br>";
echo "<strong>Fichier existe ?</strong> " . (file_exists($controllerFile) ? '✅ OUI' : '❌ NON') . "<br><br>";

// Lister les fichiers dans controllers
echo "<strong>Fichiers dans le dossier controllers/ :</strong><br>";
if (is_dir(__DIR__ . '/controllers')) {
    $files = scandir(__DIR__ . '/controllers');
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "- $file<br>";
        }
    }
} else {
    echo "❌ Le dossier controllers/ n'existe pas !<br>";
}

echo "<br><strong>Structure attendue :</strong><br>";
echo "SAE/<br>";
echo "├── debug.php (ce fichier)<br>";
echo "├── index.php<br>";
echo "├── config/<br>";
echo "│   └── router.php<br>";
echo "└── controllers/<br>";
echo "    ├── accueilController.php<br>";
echo "    └── inscriptionController.php<br>";