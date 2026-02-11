<?php

namespace Application\ResourceManagement\UseCase;

use Application\ResourceManagement\DTO\DeleteResourceRequest;
use Application\ResourceManagement\DTO\ResourceResponse;
use Domain\ResourceManagement\Repository\ResourceRepositoryInterface;

/**
 * DeleteResource Use Case
 *
 * Handles resource deletion business logic
 */
class DeleteResource
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
     * Execute resource deletion
     *
     * @param DeleteResourceRequest $request Deletion request
     * @return ResourceResponse Response with result
     */
    public function execute(DeleteResourceRequest $request): ResourceResponse
    {
        try {
            // Check if resource exists and user has access
            $resource = $this->resourceRepository->findById($request->resourceId, $request->userId);

            if (!$resource) {
                return new ResourceResponse(false, 'Ressource non trouvée');
            }

            // Only the owner can delete a resource
            if ($resource->getOwnerUserId() !== $request->userId) {
                return new ResourceResponse(false, 'Vous n\'êtes pas autorisé à supprimer cette ressource');
            }

            // Delete the resource
            $success = $this->resourceRepository->delete($request->resourceId);

            if ($success) {
                return new ResourceResponse(true, 'Ressource supprimée avec succès', $request->resourceId);
            } else {
                return new ResourceResponse(false, 'Erreur lors de la suppression');
            }
        } catch (\Exception $e) {
            error_log("Error deleting resource: " . $e->getMessage());
            return new ResourceResponse(false, 'Erreur lors de la suppression de la ressource');
        }
    }
}

