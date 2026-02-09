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
        require __DIR__ . '/../../Views/auth/emailverification.php';
    }

    /**
     * Process email verification
     *
     * @return void
     */
    public function verify(): void
    {
        $email = $_POST['mail'] ?? '';
        $code = $_POST['code'] ?? '';

        $request = new VerifyEmailRequest($email, $code);
        $response = $this->verifyEmailUseCase->execute($request);

        if ($response->success) {
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
        require __DIR__ . '/../../Views/auth/pendingapproval.php';
    }
}
