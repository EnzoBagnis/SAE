<?php
require_once '../models/Database.php';

// Suppression automatique des inscriptions expirées (plus de 15 minutes)
$bdd = Database::getConnection();
$deleteExpired = $bdd->prepare("DELETE FROM inscriptions_en_attente WHERE date_creation < DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
$deleteExpired->execute();