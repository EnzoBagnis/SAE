<?php

namespace App\Model\UseCase;

use App\Model\UseCase\Ports\UserRegistrationPort;
use App\Model\Entity\User;
use App\Model\EmailService;

/**
 * Register User Use Case.
 *
 * Inserts directly into the `teachers` table with account_status=0 (pending approval).
 * Depends on {@see UserRegistrationPort} for persistence, following the
 * Dependency Inversion Principle.
 */
class RegisterUserUseCase
{
    private UserRegistrationPort $userRepository;
    private EmailService $emailService;

    /**
     * Constructor.
     *
     * @param UserRegistrationPort $userRepository Port for user persistence operations.
     * @param EmailService         $emailService   Email service for verification emails.
     */
    public function __construct(
        UserRegistrationPort $userRepository,
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

        if (empty($data['password']) || !$this->isPasswordValid($data['password'])) {
            return [
                'success' => false,
                'message' => 'Le mot de passe doit contenir au moins 12 caractères, une majuscule, une minuscule et un caractère spécial',
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

    /**
     * Validate password strength.
     *
     * A valid password must:
     * - Be at least 12 characters long
     * - Contain at least one uppercase letter
     * - Contain at least one lowercase letter
     * - Contain at least one special character
     *
     * @param string $password Password to validate.
     * @return bool True if the password meets all requirements.
     */
    private function isPasswordValid(string $password): bool
    {
        if (strlen($password) < 12) {
            return false;
        }

        // At least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // At least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // At least one special character
        if (!preg_match('/[\W_]/', $password)) {
            return false;
        }

        return true;
    }
}
