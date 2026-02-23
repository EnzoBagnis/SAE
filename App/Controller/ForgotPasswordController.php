<?php

namespace App\Controller;

use Core\Controller\AbstractController;
use App\Model\UserRepository;
use App\Model\EmailService;

/**
 * ForgotPassword Controller
 * Handles forgot password and reset password flows
 */
class ForgotPasswordController extends AbstractController
{
    private UserRepository $userRepository;
    private EmailService $emailService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->emailService   = new EmailService();
    }

    /**
     * Show forgot password form
     *
     * @return void
     */
    public function index(): void
    {
        $this->renderView('auth/forgot-password');
    }

    /**
     * Process forgot password request
     *
     * @return void
     */
    public function send(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/auth/forgot-password');
            return;
        }

        $email = $this->getPost('email') ?? $this->getPost('mail') ?? '';
        $user = $this->userRepository->findByEmail($email);

        // Always show success to avoid email enumeration
        if ($user) {
            $token      = bin2hex(random_bytes(32));
            $expiration = new \DateTimeImmutable('+1 hour');

            $user->setResetToken($token, $expiration);
            $this->userRepository->save($user);

            $this->emailService->sendPasswordResetEmail(
                $user->getEmail(),
                $user->getFirstName(),
                $token
            );
        }

        $this->renderView('auth/forgot-password', [
            'success_message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.',
        ]);
    }

    /**
     * Show reset password form
     *
     * @return void
     */
    public function resetForm(): void
    {
        $token = $this->getQuery('token') ?? '';

        if (empty($token)) {
            $this->redirect('/auth/login');
            return;
        }

        $user = $this->userRepository->findByResetToken($token);

        if (!$user || $user->getResetTokenExpiration() < date('Y-m-d H:i:s')) {
            $this->renderView('auth/forgot-password', [
                'error_message' => 'Ce lien est invalide ou a expiré.',
            ]);
            return;
        }

        $this->renderView('auth/reset-password', ['token' => $token]);
    }

    /**
     * Process password reset
     *
     * @return void
     */
    public function reset(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/auth/login');
            return;
        }

        $token       = $this->getPost('token') ?? '';
        $newPassword = $this->getPost('nouveau_mdp') ?? $this->getPost('password') ?? '';
        $confirm     = $this->getPost('confirm_mdp') ?? $this->getPost('confirm_password') ?? '';

        if (empty($token)) {
            $this->redirect('/auth/login');
            return;
        }

        $user = $this->userRepository->findByResetToken($token);

        if (!$user || !$user->isResetTokenValid()) {
            $this->renderView('auth/forgot-password', [
                'error_message' => 'Ce lien est invalide ou a expiré.',
            ]);
            return;
        }

        if ($newPassword !== $confirm) {
            $this->renderView('auth/reset-password', [
                'token'         => $token,
                'error_message' => 'Les mots de passe ne correspondent pas.',
            ]);
            return;
        }

        if (strlen($newPassword) < 6) {
            $this->renderView('auth/reset-password', [
                'token'         => $token,
                'error_message' => 'Le mot de passe doit contenir au moins 6 caractères.',
            ]);
            return;
        }

        $user->changePassword($newPassword);
        $this->userRepository->save($user);

        $this->redirect('/auth/login');
    }
}
