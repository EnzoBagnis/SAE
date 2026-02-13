<?php

namespace Presentation\Controller\ResourceManagement;

use Domain\ResourceManagement\Repository\ResourceRepositoryInterface;

/**
 * Resource Visualization Controller
 * Handles resource visualization display
 */
class ResourceVisualizationController
{
    private ResourceRepositoryInterface $resourceRepository;

    /**
     * Constructor
     *
     * @param ResourceRepositoryInterface $resourceRepository Resource repository
     */
    public function __construct(
        ResourceRepositoryInterface $resourceRepository
    ) {
        $this->resourceRepository = $resourceRepository;
    }

    /**
     * Display resource visualization page
     *
     * @return void
     */
    public function index(): void
    {
        // Check authentication
        if (!isset($_SESSION['id'])) {
            header('Location: ' . BASE_URL . '/index.php?action=login');
            exit;
        }

        $userId = (int)$_SESSION['id'];
        $resourceId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$resourceId) {
            header('Location: ' . BASE_URL . '/index.php?action=resources_list');
            exit;
        }

        // Get resource
        $resource = $this->resourceRepository->findById($resourceId, $userId);

        if (!$resource) {
            header('Location: ' . BASE_URL . '/index.php?action=resources_list');
            exit;
        }

        // Verify access permissions
        $hasAccess = $this->resourceRepository->userHasAccess($resourceId, $userId);

        if (!$hasAccess) {
            header('Location: ' . BASE_URL . '/index.php?action=resources_list&error=access_denied');
            exit;
        }

        // Prepare data for the view
        $data = [
            'title' => htmlspecialchars($resource->getResourceName()) . ' - Visualisation',
            'user_firstname' => $_SESSION['prenom'] ?? 'Utilisateur',
            'user_lastname' => $_SESSION['nom'] ?? '',
            'user_email' => $_SESSION['mail'] ?? '',
            'resource' => $resource,
            'user_id' => $userId
        ];

        $this->loadView('user/resource_visualization', $data);
    }

    /**
     * Load a view
     *
     * @param string $view View name
     * @param array $data Data to pass to view
     * @return void
     */
    private function loadView(string $view, array $data = []): void
    {
        extract($data);
        require_once __DIR__ . '/../../Views/' . $view . '.php';
    }
}

