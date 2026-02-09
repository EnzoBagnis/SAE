<?php

namespace Presentation\Controller\UserManagement;

use Domain\ResourceManagement\Repository\ResourceRepositoryInterface;

/**
 * Dashboard Controller
 * Handles user dashboard display
 */
class DashboardController
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
     * Display dashboard page
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

        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;
        $resourceName = null;
        $currentUserId = (int)$_SESSION['id'];

        // If resource_id is provided, verify access and get resource name
        if ($resourceId) {
            try {
                $resource = $this->resourceRepository->findById($resourceId, $currentUserId);

                if ($resource) {
                    // Check access permissions
                    $hasAccess = $this->resourceRepository->userHasAccess($resourceId, $currentUserId);

                    if (!$hasAccess) {
                        // User does not have access to this resource
                        $redirectUrl = BASE_URL . '/index.php?action=dashboard';

                        if (
                            isset($_SESSION['last_valid_resource_id']) &&
                            $_SESSION['last_valid_resource_id'] != $resourceId
                        ) {
                            $redirectUrl .= '&resource_id=' . (int)$_SESSION['last_valid_resource_id'];
                        }

                        header('Location: ' . $redirectUrl);
                        exit;
                    }

                    // Save current resource as last valid resource
                    $_SESSION['last_valid_resource_id'] = $resourceId;
                    $resourceName = $resource->getResourceName();
                }
            } catch (\Exception $e) {
                error_log("Error loading resource: " . $e->getMessage());
            }
        }

        // Prepare data for the view
        $data = [
            'title' => $resourceName ? "StudTraj - $resourceName" : 'StudTraj - Tableau de bord',
            'user_firstname' => $_SESSION['prenom'] ?? 'Utilisateur',
            'user_lastname' => $_SESSION['nom'] ?? '',
            'user_email' => $_SESSION['mail'] ?? '',
            'resource_id' => $resourceId,
            'resource_name' => $resourceName
        ];

        $this->loadView('user/dashboard', $data);
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
