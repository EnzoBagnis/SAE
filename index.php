<?php

/**
 * Application Entry Point
 * Routes all requests through the new Clean Architecture
 */

// Bootstrap the application
$bootstrap = require_once __DIR__ . '/src/bootstrap.php';

// Get router from bootstrap
$router = $bootstrap['router'];

// Dispatch the request
$router->dispatch();
