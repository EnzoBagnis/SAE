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

$router->get('/auth/logout', App\Controller\LogoutController::class, 'logout');

// Dashboard routes (protected)
$router->get('/dashboard', App\Controller\DashboardController::class, 'index');

// Exercise routes
$router->get('/exercises', App\Controller\ExercisesController::class, 'index');
$router->get('/exercises/{id}', App\Controller\ExercisesController::class, 'show');

// Resource routes
$router->get('/resources', App\Controller\ResourcesController::class, 'index');
$router->get('/resources/{id}', App\Controller\ResourcesController::class, 'show');


