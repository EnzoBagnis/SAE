<?php

namespace Presentation\Controller\ResourceManagement;

use Domain\ResourceManagement\Repository\ResourceRepositoryInterface;

/**
 * Resources List Controller
 * Handles resource list display
 */
class ResourcesListController
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
     * Display resources list page
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

        // Prepare data for the view
        $data = [
            'title' => 'StudTraj - Tableau de bord',
            'user_firstname' => $_SESSION['prenom'] ?? 'Utilisateur',
            'user_lastname' => $_SESSION['nom'] ?? '',
            'user_email' => $_SESSION['mail'] ?? ''
        ];

        $this->loadView('user/resources_list', $data);
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
        require_once __DIR__ . '/../../../../views/' . $view . '.php';
    }
}
