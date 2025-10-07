<?php
require_once __DIR__ . '/../models/database.php';
require_once __DIR__ . '/../models/inscriptionEnAttente.php';

// Nettoyage automatique des inscriptions expirées (>15 minutes)
$inscriptionModel = new inscriptionEnAttente();
$inscriptionModel->deleteExpired();

echo "Nettoyage effectué : inscriptions expirées supprimées.";
