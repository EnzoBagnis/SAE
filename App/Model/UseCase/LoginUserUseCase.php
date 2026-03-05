<?php

namespace App\Model\UseCase;

use App\Model\UseCase\Ports\UserAuthFinderPort;
use App\Model\AuthenticationService;

/**
 * Login User Use Case.
 *
 * Handles user authentication by looking up the user via
 * a {@see UserAuthFinderPort} abstraction and delegating
 * session creation to the AuthenticationService.
 */
class LoginUserUseCase
{
    private UserAuthFinderPort $userFinder;
    private AuthenticationService $authService;

    /**
     * Constructor.
     *
     * @param UserAuthFinderPort    $userFinder  Port for finding users by email.
     * @param AuthenticationService $authService Authentication service.
     */
    public function __construct(
        UserAuthFinderPort $userFinder,
        AuthenticationService $authService
    ) {
        $this->userFinder = $userFinder;
        $this->authService = $authService;
    }

    /**
     * Execute login use case
     *
     * @param array $data Login data (email, password)
     * @return array Result array with success status and message
     */
    public function execute(array $data): array
    {
        // Validate input
        if (empty($data['email']) || empty($data['password'])) {
            return [
                'success' => false,
                'message' => 'Email et mot de passe requis',
            ];
        }

        // Find user by email
        $user = $this->userFinder->findByEmail($data['email']);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Email ou mot de passe incorrect',
            ];
        }

        // Check if account is blocked
        if ($user->isBlocked()) {
            return [
                'success' => false,
                'message' => 'Votre compte a été bloqué. Contactez un administrateur.',
            ];
        }

        // Check if email is verified
        if (!$user->isVerified()) {
            return [
                'success' => false,
                'message' => 'Votre email n\'est pas encore vérifié',
            ];
        }

        // Verify password
        if (!$user->verifyPassword($data['password'])) {
            return [
                'success' => false,
                'message' => 'Email ou mot de passe incorrect',
            ];
        }

        // Create authenticated session
        $this->authService->createSession($user);

        return [
            'success' => true,
            'message' => 'Connexion réussie',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
            ],
        ];
    }
}
