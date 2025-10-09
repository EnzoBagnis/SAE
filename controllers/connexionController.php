<?php
require_once __DIR__ . '/baseController.php';

/**
 * LoginController - Handles login page display
 */
class LoginController extends BaseController {

    /**
     * Show login view
     */
    public function showView() {
        $this->loadView('connexion', ['titre' => 'login']);
    }
}