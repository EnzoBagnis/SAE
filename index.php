<?php
/**
 * Application Entry Point
 * Routes all requests through the main router
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load router
require_once 'config/router.php';

// Initialize and execute router
$router = new Router();
$router->route();