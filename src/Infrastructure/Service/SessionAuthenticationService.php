<?php

namespace Infrastructure\Service;

use Domain\Authentication\Entity\User;
use Domain\Authentication\Service\AuthenticationServiceInterface;
use Domain\Authentication\Repository\UserRepositoryInterface;

/**
 * Session Authentication Service - Manages user sessions
 *
 * This service handles authentication using PHP sessions.
 */
class SessionAuthenticationService implements AuthenticationServiceInterface
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

        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createSession(User $user): void
    {
        $_SESSION['id'] = $user->getId();
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['mail'] = $user->getEmail();
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['nom'] = $user->getLastName();
        $_SESSION['prenom'] = $user->getFirstName();
        $_SESSION['user_name'] = $user->getFullName();
    }

    /**
     * {@inheritdoc}
     */
    public function destroySession(): void
    {
        session_unset();
        session_destroy();
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentUser(): ?User
    {
        if (!isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
            return null;
        }

        $userId = $_SESSION['id'] ?? $_SESSION['user_id'];
        return $this->userRepository->findById($userId);
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['id']) || isset($_SESSION['user_id']);
    }
}
