<?php

namespace Application\Authentication\UseCase;

use Application\Authentication\DTO\PasswordResetRequest;
use Application\Authentication\DTO\PasswordResetResponse;
use Domain\Authentication\Repository\UserRepositoryInterface;
use Domain\Authentication\Service\EmailServiceInterface;

/**
 * Request Password Reset Use Case
 * Handles password reset request by sending reset email
 */
class RequestPasswordReset
{
    private UserRepositoryInterface $userRepository;
    private EmailServiceInterface $emailService;

    /**
     * Constructor
     *
     * @param UserRepositoryInterface $userRepository User repository
     * @param EmailServiceInterface $emailService Email service
     */
    public function __construct(
        UserRepositoryInterface $userRepository,
        EmailServiceInterface $emailService
    ) {
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
    }

    /**
     * Execute password reset request
     *
     * @param PasswordResetRequest $request Reset request
     * @return PasswordResetResponse Response
     */
    public function execute(PasswordResetRequest $request): PasswordResetResponse
    {
        // Find user by email
        $user = $this->userRepository->findByEmail($request->email);

        if (!$user) {
            // Don't reveal if email exists or not (security)
            return new PasswordResetResponse(
                true,
                'Si votre email existe, vous recevrez un lien de réinitialisation'
            );
        }

        // Generate reset token (valid for 1 hour)
        $resetToken = bin2hex(random_bytes(32));
        $resetExpiry = new \DateTimeImmutable('+1 hour');

        // Save token to user
        $user->setResetToken($resetToken, $resetExpiry);
        $this->userRepository->save($user);

        try {
            // Send password reset email using the interface method
            $emailSent = $this->emailService->sendPasswordResetEmail(
                $user->getEmail(),
                $user->getFirstName(),
                $resetToken
            );

            if (!$emailSent) {
                error_log("Failed to send password reset email to: " . $user->getEmail());
                return new PasswordResetResponse(
                    false,
                    'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.'
                );
            }

            return new PasswordResetResponse(
                true,
                'Si votre email existe, vous recevrez un lien de réinitialisation'
            );
        } catch (\Exception $e) {
            error_log("Password reset email error: " . $e->getMessage());
            return new PasswordResetResponse(
                false,
                'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.'
            );
        }
    }
}
