<?php

namespace Application\Authentication\UseCase;

use Application\Authentication\DTO\RegisterRequest;
use Application\Authentication\DTO\RegisterResponse;
use Domain\Authentication\Entity\PendingRegistration;
use Domain\Authentication\Repository\UserRepositoryInterface;
use Domain\Authentication\Repository\PendingRegistrationRepositoryInterface;
use Domain\Authentication\Service\EmailServiceInterface;

/**
 * RegisterUser Use Case - Registers a new user and sends verification email
 *
 * This use case handles the complete user registration process including
 * validation, creating pending registration, and sending verification email.
 */
class RegisterUser
{
    private UserRepositoryInterface $userRepository;
    private PendingRegistrationRepositoryInterface $pendingRepository;
    private EmailServiceInterface $emailService;

    /**
     * Constructor
     *
     * @param UserRepositoryInterface $userRepository Repository for users
     * @param PendingRegistrationRepositoryInterface $pendingRepository Repository for pending registrations
     * @param EmailServiceInterface $emailService Email service
     */
    public function __construct(
        UserRepositoryInterface $userRepository,
        PendingRegistrationRepositoryInterface $pendingRepository,
        EmailServiceInterface $emailService
    ) {
        $this->userRepository = $userRepository;
        $this->pendingRepository = $pendingRepository;
        $this->emailService = $emailService;
    }

    /**
     * Execute the registration use case
     *
     * @param RegisterRequest $request Registration request data
     * @return RegisterResponse Registration result
     */
    public function execute(RegisterRequest $request): RegisterResponse
    {
        // Check if email already exists in active users
        if ($this->userRepository->emailExists($request->email)) {
            return new RegisterResponse(
                false,
                'Cet email est déjà enregistré',
                null
            );
        }

        // Check if email already exists in pending registrations
        if ($this->pendingRepository->emailExists($request->email)) {
            return new RegisterResponse(
                false,
                'Une inscription avec cet email est déjà en attente',
                null
            );
        }

        // Hash password
        $passwordHash = password_hash($request->password, PASSWORD_DEFAULT);

        // Create pending registration
        $pendingRegistration = new PendingRegistration(
            null,
            $request->lastName,
            $request->firstName,
            $request->email,
            $passwordHash
        );

        // Generate verification code
        $verificationCode = $pendingRegistration->generateVerificationCode();

        // Save pending registration
        $savedRegistration = $this->pendingRepository->save($pendingRegistration);

        // Send verification email
        $emailSent = $this->emailService->sendVerificationCode(
            $request->email,
            $request->firstName,
            $verificationCode
        );

        if (!$emailSent) {
            return new RegisterResponse(
                false,
                'Erreur lors de l\'envoi de l\'email de vérification',
                null
            );
        }

        return new RegisterResponse(
            true,
            'Inscription réussie. Veuillez vérifier votre email',
            $savedRegistration
        );
    }
}
