<?php
/**
 * BaseController - Base controller class with common methods
 * All controllers should extend this class
 */
abstract class BaseController {

    /**
     * Load a view file with data
     * @param string $viewName Name of the view file (without .php extension)
     * @param array $data Associative array of data to pass to the view
     */
    protected function loadView($viewName, $data = []) {
        // Extract data array to variables
        extract($data);

        $viewFile = __DIR__ . '/../views/' . $viewName . '.php';

        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            die("View $viewName not found. Path tested: $viewFile");
        }
    }
}
