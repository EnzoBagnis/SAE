<?php

/**
 * BaseController - Base controller class with common methods
 * All controllers should extend this class
 */
abstract class BaseController
{
    /**
     * Load a view file with data
     * @param string $viewName Name of the view file (without .php extension)
     * @param array $data Associative array of data to pass to the view
     */
    protected function loadView($viewName, $data = [])
    {
        // Extract data array to variables
        extract($data);

        // Support both old and new view paths
        $viewFile = __DIR__ . '/../views/' . $viewName . '.php';

        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            die("View $viewName not found. Path tested: $viewFile");
        }
    }

    /**
     * Redirect to a specific action
     * @param string $action Action name for routing
     * @param array $params Additional URL parameters
     */
    protected function redirect($action, $params = [])
    {
        $url = '/index.php?action=' . $action;

        foreach ($params as $key => $value) {
            $url .= '&' . urlencode($key) . '=' . urlencode($value);
        }

        header('Location: ' . $url);
        exit;
    }

    /**
     * Check if user is authenticated
     * @return bool
     */
    protected function isAuthenticated()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['id']);
    }

    /**
     * Require authentication - redirect to login if not authenticated
     */
    protected function requireAuth()
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('login');
        }
    }
}
