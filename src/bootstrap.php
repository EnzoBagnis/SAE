<?php

/**
 * Bootstrap - Application initialization
 *
 * This file initializes the application following Clean Architecture principles.
 * It sets up the dependency injection container and routing.
 */

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('display_errors', 1);

// Define base paths
define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');

// Define base URL (only if running in web context)
if (php_sapi_name() !== 'cli') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    if ($baseDir === '/') {
        $baseDir = '';
    }
    define('BASE_URL', $protocol . "://" . $host . $baseDir);
} else {
    define('BASE_URL', 'http://localhost');
}

// Autoloader for new architecture
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $prefix = '';
    $baseDir = SRC_PATH . '/';

    // Check if class uses our namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        $file = $baseDir . str_replace('\\', '/', $class) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

// Require composer autoload for external dependencies
$autoloadPath = ROOT_PATH . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('<h1>Erreur de configuration</h1>' .
        '<p><strong>Le fichier vendor/autoload.php est manquant.</strong></p>' .
        '<p>Veuillez exécuter la commande suivante à la racine du projet :</p>' .
        '<pre style="background:#f4f4f4;padding:10px;border:1px solid #ddd;">composer install</pre>' .
        '<p>Chemin recherché : ' . htmlspecialchars($autoloadPath) . '</p>' .
        '<p>Répertoire actuel : ' . htmlspecialchars(ROOT_PATH) . '</p>');
}
require_once $autoloadPath;

// Initialize dependency injection container
$container = new \Infrastructure\DependencyInjection\ServiceContainer();
$container->register();

// Initialize router
$router = new \Infrastructure\Routing\Router($container);

// Return container and router for use in index.php
return [
    'container' => $container,
    'router' => $router
];
