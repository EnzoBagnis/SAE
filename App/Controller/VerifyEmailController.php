<?php

namespace App\Controller;

use Core\Controller\AbstractController;
use App\Model\UserRepository;

/**
 * Verify Email Controller
 * Handles email verification via code sent after registration.
 */
class VerifyEmailController extends AbstractController
{
    private UserRepository $userRepository;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    /**
     * Show email verification form.
     * Requires a pending_verification_email stored in session.
     *
     * @return void
     */
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['pending_verification_email'])) {
            $this->redirect(BASE_URL . '/auth/register');
            return;
        }

        $this->renderView('auth/email-verification');
    }

    /**
     * Process email verification code submission.
     * Validates the code against the stored code_verif in the database.
     * On success, sets account_status = 1 (email verified, awaiting admin approval).
     *
     * @return void
     */
    public function verify(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!$this->isPost()) {
            $this->redirect(BASE_URL . '/auth/verify-email');
            return;
        }

        $email = $_SESSION['pending_verification_email'] ?? '';

        if (empty($email)) {
            $this->redirect(BASE_URL . '/auth/register');
            return;
        }

        $code = trim($this->getPost('code') ?? '');

        if (empty($code)) {
            $this->renderView('auth/email-verification', [
                'error_message' => 'Veuillez entrer le code de vérification.',
            ]);
            return;
        }

        // Retrieve user from DB
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            $this->renderView('auth/email-verification', [
                'error_message' => 'Compte introuvable. Veuillez vous réinscrire.',
            ]);
            return;
        }

        // Compare code (stored as int in DB, compare as string)
        if ((string) $user->getVerificationCode() !== $code) {
            $this->renderView('auth/email-verification', [
                'error_message' => 'Code incorrect. Veuillez réessayer.',
            ]);
            return;
        }

        // Mark email as verified: account_status = 1 (awaiting admin approval)
        $user->setAccountStatus(1);
        $user->setVerificationCode(null);
        $this->userRepository->save($user);

        // Clear session email
        unset($_SESSION['pending_verification_email']);

        // Redirect to pending approval page
        $this->redirect(BASE_URL . '/auth/pending-approval');
    }

    /**
     * Resend verification code to the user's email.
     *
     * @return void
     */
    public function resend(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $email = $_SESSION['pending_verification_email'] ?? '';

        if (empty($email)) {
            $this->redirect(BASE_URL . '/auth/register');
            return;
        }

        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            $this->redirect(BASE_URL . '/auth/register');
            return;
        }

        // Generate a new code and save it
        $newCode = $user->generateVerificationCode();
        $this->userRepository->save($user);

        // Send the new code by email
        $emailService = new \App\Model\EmailService();
        $emailService->sendVerificationCode($email, $user->getFirstName(), $newCode);

        $this->renderView('auth/email-verification', [
            'success_message' => 'Un nouveau code a été envoyé à votre adresse email.',
        ]);
    }
}
