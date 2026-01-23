<?php

/**
 * Application Entry Point
 * Routes all requests through the main router
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Start session FIRST
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définition de la racine du projet pour le navigateur
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$baseDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
if ($baseDir === '/') {
    $baseDir = '';
}

define('BASE_URL', $protocol . "://" . $host . $baseDir);

// Désactiver l'affichage des erreurs pour les actions API (après le démarrage de session)
$action = $_GET['action'] ?? 'home';
$apiActions = ['students', 'resources', 'resource', 'upload', 'vector']; // Retiré 'student' temporairement
if (in_array($action, $apiActions)) {
    ini_set('display_errors', 0);
} else {
    ini_set('display_errors', 1);
}

// Load router
require_once 'config/router.php';

// Initialize and execute router
$router = new Router();
$router->route();
