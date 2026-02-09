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
        // Find pending registration by email
        $pendingRegistration = $this->pendingRepository->findByEmail($request->email);

        if (!$pendingRegistration) {
            return new VerifyEmailResponse(
                false,
                'Aucune inscription en attente trouvée pour cet email'
            );
        }

        // Verify the code
        if (!$pendingRegistration->verify($request->verificationCode)) {
            return new VerifyEmailResponse(
                false,
                'Code de vérification incorrect'
            );
        }

        // Save the updated pending registration
        $this->pendingRepository->save($pendingRegistration);

        return new VerifyEmailResponse(
            true,
            'Email vérifié avec succès. En attente d\'approbation de l\'administrateur'
        );
    }
}
