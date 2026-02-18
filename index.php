<?php

/**
 * Application Entry Point
 * Main entry point for the application using Core/App architecture
 */

// Bootstrap the application
require_once __DIR__ . '/App/bootstrap.php';

// Initialize router
use Core\Router\Router;

$router = new Router();

// Load application routes
require_once __DIR__ . '/App/routes.php';

// Set 404 handler
$router->setNotFoundHandler(function() {
    http_response_code(404);
    if (file_exists(__DIR__ . '/App/View/errors/404.php')) {
        require __DIR__ . '/App/View/errors/404.php';
    } else {
        echo '<h1>404 - Page non trouvée</h1>';
    }
});

// Dispatch the request
$router->dispatch();
