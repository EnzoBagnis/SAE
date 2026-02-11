<?php

namespace Application\ResourceManagement\UseCase;

use Application\ResourceManagement\DTO\CreateResourceRequest;
use Application\ResourceManagement\DTO\ResourceResponse;
use Domain\ResourceManagement\Repository\ResourceRepositoryInterface;

/**
 * CreateResource Use Case
 *
 * Handles resource creation business logic
 */
class CreateResource
{
    private ResourceRepositoryInterface $resourceRepository;

    /**
     * Constructor
     *
     * @param ResourceRepositoryInterface $resourceRepository Resource repository
     */
    public function __construct(ResourceRepositoryInterface $resourceRepository)
    {
        $this->resourceRepository = $resourceRepository;
    }

    /**
     * Execute resource creation
     *
     * @param CreateResourceRequest $request Creation request
     * @return ResourceResponse Response with result
     */
    public function execute(CreateResourceRequest $request): ResourceResponse
    {
        try {
            // Validate input
            if (empty($request->name)) {
                return new ResourceResponse(false, 'Le nom de la ressource est requis');
            }

            // Create resource
            $resourceId = $this->resourceRepository->create(
                $request->name,
                $request->userId,
                $request->description,
                $request->imagePath
            );

            // Update shared users if any
            if (!empty($request->sharedUserIds)) {
                $this->resourceRepository->updateSharedUsers($resourceId, $request->sharedUserIds);
            }

            return new ResourceResponse(true, 'Ressource créée avec succès', $resourceId);
        } catch (\Exception $e) {
            error_log("Error creating resource: " . $e->getMessage());
            return new ResourceResponse(false, 'Erreur lors de la création de la ressource');
        }
    }
}

