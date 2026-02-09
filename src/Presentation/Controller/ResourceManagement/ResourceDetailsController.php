<?php

namespace Presentation\Controller\ResourceManagement;

use Domain\ResourceManagement\Repository\ResourceRepositoryInterface;
use Domain\ExerciseManagement\Repository\ExerciseRepositoryInterface;

/**
 * Resource Details Controller
 * Handles resource details display
 */
class ResourceDetailsController
{
    private ResourceRepositoryInterface $resourceRepository;
    private ExerciseRepositoryInterface $exerciseRepository;

    /**
     * Constructor
     *
     * @param ResourceRepositoryInterface $resourceRepository Resource repository
     * @param ExerciseRepositoryInterface $exerciseRepository Exercise repository
     */
    public function __construct(
        ResourceRepositoryInterface $resourceRepository,
        ExerciseRepositoryInterface $exerciseRepository
    ) {
        $this->resourceRepository = $resourceRepository;
        $this->exerciseRepository = $exerciseRepository;
    }

    /**
     * Display resource details page
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

        // Get exercises for this resource
        $exercises = $this->exerciseRepository->findByResourceId($resourceId);

        // Prepare data for the view
        $data = [
            'title' => htmlspecialchars($resource->getResourceName()) . ' - TPs',
            'user_firstname' => $_SESSION['prenom'] ?? 'Utilisateur',
            'user_lastname' => $_SESSION['nom'] ?? '',
            'user_email' => $_SESSION['mail'] ?? '',
            'resource' => $resource,
            'exercises' => $exercises,
            'user_id' => $userId
        ];

        $this->loadView('user/resource_details', $data);
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
