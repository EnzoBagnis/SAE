<?php

namespace App\Controller;

use Core\Controller\AbstractController;
use App\Model\ResourceRepository;
use App\Model\AuthenticationService;
use Core\Service\SessionService;

/**
 * Resources Controller
 * Handles resource management
 */
class ResourcesController extends AbstractController
{
    private ResourceRepository $resourceRepository;
    private AuthenticationService $authService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->resourceRepository = new ResourceRepository();
        $this->authService = new AuthenticationService(new SessionService());
    }

    /**
     * List resources
     *
     * @return void
     */
    public function index(): void
    {
        $this->authService->requireAuth('/auth/login');

        $userId = $this->authService->getUserId();
        $ownedResources = $this->resourceRepository->findByOwnerUserId($userId);
        $sharedResources = $this->resourceRepository->findSharedWithUser($userId);

        $this->renderView('resources/list', [
            'owned_resources' => $ownedResources,
            'shared_resources' => $sharedResources,
        ]);
    }

    /**
     * Show resource details
     *
     * @param int $resourceId Resource ID
     * @return void
     */
    public function show(int $resourceId): void
    {
        $this->authService->requireAuth('/auth/login');

        $resource = $this->resourceRepository->findById($resourceId);

        if (!$resource) {
            http_response_code(404);
            $this->renderView('errors/404');
            return;
        }

        $this->renderView('resources/details', [
            'resource' => $resource,
        ]);
    }
}

