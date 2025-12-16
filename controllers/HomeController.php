<?php

require_once __DIR__ . '/BaseController.php';

/**
 * HomeController - Handles home page routing
 */
class HomeController extends BaseController
{
    /**
     * Main index action
     * Redirects authenticated users to dashboard, others to public home page
     */
    public function index()
    {
        // Check if user is authenticated
        if ($this->isAuthenticated()) {
            // Redirect to dashboard using new route
            $this->redirect('dashboard');
        } else {
            // Redirect to public home page
            header('Location: /index.html');
            exit;
        }
    }
}
