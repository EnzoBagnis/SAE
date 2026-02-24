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
    private ?ResourceRepository $resourceRepository = null;
    private AuthenticationService $authService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->authService = new AuthenticationService(new SessionService());
    }

    /**
     * Get ResourceRepository instance (lazy initialization)
     *
     * @return ResourceRepository
     */
    private function getRepository(): ResourceRepository
    {
        if ($this->resourceRepository === null) {
            $this->resourceRepository = new ResourceRepository();
        }
        return $this->resourceRepository;
    }

    /**
     * List resources
     *
     * @return void
     */
    public function index(): void
    {
        $this->authService->requireAuth('/auth/login');

        $email = $this->authService->getUserEmail();

        if ($email === null) {
            $this->redirect('/auth/login');
            return;
        }

        try {
            $ownedResources = $this->getRepository()->findByOwnerMail($email);
        } catch (\Exception $e) {
            $ownedResources = [];
        }

        try {
            $sharedResources = $this->getRepository()->findSharedWithMail($email);
        } catch (\Exception $e) {
            $sharedResources = [];
        }

        $this->renderView('resources/list', [
            'owned_resources'  => $ownedResources,
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

        try {
            $resource = $this->getRepository()->findById($resourceId);
        } catch (\Exception $e) {
            $resource = null;
        }

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
