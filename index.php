<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ========================================
// GESTION DES URLs PROPRES
// ========================================

// Récupérer l'URL demandée
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Enlever le nom du script si présent
$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$requestUri = substr($requestUri, strlen($scriptName));
$requestUri = trim($requestUri, '/');

// Si une route est détectée et qu'il n'y a pas déjà d'action via GET
if (!empty($requestUri) && $requestUri !== 'index.php' && !isset($_GET['action'])) {
    // Extraire l'action de l'URL
    $parts = explode('/', $requestUri);
    $_GET['action'] = $parts[0];
}

// ========================================
// CHARGEMENT DU ROUTEUR
// ========================================

require_once 'config/router.php';

$router = new Router();
$router->route();
