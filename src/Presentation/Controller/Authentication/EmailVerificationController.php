<?php

namespace Presentation\Controller\Authentication;

use Application\Authentication\UseCase\VerifyUserEmail;
use Application\Authentication\DTO\VerifyEmailRequest;

/**
 * EmailVerificationController - Handles email verification
 *
 * This controller is thin and delegates business logic to use cases.
 */
class EmailVerificationController
{
    private VerifyUserEmail $verifyEmailUseCase;

    /**
     * Constructor
     *
     * @param VerifyUserEmail $verifyEmailUseCase Verify email use case
     */
    public function __construct(VerifyUserEmail $verifyEmailUseCase)
    {
        $this->verifyEmailUseCase = $verifyEmailUseCase;
    }

    /**
     * Display email verification form
     *
     * @return void
     */
    public function index(): void
    {
        // Check if email is in session
        if (!isset($_SESSION['pending_verification_email'])) {
            $_SESSION['error'] = 'Session expirée. Veuillez vous réinscrire.';
            header('Location: ' . BASE_URL . '/index.php?action=signup');
            exit;
        }

        require __DIR__ . '/../../Views/auth/email-verification.php';
    }

    /**
     * Process email verification
     *
     * @return void
     */
    public function verify(): void
    {
        // Get email from session instead of POST
        $email = $_SESSION['pending_verification_email'] ?? '';
        $code = $_POST['code'] ?? '';

        // Debug logging
        error_log("EmailVerificationController::verify() - Email from session: " . $email);
        error_log("EmailVerificationController::verify() - Code from POST: " . $code);

        // Validate session
        if (!$email) {
            $_SESSION['error'] = 'Session expirée. Veuillez vous réinscrire.';
            header('Location: ' . BASE_URL . '/index.php?action=signup');
            exit;
        }

        // Validate code input
        if (!$code) {
            $_SESSION['error'] = 'Veuillez entrer le code de vérification.';
            header('Location: ' . BASE_URL . '/index.php?action=emailverification');
            exit;
        }

        $request = new VerifyEmailRequest($email, $code);
        $response = $this->verifyEmailUseCase->execute($request);

        error_log("EmailVerificationController::verify() - Response success: " . ($response->success ? 'true' : 'false'));
        error_log("EmailVerificationController::verify() - Response message: " . $response->message);

        if ($response->success) {
            // Clear the pending verification email from session
            unset($_SESSION['pending_verification_email']);
            $_SESSION['success'] = $response->message;
            header('Location: ' . BASE_URL . '/index.php?action=pendingapproval');
            exit;
        } else {
            $_SESSION['error'] = $response->message;
            header('Location: ' . BASE_URL . '/index.php?action=emailverification');
            exit;
        }
    }

    /**
     * Display pending approval page
     *
     * @return void
     */
    public function pendingApproval(): void
    {
        require __DIR__ . '/../../Views/auth/pending-approval.php';
    }
}
