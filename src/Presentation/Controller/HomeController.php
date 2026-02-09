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
        require __DIR__ . '/../Views/home.php';
    }
}
