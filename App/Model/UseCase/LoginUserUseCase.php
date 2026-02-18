<?php

namespace App\Model\UseCase;

use App\Model\UserRepository;
use App\Model\AuthenticationService;

/**
 * Login User Use Case
 * Handles user authentication
 */
class LoginUserUseCase
{
    private UserRepository $userRepository;
    private AuthenticationService $authService;

    /**
     * Constructor
     *
     * @param UserRepository $userRepository User repository
     * @param AuthenticationService $authService Authentication service
     */
    public function __construct(
        UserRepository $userRepository,
        AuthenticationService $authService
    ) {
        $this->userRepository = $userRepository;
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
        $user = $this->userRepository->findByEmail($data['email']);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Email ou mot de passe incorrect',
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

