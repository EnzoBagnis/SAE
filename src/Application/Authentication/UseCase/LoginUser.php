<?php

namespace Application\Authentication\UseCase;

use Application\Authentication\DTO\LoginRequest;
use Application\Authentication\DTO\LoginResponse;
use Domain\Authentication\Repository\UserRepositoryInterface;
use Domain\Authentication\Service\AuthenticationServiceInterface;

/**
 * LoginUser Use Case - Authenticates a user and creates a session
 *
 * This use case handles the business logic for user authentication.
 * It validates credentials and creates an authenticated session.
 */
class LoginUser
{
    private UserRepositoryInterface $userRepository;
    private AuthenticationServiceInterface $authService;

    /**
     * Constructor
     *
     * @param UserRepositoryInterface $userRepository Repository for user data
     * @param AuthenticationServiceInterface $authService Authentication service
     */
    public function __construct(
        UserRepositoryInterface $userRepository,
        AuthenticationServiceInterface $authService
    ) {
        $this->userRepository = $userRepository;
        $this->authService = $authService;
    }

    /**
     * Execute the login use case
     *
     * @param LoginRequest $request Login request data
     * @return LoginResponse Login result
     */
    public function execute(LoginRequest $request): LoginResponse
    {
        // Find user by email
        $user = $this->userRepository->findByEmail($request->email);

        if (!$user) {
            return new LoginResponse(
                false,
                'Email ou mot de passe incorrect',
                null
            );
        }

        // Check if email is verified
        if (!$user->isVerified()) {
            return new LoginResponse(
                false,
                'Votre email n\'est pas encore vérifié',
                null
            );
        }

        // Verify password
        if (!$user->verifyPassword($request->password)) {
            return new LoginResponse(
                false,
                'Email ou mot de passe incorrect',
                null
            );
        }

        // Create authenticated session
        $this->authService->createSession($user);

        return new LoginResponse(
            true,
            'Connexion réussie',
            $user
        );
    }
}
