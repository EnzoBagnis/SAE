<?php

namespace Application\Authentication\UseCase;

use Application\Authentication\DTO\UpdatePasswordRequest;
use Application\Authentication\DTO\UpdatePasswordResponse;
use Domain\Authentication\Repository\UserRepositoryInterface;

/**
 * Reset Password Use Case
 * Handles password reset by validating token and updating password
 */
class ResetPassword
{
    private UserRepositoryInterface $userRepository;

    /**
     * Constructor
     *
     * @param UserRepositoryInterface $userRepository User repository
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Execute password reset
     *
     * @param UpdatePasswordRequest $request Update password request
     * @return UpdatePasswordResponse Response
     */
    public function execute(UpdatePasswordRequest $request): UpdatePasswordResponse
    {
        // Validate password length
        if (strlen($request->newPassword) < 8) {
            return new UpdatePasswordResponse(
                false,
                'Le mot de passe doit contenir au moins 8 caractères'
            );
        }

        // Find user by reset token
        $user = $this->userRepository->findByResetToken($request->token);

        if (!$user) {
            return new UpdatePasswordResponse(
                false,
                'Token invalide ou expiré'
            );
        }

        // Check if token is still valid
        if (!$user->isResetTokenValid()) {
            return new UpdatePasswordResponse(
                false,
                'Token invalide ou expiré'
            );
        }

        try {
            // Update password (this also clears the reset token)
            $user->changePassword($request->newPassword);

            // Save updated user
            $this->userRepository->save($user);

            return new UpdatePasswordResponse(
                true,
                'Votre mot de passe a été réinitialisé avec succès'
            );
        } catch (\Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return new UpdatePasswordResponse(
                false,
                'Erreur lors de la réinitialisation du mot de passe. Veuillez réessayer.'
            );
        }
    }
}
