<?php

namespace App\Model\UseCase;

use App\Model\UserRepository;
use App\Model\PendingRegistrationRepository;
use App\Model\Entity\PendingRegistration;
use App\Model\EmailService;

/**
 * Register User Use Case
 * Handles user registration process
 */
class RegisterUserUseCase
{
    private UserRepository $userRepository;
    private PendingRegistrationRepository $pendingRepository;
    private EmailService $emailService;

    /**
     * Constructor
     *
     * @param UserRepository $userRepository User repository
     * @param PendingRegistrationRepository $pendingRepository Pending registration repository
     * @param EmailService $emailService Email service
     */
    public function __construct(
        UserRepository $userRepository,
        PendingRegistrationRepository $pendingRepository,
        EmailService $emailService
    ) {
        $this->userRepository = $userRepository;
        $this->pendingRepository = $pendingRepository;
        $this->emailService = $emailService;
    }

    /**
     * Execute registration use case
     *
     * @param array $data Registration data
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

        // Check if email already exists
        if ($this->userRepository->emailExists($data['email'])) {
            return [
                'success' => false,
                'message' => 'Cet email est déjà enregistré',
            ];
        }

        if ($this->pendingRepository->emailExists($data['email'])) {
            return [
                'success' => false,
                'message' => 'Une inscription avec cet email est déjà en attente',
            ];
        }

        // Create pending registration
        $registration = new PendingRegistration();
        $registration->setLastName($data['last_name']);
        $registration->setFirstName($data['first_name']);
        $registration->setEmail($data['email']);
        $registration->setPasswordHash(password_hash($data['password'], PASSWORD_DEFAULT));
        $registration->setCreatedAt(new \DateTimeImmutable());

        // Generate verification code
        $verificationCode = $registration->generateVerificationCode();

        // Save pending registration
        $savedRegistration = $this->pendingRepository->save($registration);

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
            'registration_id' => $savedRegistration->getId(),
        ];
    }
}

