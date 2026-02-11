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
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check authentication
        if (!isset($_SESSION['id'])) {
            header('Location: ' . BASE_URL . '/index.php?action=login');
            exit;
        }

        $userId = (int)$_SESSION['id'];

        // Get resources accessible by user
        $resourceEntities = $this->resourceRepository->findAllAccessibleByUser($userId);

        // Convert Resource entities to simple objects for the view
        $resources = array_map(function($resource) {
            $data = $resource->toArray();
            // Get shared user IDs for this resource
            $sharedUserIds = $this->resourceRepository->getSharedUserIds($data['resource_id']);
            $data['shared_user_ids'] = implode(',', $sharedUserIds);
            return (object) $data;
        }, $resourceEntities);

        // Get all users for sharing (except current user)
        $allUsers = $this->getAllUsersExcept($userId);

        // Prepare data for the view
        $data = [
            'title' => 'StudTraj - Mes Ressources',
            'user_id' => $userId,
            'user_firstname' => $_SESSION['prenom'] ?? 'Utilisateur',
            'user_lastname' => $_SESSION['nom'] ?? '',
            'user_email' => $_SESSION['mail'] ?? '',
            'resources' => $resources,
            'all_users' => $allUsers
        ];

        $this->loadView('user/resources_list', $data);
    }

    /**
     * Get all users except the specified user
     *
     * @param int $exceptUserId User ID to exclude
     * @return array Array of users
     */
    private function getAllUsersExcept(int $exceptUserId): array
    {
        try {
            // We need direct DB access for this query
            // This is a temporary solution until we have a UserRepository
            $db = \Infrastructure\Persistence\DatabaseConnection::getConnection();
            $stmt = $db->prepare(
                "SELECT id, prenom, nom FROM utilisateurs WHERE id != :id ORDER BY nom ASC"
            );
            $stmt->execute([':id' => $exceptUserId]);
            return $stmt->fetchAll(\PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            error_log("Error fetching users: " . $e->getMessage());
            return [];
        }
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
