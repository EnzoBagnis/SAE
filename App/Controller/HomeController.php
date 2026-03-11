<?php

namespace App\Controller;

use Core\Controller\AbstractController;

/**
 * Home Controller
 * Handles home page
 */
class HomeController extends AbstractController
{
    /**
     * Show home page
     *
     * @return void
     */
    public function index(): void
    {
        $this->renderView('home/index');
    }
}
