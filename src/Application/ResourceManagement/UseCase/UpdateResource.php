<?php

namespace Application\ResourceManagement\UseCase;

use Application\ResourceManagement\DTO\UpdateResourceRequest;
use Application\ResourceManagement\DTO\ResourceResponse;
use Domain\ResourceManagement\Repository\ResourceRepositoryInterface;

/**
 * UpdateResource Use Case
 *
 * Handles resource update business logic
 */
class UpdateResource
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
     * Execute resource update
     *
     * @param UpdateResourceRequest $request Update request
     * @return ResourceResponse Response with result
     */
    public function execute(UpdateResourceRequest $request): ResourceResponse
    {
        try {
            // Validate input
            if (empty($request->name)) {
                return new ResourceResponse(false, 'Le nom de la ressource est requis');
            }

            // Check if user has access to this resource
            if (!$this->resourceRepository->userHasAccess($request->resourceId, $request->userId)) {
                return new ResourceResponse(false, 'Accès non autorisé');
            }

            // Update resource
            $success = $this->resourceRepository->update(
                $request->resourceId,
                $request->name,
                $request->description,
                $request->imagePath
            );

            if (!$success) {
                return new ResourceResponse(false, 'Erreur lors de la mise à jour');
            }

            // Update shared users
            $this->resourceRepository->updateSharedUsers($request->resourceId, $request->sharedUserIds);

            return new ResourceResponse(true, 'Ressource mise à jour avec succès', $request->resourceId);
        } catch (\Exception $e) {
            error_log("Error updating resource: " . $e->getMessage());
            return new ResourceResponse(false, 'Erreur lors de la mise à jour de la ressource');
        }
    }
}

