<?php
require_once __DIR__ . '/BaseController.php';

/**
 * EmailVerificationController - Handles email verification page display
 */
class EmailVerificationController extends BaseController {

    /**
     * Show email verification view
     */
    public function showView() {
        $this->loadView('verificationMail', ['titre' => 'emailVerification']);
    }
}