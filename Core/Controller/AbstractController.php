<?php

namespace Core\Controller;

/**
 * Abstract Base Controller
 * Provides common controller functionality
 */
abstract class AbstractController
{
    /**
     * Render a view template
     *
     * @param string $viewName View name (relative to App/View/)
     * @param array $data Data to pass to view
     * @return void
     */
    protected function renderView(string $viewName, array $data = []): void
    {
        extract($data);
        $viewPath = __DIR__ . '/../../App/View/' . $viewName . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("Vue non trouvée : {$viewName} (chemin: {$viewPath})");
        }

        require $viewPath;
    }

    /**
     * Send JSON response
     *
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     * @return void
     */
    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send success JSON response
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @return void
     */
    protected function jsonSuccess(mixed $data = null, string $message = 'Success', int $statusCode = 200): void
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        $this->jsonResponse($response, $statusCode);
    }

    /**
     * Send error JSON response
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param mixed $errors Additional error details
     * @return void
     */
    protected function jsonError(string $message, int $statusCode = 400, mixed $errors = null): void
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        $this->jsonResponse($response, $statusCode);
    }

    /**
     * Redirect to URL
     *
     * @param string $url Target URL
     * @param int $statusCode HTTP status code (301, 302, 303, 307, 308)
     * @return void
     */
    protected function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }

    /**
     * Get request method
     *
     * @return string HTTP method (GET, POST, PUT, DELETE, etc.)
     */
    protected function getRequestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Check if request is POST
     *
     * @return bool True if POST request
     */
    protected function isPost(): bool
    {
        return $this->getRequestMethod() === 'POST';
    }

    /**
     * Check if request is GET
     *
     * @return bool True if GET request
     */
    protected function isGet(): bool
    {
        return $this->getRequestMethod() === 'GET';
    }

    /**
     * Check if request is AJAX
     *
     * @return bool True if AJAX request
     */
    protected function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get POST data
     *
     * @param string|null $key Specific key to retrieve
     * @param mixed $default Default value if key not found
     * @return mixed POST data or specific value
     */
    protected function getPost(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_POST;
        }

        return $_POST[$key] ?? $default;
    }

    /**
     * Get GET data
     *
     * @param string|null $key Specific key to retrieve
     * @param mixed $default Default value if key not found
     * @return mixed GET data or specific value
     */
    protected function getQuery(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_GET;
        }

        return $_GET[$key] ?? $default;
    }

    /**
     * Get request input (works with JSON and form data)
     *
     * @return array Request input data
     */
    protected function getInput(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (strpos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            return json_decode($json, true) ?? [];
        }

        return $_POST;
    }

    /**
     * Validate CSRF token
     *
     * @param string|null $token Token to validate
     * @return bool True if valid
     */
    protected function validateCsrfToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
    }

    /**
     * Generate CSRF token
     *
     * @return string CSRF token
     */
    protected function generateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Set flash message
     *
     * @param string $key Message key
     * @param string $message Message content
     * @return void
     */
    protected function setFlash(string $key, string $message): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['flash'][$key] = $message;
    }

    /**
     * Get flash message and clear it
     *
     * @param string $key Message key
     * @return string|null Message content
     */
    protected function getFlash(string $key): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $message;
        }

        return null;
    }
}
