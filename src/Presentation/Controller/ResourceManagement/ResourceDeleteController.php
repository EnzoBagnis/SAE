<?php

namespace Presentation\Controller\ResourceManagement;

use Application\ResourceManagement\UseCase\DeleteResource;
use Application\ResourceManagement\DTO\DeleteResourceRequest;

/**
 * Resource Delete Controller
 * Handles resource deletion operations
 */
class ResourceDeleteController
{
    private DeleteResource $deleteResourceUseCase;

    /**
     * Constructor
     *
     * @param DeleteResource $deleteResourceUseCase Delete resource use case
     */
    public function __construct(DeleteResource $deleteResourceUseCase)
    {
        $this->deleteResourceUseCase = $deleteResourceUseCase;
    }

    /**
     * Delete a resource
     *
     * @return void
     */
    public function delete(): void
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

        // Check if it's a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?action=resources_list');
            exit;
        }

        $userId = (int)$_SESSION['id'];
        $resourceId = isset($_POST['resource_id']) ? (int)$_POST['resource_id'] : 0;

        if ($resourceId <= 0) {
            $_SESSION['error'] = 'ID de ressource invalide';
            header('Location: ' . BASE_URL . '/index.php?action=resources_list');
            exit;
        }

        // Create request
        $request = new DeleteResourceRequest($resourceId, $userId);

        // Execute deletion
        $response = $this->deleteResourceUseCase->execute($request);

        // Redirect with result
        if ($response->success) {
            $_SESSION['success'] = $response->message;
            header('Location: ' . BASE_URL . '/index.php?action=resources_list');
        } else {
            $_SESSION['error'] = $response->message;
            header('Location: ' . BASE_URL . '/index.php?action=resources_list');
        }
        exit;
    }
}

