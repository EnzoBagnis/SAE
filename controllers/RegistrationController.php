<?php
require_once __DIR__ . '/BaseController.php';

/**
 * RegistrationController - Handles registration page display
 */
class RegistrationController extends BaseController {

    /**
     * Show registration view
     */
    public function showView() {
        $this->loadView('formulaire', ['titre' => 'registration']);
    }
}