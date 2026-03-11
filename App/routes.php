<?php

/**
 * Application Routes
 * Define all application routes here
 *
 * @var \Core\Router\Router $router
 */

// Home route
$router->get('/', App\Controller\HomeController::class, 'index');

// Authentication routes
$router->get('/auth/login', App\Controller\LoginController::class, 'index');
$router->post('/auth/login', App\Controller\LoginController::class, 'login');

$router->get('/auth/register', App\Controller\RegisterController::class, 'index');
$router->post('/auth/register', App\Controller\RegisterController::class, 'register');

$router->get('/auth/verify-email', App\Controller\VerifyEmailController::class, 'index');
$router->post('/auth/verify-email', App\Controller\VerifyEmailController::class, 'verify');
$router->post('/auth/resend-code', App\Controller\VerifyEmailController::class, 'resend');

$router->get('/auth/pending-approval', App\Controller\VerifyEmailController::class, 'pendingApproval');

$router->get('/auth/logout', App\Controller\LogoutController::class, 'logout');

$router->get('/auth/forgot-password', App\Controller\ForgotPasswordController::class, 'index');
$router->post('/auth/forgot-password', App\Controller\ForgotPasswordController::class, 'send');
$router->get('/auth/reset-password', App\Controller\ForgotPasswordController::class, 'resetForm');
$router->post('/auth/reset-password', App\Controller\ForgotPasswordController::class, 'reset');

// Dashboard routes (protected)
$router->get('/dashboard', App\Controller\DashboardController::class, 'index');

// IA route
$router->get('/ia', App\Controller\IaController::class, 'index');

// IA API route (clustering pipeline)
$router->post('/api/ia/clustering', App\Controller\IaController::class, 'clustering');

// IA API routes (vue Macro / Micro)
$router->post('/api/ia/macro', App\Controller\IaController::class, 'macro');
$router->post('/api/ia/micro', App\Controller\IaController::class, 'micro');

// IA diagnostic route (TEMPORAIRE - à supprimer après debug)
$router->get('/api/ia/debug-python', App\Controller\IaController::class, 'debugPython');

// Exercise routes
$router->get('/exercises', App\Controller\ExercisesController::class, 'index');
$router->get('/exercises/{id}', App\Controller\ExercisesController::class, 'show');

// Resource routes
$router->get('/resources', App\Controller\ResourcesController::class, 'index');
$router->post('/resources', App\Controller\ResourcesController::class, 'store');
$router->get('/resources/{id}', App\Controller\ResourcesController::class, 'show');
$router->post('/resources/{id}/update', App\Controller\ResourcesController::class, 'update');
$router->post('/resources/{id}/delete', App\Controller\ResourcesController::class, 'delete');

// Import API routes (replaces api_import_exercises.php and api_import_attempts.php)
$router->post('/api/import/exercises', App\Controller\ImportController::class, 'exercises');
$router->post('/api/import/attempts', App\Controller\ImportController::class, 'attempts');

// Dashboard API routes (JSON endpoints consumed by dashboard-main.js)
$router->get('/api/dashboard/students', App\Controller\DashboardApiController::class, 'students');
$router->get('/api/dashboard/student/{id}', App\Controller\DashboardApiController::class, 'student');
$router->get('/api/dashboard/exercises', App\Controller\DashboardApiController::class, 'exercises');
$router->get('/api/dashboard/students-stats', App\Controller\DashboardApiController::class, 'studentsStats');

// Dashboard IA API routes (clustering pipeline intégré au dashboard)
$router->post('/api/dashboard/ia/macro', App\Controller\DashboardApiController::class, 'iaMacro');
$router->post('/api/dashboard/ia/micro', App\Controller\DashboardApiController::class, 'iaMicro');
$router->get('/api/dashboard/ia/check-data', App\Controller\DashboardApiController::class, 'iaCheckData');

// Admin routes
$router->get('/admin', App\Controller\AdminController::class, 'loginForm');
$router->get('/admin/login', App\Controller\AdminController::class, 'loginForm');
$router->post('/admin/login', App\Controller\AdminController::class, 'login');
$router->get('/admin/dashboard', App\Controller\AdminController::class, 'dashboard');
$router->get('/admin/logout', App\Controller\AdminController::class, 'logout');
$router->get('/admin/delete-user', App\Controller\AdminController::class, 'deleteUser');
$router->get('/admin/validate-user', App\Controller\AdminController::class, 'validateUser');
$router->post('/admin/edit-user', App\Controller\AdminController::class, 'editUser');
$router->post('/admin/ban-user', App\Controller\AdminController::class, 'banUser');
$router->get('/admin/unban-user', App\Controller\AdminController::class, 'unbanUser');
$router->get('/admin/switch-user', App\Controller\AdminController::class, 'switchUser');
