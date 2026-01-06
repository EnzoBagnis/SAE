<?php

namespace Controllers\User;

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../models/Resource.php';
require_once __DIR__ . '/../../models/Database.php';

/**
 * DashboardController - Handles user dashboard
 */
class DashboardController extends \BaseController
{
    /**
     * Show dashboard page
     */
    public function index()
    {
        // Check if user is authenticated
        if (!isset($_SESSION['id'])) {
            header('Location: /index.php?action=login');
            exit;
        }

        // Get resource_id from URL if provided
        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;
        $resourceName = null;

        // If resource_id is provided, get resource name
        if ($resourceId) {
            try {
                $db = \Database::getConnection();
                $resource = \Resource::getResourceById($db, $resourceId);
                if ($resource) {
                    // Verify access permissions (Owner or Shared)
                    $hasAccess = ($resource->owner_user_id === $_SESSION['id']);

                    if (!$hasAccess) {
                        $stmt = $db->prepare("SELECT 1 FROM resource_professors_access WHERE resource_id = :resourceId AND user_id = :userId");
                        $stmt->execute(['resourceId' => $resourceId, 'userId' => $_SESSION['id']]);
                        if ($stmt->fetch()) {
                            $hasAccess = true;
                        }
                    }

                    if (!$hasAccess) {
                        // User does not have access to this resource, redirect to dashboard root
                        header('Location: /index.php?action=dashboard');
                        exit;
                    }

                    $resourceName = $resource->resource_name;
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
}
