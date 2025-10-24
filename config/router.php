<?php
/**
 * Router class - Main application router
 * Handles URL routing and controller dispatching with namespaces
 */
class Router {
    
    /**
     * Route the request to the appropriate controller
     */
    public function route() {
        $action = $_GET['action'] ?? 'home';

        switch($action) {
            // ========== HOME ==========
            case 'index':
            case 'home':
                $this->loadController('HomeController', 'index');
                break;

            // ========== AUTH - LOGIN ==========
            case 'login':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->loadNamespacedController('Controllers\Auth\LoginController', 'authenticate');
                } else {
                    $this->loadNamespacedController('Controllers\Auth\LoginController', 'index');
                }
                break;

            // ========== AUTH - REGISTER ==========
            case 'signup':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->loadNamespacedController('Controllers\Auth\RegisterController', 'register');
                } else {
                    $this->loadNamespacedController('Controllers\Auth\RegisterController', 'index');
                }
                break;

            // ========== AUTH - EMAIL VERIFICATION ==========
            case 'emailverification':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->loadNamespacedController('Controllers\Auth\EmailVerificationController', 'verify');
                } else {
                    $this->loadNamespacedController('Controllers\Auth\EmailVerificationController', 'index');
                }
                break;

            // ========== AUTH - RESEND CODE ==========
            case 'resendcode':
                $this->loadNamespacedController('Controllers\Auth\EmailVerificationController', 'resendCode');
                break;

            // ========== AUTH - FORGOT PASSWORD ==========
            case 'forgotpassword':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->loadNamespacedController('Controllers\Auth\PasswordResetController', 'requestReset');
                } else {
                    $this->loadNamespacedController('Controllers\Auth\PasswordResetController', 'forgotPassword');
                }
                break;

            // ========== AUTH - RESET PASSWORD ==========
            case 'resetpassword':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->loadNamespacedController('Controllers\Auth\PasswordResetController', 'resetPassword');
                } else {
                    $this->loadNamespacedController('Controllers\Auth\PasswordResetController', 'showResetForm');
                }
                break;

            // ========== AUTH - LOGOUT ==========
            case 'logout':
                $this->loadNamespacedController('Controllers\Auth\LogoutController', 'logout');
                break;

            // ========== USER - DASHBOARD ==========
            case 'dashboard':
                $this->loadNamespacedController('Controllers\User\DashboardController', 'index');
                break;

            // ========== WORKSHOP API ==========
            case 'workshops':
            case 'tplist':
                $this->loadNamespacedController('Controllers\Workshop\WorkshopController', 'list');
                break;

            case 'workshop':
                $this->loadNamespacedController('Controllers\Workshop\WorkshopController', 'show');
                break;

            // ========== PAGES ==========
            case 'mentions':
                $this->loadView('pages/mentions-legales');
                break;


	    // ========== RESOURCES LIST ==========
            case 'resources_list':
                $this->loadView('User\Resources_listController');
                break;


	    // ========== RESOURCE DETAILS ==========
            case 'resource_details':
                $this->loadView('User\Resource_detailsController');
                break;

            default:
                $this->loadController('HomeController', 'index');
                break;
		
        }
    }
    
    /**
     * Load and execute controller method (legacy - old controllers)
     */
    private function loadController($controllerName, $method) {
        $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';

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

    /**
     * Load and execute namespaced controller method (new MVC structure)
     */
    private function loadNamespacedController($fullyQualifiedClassName, $method) {
        // Load BaseController if not already loaded
        $baseControllerFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'BaseController.php';
        if (file_exists($baseControllerFile)) {
            require_once $baseControllerFile;
        }

        // Convert namespace to file path
        // Replace Controllers\ with controllers\ and all remaining backslashes with directory separator
        $classPath = str_replace('Controllers\\', 'controllers' . DIRECTORY_SEPARATOR, $fullyQualifiedClassName);
        $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $classPath);
        $controllerFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $classPath . '.php';

        if (file_exists($controllerFile)) {
            require_once $controllerFile;

            $controllerInstance = new $fullyQualifiedClassName();

            if (method_exists($controllerInstance, $method)) {
                $controllerInstance->$method();
            } else {
                die("Method $method not found in $fullyQualifiedClassName");
            }
        } else {
            die("Controller $fullyQualifiedClassName not found. Path tested: $controllerFile");
        }
    }

    /**
     * Load a static view directly
     */
    private function loadView($viewPath) {
        $viewFile = __DIR__ . '/../views/' . $viewPath . '.php';

        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            die("View $viewPath not found.");
        }
    }
}