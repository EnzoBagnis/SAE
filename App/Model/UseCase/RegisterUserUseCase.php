<?php

namespace App\Model\UseCase;

use App\Model\UserRepository;
use App\Model\Entity\User;
use App\Model\EmailService;

/**
 * Register User Use Case
 * Handles user registration process.
 * Inserts directly into the `teachers` table with account_status=0 (pending approval).
 */
class RegisterUserUseCase
{
    private UserRepository $userRepository;
    private EmailService $emailService;

    /**
     * Constructor
     *
     * @param UserRepository $userRepository User repository
     * @param EmailService $emailService Email service
     */
    public function __construct(
        UserRepository $userRepository,
        EmailService $emailService
    ) {
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
    }

    /**
     * Execute registration use case.
     * Creates a teacher account with account_status=0 (awaiting verification).
     *
     * @param array $data Registration data (email, password, first_name, last_name)
     * @return array Result array with success status and message
     */
    public function execute(array $data): array
    {
        // Validate input
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Email invalide',
            ];
        }

        if (empty($data['password']) || strlen($data['password']) < 8) {
            return [
                'success' => false,
                'message' => 'Le mot de passe doit contenir au moins 8 caractères',
            ];
        }

        if (empty($data['first_name']) || empty($data['last_name'])) {
            return [
                'success' => false,
                'message' => 'Le nom et le prénom sont requis',
            ];
        }

        // Check if email already exists in teachers
        if ($this->userRepository->emailExists($data['email'])) {
            // Check if the existing account is blocked
            $existingUser = $this->userRepository->findByEmail($data['email']);
            if ($existingUser && $existingUser->isBlocked()) {
                return [
                    'success' => false,
                    'message' => 'Votre compte a été bloqué. Contactez un administrateur.',
                ];
            }
            return [
                'success' => false,
                'message' => 'Cet email est déjà enregistré',
            ];
        }

        // Create user entity with account_status = 0 (not verified yet)
        $user = new User();
        $user->setLastName($data['last_name']);
        $user->setFirstName($data['first_name']);
        $user->setEmail($data['email']);
        $user->setPasswordHash(password_hash($data['password'], PASSWORD_DEFAULT));
        $user->setIsVerified(false);

        // Generate and assign verification code
        $verificationCode = $user->generateVerificationCode();

        // Save user into teachers table
        $this->userRepository->save($user);

        // Send verification email
        $emailSent = $this->emailService->sendVerificationCode(
            $data['email'],
            $data['first_name'],
            $verificationCode
        );

        if (!$emailSent) {
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email de vérification',
            ];
        }

        return [
            'success' => true,
            'message' => 'Inscription réussie. Veuillez vérifier votre email.',
        ];
    }
}
