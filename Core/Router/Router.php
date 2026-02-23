<?php

namespace Core\Router;

/**
 * Router Class
 * Handles URL routing without business logic
 */
class Router
{
    private array $routes = [];
    private array $middleware = [];
    private $notFoundHandler = null;

    /**
     * Add route for any HTTP method
     *
     * @param string $method HTTP method
     * @param string $path URL path pattern
     * @param string $controller Controller class name
     * @param string $action Controller method name
     * @return void
     */
    public function addRoute(string $method, string $path, string $controller, string $action): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
        ];
    }

    /**
     * Add GET route
     *
     * @param string $path URL path pattern
     * @param string $controller Controller class name
     * @param string $action Controller method name
     * @return void
     */
    public function get(string $path, string $controller, string $action): void
    {
        $this->addRoute('GET', $path, $controller, $action);
    }

    /**
     * Add POST route
     *
     * @param string $path URL path pattern
     * @param string $controller Controller class name
     * @param string $action Controller method name
     * @return void
     */
    public function post(string $path, string $controller, string $action): void
    {
        $this->addRoute('POST', $path, $controller, $action);
    }

    /**
     * Add PUT route
     *
     * @param string $path URL path pattern
     * @param string $controller Controller class name
     * @param string $action Controller method name
     * @return void
     */
    public function put(string $path, string $controller, string $action): void
    {
        $this->addRoute('PUT', $path, $controller, $action);
    }

    /**
     * Add DELETE route
     *
     * @param string $path URL path pattern
     * @param string $controller Controller class name
     * @param string $action Controller method name
     * @return void
     */
    public function delete(string $path, string $controller, string $action): void
    {
        $this->addRoute('DELETE', $path, $controller, $action);
    }

    /**
     * Add PATCH route
     *
     * @param string $path URL path pattern
     * @param string $controller Controller class name
     * @param string $action Controller method name
     * @return void
     */
    public function patch(string $path, string $controller, string $action): void
    {
        $this->addRoute('PATCH', $path, $controller, $action);
    }

    /**
     * Add middleware
     *
     * @param callable $middleware Middleware callable
     * @return void
     */
    public function addMiddleware(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Set 404 not found handler
     *
     * @param callable $handler Handler callable
     * @return void
     */
    public function setNotFoundHandler(callable $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    /**
     * Dispatch request to appropriate controller
     *
     * @return void
     */
    public function dispatch(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Strip subdirectory prefix so routes work in both root and subfolder installs
        // e.g. /SAE/auth/login → /auth/login
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        if ($scriptDir !== '' && strpos($requestUri, $scriptDir) === 0) {
            $requestUri = substr($requestUri, strlen($scriptDir));
        }
        if ($requestUri === '' || $requestUri === false) {
            $requestUri = '/';
        }

        // Normalize URI (remove trailing slash except for root)
        if ($requestUri !== '/' && substr($requestUri, -1) === '/') {
            $requestUri = rtrim($requestUri, '/');
        }

        // Execute middleware
        foreach ($this->middleware as $middleware) {
            $result = $middleware($requestMethod, $requestUri);
            if ($result === false) {
                return;
            }
        }

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            $pattern = $this->convertPathToRegex($route['path']);

            if (preg_match($pattern, $requestUri, $matches)) {
                array_shift($matches); // Remove full match

                $this->executeController(
                    $route['controller'],
                    $route['action'],
                    $matches
                );
                return;
            }
        }

        // No route matched - handle 404
        $this->handle404();
    }

    /**
     * Execute controller action
     *
     * @param string $controllerClass Controller class name
     * @param string $action Action method name
     * @param array $params Route parameters
     * @return void
     */
    private function executeController(string $controllerClass, string $action, array $params): void
    {
        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Contrôleur non trouvé : {$controllerClass}");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $action)) {
            throw new \RuntimeException("Action non trouvée : {$action} dans {$controllerClass}");
        }

        call_user_func_array([$controller, $action], $params);
    }

    /**
     * Handle 404 Not Found
     *
     * @return void
     */
    private function handle404(): void
    {
        if ($this->notFoundHandler !== null) {
            call_user_func($this->notFoundHandler);
            return;
        }

        http_response_code(404);
        echo '<h1>404 - Page non trouvée</h1>';
        echo '<p>La ressource demandée n\'existe pas.</p>';
    }

    /**
     * Convert path pattern to regex
     * Converts {param} to regex capture groups
     *
     * @param string $path Path pattern
     * @return string Regex pattern
     */
    private function convertPathToRegex(string $path): string
    {
        // Escape special regex characters except {/}
        $pattern = preg_replace('/([.+?^\$\[\](){}\\\\|])/', '\\\\$1', $path);

        // Replace {param} with capture group
        $pattern = preg_replace('/\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}/', '([^/]+)', $pattern);

        return '#^' . $pattern . '$#';
    }

    /**
     * Generate URL from route name and parameters
     *
     * @param string $path Route path pattern
     * @param array $params Route parameters
     * @return string Generated URL
     */
    public function generateUrl(string $path, array $params = []): string
    {
        $url = $path;

        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }

        return $url;
    }
}

