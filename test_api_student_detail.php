<?php
// Test direct de l'API student

session_start();
$_SESSION['id'] = 1; // Simuler un utilisateur connecté

$_GET['action'] = 'student';
$_GET['id'] = 49; // ID du premier étudiant
$_GET['resource_id'] = 1;

require_once __DIR__ . '/controllers/User/StudentsController.php';

try {
    $controller = new \Controllers\User\StudentsController();
    $controller->getStudent();
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString();
}

