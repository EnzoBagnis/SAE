<?php

/**
 * Application Entry Point
 * Main entry point for the application using Core/App architecture
 */

// Start output buffering immediately to prevent "headers already sent" issues
ob_start();

// Bootstrap the application
require_once __DIR__ . '/App/bootstrap.php';

// Catch fatal errors (E_ERROR, E_PARSE, etc.) that set_exception_handler cannot catch
register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $logFile = __DIR__ . '/logs/php_errors.log';
        $logMsg  = '[' . date('d-M-Y H:i:s') . '] [FATAL] '
                 . $error['message'] . ' in ' . $error['file'] . ':' . $error['line'] . PHP_EOL;
        @file_put_contents($logFile, $logMsg, FILE_APPEND);
        error_log('[FATAL] ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);

        if (!headers_sent()) {
            http_response_code(500);
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($uri, '/api/') !== false) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Erreur fatale du serveur'], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/api/') !== false) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['success' => false, 'message' => 'Erreur interne fatale.', 'debug' => $error['message']]);
            return;
        }

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        $errorView = __DIR__ . '/App/View/errors/500.php';
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo '<h1>Erreur interne du serveur</h1>';
        }
    }
});


// Global exception handler — catches any uncaught exception/error and returns a proper 500
set_exception_handler(function (\Throwable $e): void {
    $msg  = $e->getMessage();
    $file = $e->getFile();
    $line = $e->getLine();

    // Toujours logger la stack trace complète dans le fichier de log de l'app
    $logFile = __DIR__ . '/logs/php_errors.log';
    $logMsg  = '[' . date('d-M-Y H:i:s') . '] [UNCAUGHT] '
             . get_class($e) . ': ' . $msg
             . ' in ' . $file . ':' . $line . PHP_EOL
             . $e->getTraceAsString() . PHP_EOL;
    @file_put_contents($logFile, $logMsg, FILE_APPEND);
    error_log('[UNCAUGHT] ' . get_class($e) . ': ' . $msg . ' in ' . $file . ':' . $line);

    if (!headers_sent()) {
        http_response_code(500);
    }

    // Pour les routes API, retourner du JSON au lieu de HTML
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/api/') !== false) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Erreur interne du serveur.',
            'debug'   => $msg . ' in ' . basename($file) . ':' . $line,
        ]);
        return;
    }

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }

    $env = defined('APP_ENV') ? APP_ENV : (\Core\Config\EnvLoader::get('APP_ENV', 'production'));
    if ($env === 'development') {
        echo '<h1>Erreur 500 – Exception non gérée</h1>';
        echo '<p><b>' . htmlspecialchars(get_class($e)) . ':</b> ' . htmlspecialchars($msg) . '</p>';
        echo '<p>dans <b>' . htmlspecialchars($file) . '</b> ligne <b>' . $line . '</b></p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        $errorView = __DIR__ . '/App/View/errors/500.php';
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo '<h1>Erreur interne du serveur</h1><p>Une erreur est survenue. Veuillez réessayer.</p>';
        }
    }
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/api/') !== false) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        $env = defined('APP_ENV') ? APP_ENV : (\Core\Config\EnvLoader::get('APP_ENV', 'production'));
        $payload = ['success' => false, 'message' => 'Erreur interne du serveur'];
        if ($env === 'development') {
            $payload['message'] = get_class($e) . ': ' . $msg;
            $payload['file'] = $file . ':' . $line;
            $payload['trace'] = $e->getTraceAsString();
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        return;
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }

    // TOUJOURS afficher les détails pour le debug
    echo '<h1>Erreur 500 – Exception non gérée</h1>';
    echo '<p><b>' . htmlspecialchars(get_class($e)) . ':</b> ' . htmlspecialchars($msg) . '</p>';
    echo '<p>dans <b>' . htmlspecialchars($file) . '</b> ligne <b>' . $line . '</b></p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
});

// Initialize router
use Core\Router\Router;
use Core\Service\Container;

$router = new Router();

// ---------------------------------------------------------------------------
// Service Container — register controllers that require dependency injection.
// Controllers not registered here are still instantiated via `new` (fallback).
// ---------------------------------------------------------------------------
$container = new Container();

$container->set(
    App\Controller\ResourcesController::class,
    function () {
        return new App\Controller\ResourcesController(
            new App\Model\ResourceRepository(),
            new App\Model\AuthenticationService(new Core\Service\SessionService())
        );
    }
);

$router->setContainer($container);

// Load application routes
require_once __DIR__ . '/App/routes.php';

// Set 404 handler
$router->setNotFoundHandler(function() {
    http_response_code(404);
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/api/') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Route API non trouvée'], JSON_UNESCAPED_UNICODE);
        return;
    }
    if (file_exists(__DIR__ . '/App/View/errors/404.php')) {
        require __DIR__ . '/App/View/errors/404.php';
    } else {
        echo '<h1>404 - Page non trouvée</h1>';
    }
});

// Dispatch the request
$router->dispatch();
