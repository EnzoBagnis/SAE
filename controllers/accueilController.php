<?php
require_once __DIR__ . '/BaseController.php';

/**
 * HomeController - Handles home page routing
 */
class HomeController extends BaseController {

    /**
     * Main index action
     * Redirects authenticated users to dashboard, others to login
     */
    public function index() {
        session_start();

        // Check if user is authenticated
        if (isset($_SESSION['id'])) {
            // Redirect to dashboard
            header('Location: views/dashboard.php');
            exit;
        } else {
            // Redirect to login page
            header('Location: views/connexion.php');
            exit;
        }
    }
}