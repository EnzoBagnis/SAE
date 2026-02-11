<?php

namespace Infrastructure\Routing;

use Infrastructure\DependencyInjection\ServiceContainer;

/**
 * Router - Application router
 *
 * Routes are organized by domain to make the application's
 * use cases immediately visible (Screaming Architecture).
 */
class Router
{
    private ServiceContainer $container;
    private array $routes = [];

    /**
     * Constructor
     *
     * @param ServiceContainer $container Service container
     */
    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
        $this->registerRoutes();
    }

    /**
     * Register all application routes
     *
     * Routes are organized by domain to make the application's
     * use cases immediately visible.
     *
     * @return void
     */
    private function registerRoutes(): void
    {
        // ========== AUTHENTICATION DOMAIN ==========
        // Login
        $this->get('login', \Presentation\Controller\Authentication\LoginController::class, 'index');
        $this->post('login', \Presentation\Controller\Authentication\LoginController::class, 'authenticate');
        $this->post('authenticate', \Presentation\Controller\Authentication\LoginController::class, 'authenticate');

        // Register
        $this->get('signup', \Presentation\Controller\Authentication\RegisterController::class, 'index');
        $this->post('signup', \Presentation\Controller\Authentication\RegisterController::class, 'register');

        // Email Verification
        $this->get(
            'emailverification',
            \Presentation\Controller\Authentication\EmailVerificationController::class,
            'index'
        );
        $this->post(
            'emailverification',
            \Presentation\Controller\Authentication\EmailVerificationController::class,
            'verify'
        );
        $this->get(
            'pendingapproval',
            \Presentation\Controller\Authentication\EmailVerificationController::class,
            'pendingApproval'
        );

        // Logout
        $this->get('logout', \Presentation\Controller\Authentication\LogoutController::class, 'logout');

        // Password Reset
        $this->get(
            'forgotpassword',
            \Presentation\Controller\Authentication\PasswordResetController::class,
            'forgotPassword'
        );
        $this->post(
            'forgotpassword',
            \Presentation\Controller\Authentication\PasswordResetController::class,
            'requestReset'
        );
        $this->post(
            'requestreset',
            \Presentation\Controller\Authentication\PasswordResetController::class,
            'requestReset'
        );
        $this->get(
            'resetpassword',
            \Presentation\Controller\Authentication\PasswordResetController::class,
            'showResetForm'
        );
        $this->post(
            'resetpassword',
            \Presentation\Controller\Authentication\PasswordResetController::class,
            'resetPassword'
        );

        // ========== USER MANAGEMENT DOMAIN ==========
        $this->get('dashboard', \Presentation\Controller\UserManagement\DashboardController::class, 'index');

        // ========== ADMINISTRATION DOMAIN ==========
        $this->get('admin', \Presentation\Controller\Administration\AdminController::class, 'showLogin');
        $this->post('adminLogin', \Presentation\Controller\Administration\AdminController::class, 'authenticate');
        $this->get('adminDashboard', \Presentation\Controller\Administration\AdminController::class, 'dashboard');
        $this->get('adminDeleteUser', \Presentation\Controller\Administration\AdminController::class, 'deleteUser');
        $this->get('adminValidUser', \Presentation\Controller\Administration\AdminController::class, 'validateUser');
        $this->post('adminEditUser', \Presentation\Controller\Administration\AdminController::class, 'editUser');
        $this->post('adminBanUser', \Presentation\Controller\Administration\AdminController::class, 'banUser');
        $this->get('adminLogout', \Presentation\Controller\Administration\AdminController::class, 'logout');
        $this->get('adminSU', \Presentation\Controller\Administration\AdminController::class, 'getStats');

        // ========== STUDENT TRACKING DOMAIN ==========
        $this->get('students', \Presentation\Controller\StudentTracking\StudentsController::class, 'getStudents');

        // ========== EXERCISE MANAGEMENT DOMAIN ==========
        $this->get('exercises', \Presentation\Controller\ExerciseManagement\ExercisesController::class, 'getExercises');

        // ========== RESOURCE MANAGEMENT DOMAIN ==========
        $this->get(
            'resources_list',
            \Presentation\Controller\ResourceManagement\ResourcesListController::class,
            'index'
        );
        $this->get(
            'resource_details',
            \Presentation\Controller\ResourceManagement\ResourceDetailsController::class,
            'index'
        );
        $this->post(
            'save_resource',
            \Presentation\Controller\ResourceManagement\ResourceSaveController::class,
            'save'
        );
        $this->post(
            'delete_resource',
            \Presentation\Controller\ResourceManagement\ResourceDeleteController::class,
            'delete'
        );

        // ========== HOME ==========
        $this->get('home', \Presentation\Controller\HomeController::class, 'index');
        $this->get('index', \Presentation\Controller\HomeController::class, 'index');
    }

    /**
     * Register a GET route
     *
     * @param string $action Action name
     * @param string $controller Controller class
     * @param string $method Controller method
     * @return void
     */
    private function get(string $action, string $controller, string $method): void
    {
        $this->routes['GET'][$action] = ['controller' => $controller, 'method' => $method];
    }

    /**
     * Register a POST route
     *
     * @param string $action Action name
     * @param string $controller Controller class
     * @param string $method Controller method
     * @return void
     */
    private function post(string $action, string $controller, string $method): void
    {
        $this->routes['POST'][$action] = ['controller' => $controller, 'method' => $method];
    }

    /**
     * Dispatch request to appropriate controller
     *
     * @return void
     */
    public function dispatch(): void
    {
        $action = $_GET['action'] ?? 'home';
        $method = $_SERVER['REQUEST_METHOD'];

        // Debug logging
        error_log("Router::dispatch() - Action: $action, Method: $method");
        error_log("Available GET routes: " . implode(', ', array_keys($this->routes['GET'] ?? [])));
        error_log("Available POST routes: " . implode(', ', array_keys($this->routes['POST'] ?? [])));

        // Check if route exists for this method
        if (!isset($this->routes[$method][$action])) {
            error_log("Route not found for $method $action");
            $this->notFound();
            return;
        }

        $route = $this->routes[$method][$action];
        error_log("Found route: " . $route['controller'] . '::' . $route['method']);

        $controllerClass = $route['controller'];
        $controllerMethod = $route['method'];

        try {
            // Get controller from container
            $controller = $this->container->get($controllerClass);

            // Call controller method
            $controller->$controllerMethod();
        } catch (\Exception $e) {
            error_log("Routing error: " . $e->getMessage());
            $this->error500($e);
        }
    }

    /**
     * Handle 404 Not Found
     *
     * @return void
     */
    private function notFound(): void
    {
        http_response_code(404);
        echo "404 - Page not found";
    }

    /**
     * Handle 500 Internal Server Error
     *
     * @param \Exception $e Exception
     * @return void
     */
    private function error500(\Exception $e): void
    {
        http_response_code(500);
        echo "500 - Internal Server Error";
        if (ini_get('display_errors')) {
            echo "<pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
        }
    }
}
