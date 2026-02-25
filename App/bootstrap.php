<?php

/**
 * Application Bootstrap
 * Initializes autoloader and configuration
 */


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

// Define BASE_URL constant if not already set
if (!defined('BASE_URL')) {
    $baseUrl = EnvLoader::get('BASE_URL', '');
    if (empty($baseUrl)) {
        // Auto-detect from server variables
        $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl  = $scheme . '://' . $host;
    }
    define('BASE_URL', rtrim($baseUrl, '/'));
}

