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
require_once ROOT_PATH . '/vendor/autoload.php';

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
