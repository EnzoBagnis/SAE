<?php
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/InscriptionEnAttente.php';

// Nettoyage automatique des inscriptions expirées (>15 minutes)
$inscriptionModel = new InscriptionEnAttente();
$inscriptionModel->deleteExpired();

echo "Nettoyage effectué : inscriptions expirées supprimées.";
