<?php

namespace Application\Authentication\UseCase;

use Application\Authentication\DTO\VerifyEmailRequest;
use Application\Authentication\DTO\VerifyEmailResponse;
use Domain\Authentication\Repository\PendingRegistrationRepositoryInterface;

/**
 * VerifyUserEmail Use Case - Verifies user's email with verification code
 *
 * This use case handles email verification by checking the provided code
 * against the pending registration.
 */
class VerifyUserEmail
{
    private PendingRegistrationRepositoryInterface $pendingRepository;

    /**
     * Constructor
     *
     * @param PendingRegistrationRepositoryInterface $pendingRepository Repository for pending registrations
     */
    public function __construct(PendingRegistrationRepositoryInterface $pendingRepository)
    {
        $this->pendingRepository = $pendingRepository;
    }

    /**
     * Execute the email verification use case
     *
     * @param VerifyEmailRequest $request Verification request data
     * @return VerifyEmailResponse Verification result
     */
    public function execute(VerifyEmailRequest $request): VerifyEmailResponse
    {
        // Debug logging
        error_log("VerifyUserEmail::execute() - Email: " . $request->email);
        error_log("VerifyUserEmail::execute() - Code: " . $request->verificationCode);

        // Find pending registration by email
        $pendingRegistration = $this->pendingRepository->findByEmail($request->email);

        if (!$pendingRegistration) {
            error_log("VerifyUserEmail::execute() - No pending registration found");
            return new VerifyEmailResponse(
                false,
                'Aucune inscription en attente trouvée pour cet email'
            );
        }

        error_log("VerifyUserEmail::execute() - Found pending registration, stored code: " . $pendingRegistration->getVerificationCode());

        // Verify the code
        if (!$pendingRegistration->verify($request->verificationCode)) {
            error_log("VerifyUserEmail::execute() - Code verification failed");
            return new VerifyEmailResponse(
                false,
                'Code de vérification incorrect'
            );
        }

        error_log("VerifyUserEmail::execute() - Code verification successful");

        // Save the updated pending registration
        $this->pendingRepository->save($pendingRegistration);

        return new VerifyEmailResponse(
            true,
            'Email vérifié avec succès. En attente d\'approbation de l\'administrateur'
        );
    }
}
