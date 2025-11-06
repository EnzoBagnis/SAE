<?php
// Script de test pour vérifier les données dans la base

require_once __DIR__ . '/models/Database.php';

$db = Database::getConnection();

echo "<h2>Test de la base de données</h2>";

// 1. Vérifier les ressources
echo "<h3>1. Ressources disponibles :</h3>";
$stmt = $db->query("SELECT resource_id, resource_name FROM resources");
$resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($resources);
echo "</pre>";

// 2. Vérifier les exercices pour la ressource ID 1
echo "<h3>2. Exercices de la ressource ID 1 :</h3>";
$stmt = $db->prepare("SELECT exercise_id, exo_name, resource_id FROM exercises WHERE resource_id = 1");
$stmt->execute();
$exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($exercises);
echo "</pre>";

// 3. Vérifier les étudiants
echo "<h3>3. Étudiants disponibles :</h3>";
$stmt = $db->query("SELECT student_id, student_identifier, dataset_id FROM students LIMIT 10");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($students);
echo "</pre>";

// 4. Vérifier les tentatives
echo "<h3>4. Tentatives (sample) :</h3>";
$stmt = $db->query("SELECT attempt_id, student_id, exercise_id FROM attempts LIMIT 10");
$attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($attempts);
echo "</pre>";

// 5. Vérifier les étudiants qui ont des tentatives pour la ressource 1
echo "<h3>5. Étudiants avec tentatives pour la ressource ID 1 :</h3>";
$stmt = $db->prepare("
    SELECT DISTINCT s.student_id, s.student_identifier, s.nom_fictif, s.prenom_fictif
    FROM students s
    JOIN attempts a ON s.student_id = a.student_id
    JOIN exercises e ON a.exercise_id = e.exercise_id
    WHERE e.resource_id = 1
    ORDER BY s.student_identifier
    LIMIT 10
");
$stmt->execute();
$studentsWithAttempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($studentsWithAttempts);
echo "</pre>";

echo "<h3>Nombre total : " . count($studentsWithAttempts) . "</h3>";

