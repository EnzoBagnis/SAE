<?php

/**
 * Application Bootstrap
 * Initializes autoloader and configuration
 */

// Error reporting for development
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
error_reporting(E_ALL);

// Custom error handler to log errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    return false;
});

// Custom exception handler
set_exception_handler(function($exception) {
    error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    http_response_code(500);

    // In production, show a generic error page
    if (php_sapi_name() !== 'cli') {
        echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Erreur</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; text-align: center; }
        h1 { color: #e74c3c; }
    </style>
</head>
<body>
    <h1>Une erreur est survenue</h1>
    <p>Nous sommes désolés, une erreur technique est survenue. Veuillez réessayer plus tard.</p>
    <p><a href="/">Retour à l\'accueil</a></p>
</body>
</html>';
    }
    exit(1);
});

// Define base path
define('BASE_PATH', __DIR__ . '/..');

// Create logs directory if it doesn't exist
if (!is_dir(BASE_PATH . '/logs')) {
    @mkdir(BASE_PATH . '/logs', 0755, true);
}

// Autoloader for Core
spl_autoload_register(function ($class) {
    $prefix = 'Core\\';
    $baseDir = BASE_PATH . '/Core/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Autoloader for App
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = BASE_PATH . '/App/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load vendor autoloader for external dependencies (PHPMailer, etc.)
$vendorAutoload = BASE_PATH . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require $vendorAutoload;
}

// Load environment configuration
use Core\Config\EnvLoader;

try {
    EnvLoader::load();
} catch (\Exception $e) {
    // En développement, afficher l'erreur
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        echo '<h1>Erreur de Configuration</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p>Vérifiez que le fichier <code>../config/.env</code> existe et est accessible.</p>';
    } else {
        echo 'Configuration error: ' . $e->getMessage() . PHP_EOL;
    }
    exit(1);
}

// Set error reporting based on environment
$env = EnvLoader::get('APP_ENV', 'production');
if ($env === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Set timezone
$timezone = EnvLoader::get('TIMEZONE', 'Europe/Paris');
date_default_timezone_set($timezone);

// Set default charset
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

