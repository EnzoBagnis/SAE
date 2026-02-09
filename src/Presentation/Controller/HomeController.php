<?php

namespace Presentation\Controller;

/**
 * HomeController - Handles home page
 */
class HomeController
{
    /**
     * Display home page
     *
     * @return void
     */
    public function index(): void
    {
        // Redirect to static index.html for now
        header('Location: ' . BASE_URL . '/index.html');
        exit;
    }
}
