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
            header('Location: ' . BASE_URL . '/index.php?action=login');
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
                    // Ensure strict integer comparison for security
                    $currentUserId = (int)$_SESSION['id'];
                    $ownerId = (int)$resource->owner_user_id;

                    $hasAccess = ($ownerId === $currentUserId);

                    if (!$hasAccess) {
                        $stmt = $db->prepare(
                            "SELECT 1 FROM resource_professors_access " .
                            "WHERE resource_id = :resourceId AND user_id = :userId"
                        );
                        $stmt->execute(['resourceId' => $resourceId, 'userId' => $currentUserId]);
                        if ($stmt->fetch()) {
                            $hasAccess = true;
                        }
                    }

                    if (!$hasAccess) {
                        // User does not have access to this resource
                        // Redirect to previous valid resource if available, otherwise to dashboard root
                        $redirectUrl = BASE_URL . '/index.php?action=dashboard';

                        if (
                            isset($_SESSION['last_valid_resource_id']) &&
                            $_SESSION['last_valid_resource_id'] != $resourceId
                        ) {
                            // Verify if we still have access to the last valid resource to avoid loops
                            // (Simplified check: just redirect, if distinct)
                            $redirectUrl .= '&resource_id=' . (int)$_SESSION['last_valid_resource_id'];
                        }

                        header('Location: ' . $redirectUrl);
                        exit;
                    }

                    // Save current resource as last valid resource for future redirections
                    $_SESSION['last_valid_resource_id'] = $resourceId;

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
