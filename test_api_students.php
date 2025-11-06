<?php
// Script de test pour l'API students

// Simuler une requête vers l'API students
$_GET['action'] = 'students';
$_GET['page'] = 1;
$_GET['perPage'] = 15;
$_GET['resource_id'] = 1;

// Démarrer la session
session_start();
$_SESSION['id'] = 1; // Simuler un utilisateur connecté

// Charger le contrôleur
require_once __DIR__ . '/controllers/User/StudentsController.php';

try {
    $controller = new \Controllers\User\StudentsController();
    $controller->getStudents();
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString();
}

