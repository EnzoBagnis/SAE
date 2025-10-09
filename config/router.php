<?php
/**
 * Router class - Main application router
 * Handles URL routing and controller dispatching
 */
class Router {
    
    /**
     * Route the request to the appropriate controller
     */
    public function route() {
        // Get action from URL parameter, default to 'home'
        $action = $_GET['action'] ?? 'home';

        switch($action) {
            case 'index':
            case 'home':
                // Redirect to public home page
                header('Location: index.html');
                exit;
                break;

            case 'dashboard':
                $this->loadController('HomeController', 'index');
                break;
                
            case 'signup':
                $this->loadController('RegistrationController', 'showView');
                break;

            case 'login':
                $this->loadController('LoginController', 'showView');
                break;

            case 'home2':
                $this->loadController('accueil2Controller', 'showView');
                break;

            case 'emailverification':
                $this->loadController('EmailVerificationController', 'showView');
                break;
                
            default:
                // Fallback to home page
                header('Location: index.html');
                exit;
                break;
        }
    }
    
    /**
     * Load and execute controller method
     * @param string $controllerName Name of the controller to load
     * @param string $method Method to execute in the controller
     */
    private function loadController($controllerName, $method) {
        $controllerFile = $_SERVER['DOCUMENT_ROOT'] . 'controllers/' . $controllerName . '.php';

        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            
            $controllerInstance = new $controllerName();
            
            if (method_exists($controllerInstance, $method)) {
                $controllerInstance->$method();
            } else {
                die("Method $method not found in $controllerName");
            }
        } else {
            die("Controller $controllerName not found. Path tested: $controllerFile");
        }
    }
}