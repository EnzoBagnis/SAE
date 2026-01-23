<?php

namespace Controllers\User;

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Resource.php';
require_once __DIR__ . '/../../models/Exercise.php';


class ResourceDetailsController extends \BaseController
{
    /**
     * Show resource details page
     */
    public function index()
    {
        // Check if user is authenticated
        if (!isset($_SESSION['id'])) {
            header('Location: ' . BASE_URL . '/index.php?action=login');
            exit;
        }

        $user_id = $_SESSION['id'];
        $resourceId = $_GET['id'] ?? null;

        if (!$resourceId || !is_numeric($resourceId)) {
            header('Location: ' . BASE_URL . '/index.php?action=resources_list');
            exit;
        }

        $db = \Database::getConnection();
        $resource = \Resource::getResourceById($db, (int)$resourceId);

        if (!$resource) {
            header('Location: ' . BASE_URL . '/index.php?action=resources_list');
            exit;
        }

        // Vérifier les permissions d'accès à la ressource
        $hasAccess = false;
        if ($resource->owner_user_id === $user_id) {
            $hasAccess = true;
        } else {
            $stmt = $db->prepare(
                "SELECT 1 FROM resource_professors_access 
                 WHERE resource_id = :resourceId AND user_id = :userId"
            );
            $stmt->execute(['resourceId' => $resourceId, 'userId' => $user_id]);
            if ($stmt->fetch()) {
                $hasAccess = true;
            }
        }

        if (!$hasAccess) {
            header('Location: ' . BASE_URL . '/index.php?action=resources_list&error=access_denied');
            exit;
        }

        $exercises = \Exercise::getExercisesByResourceId($db, (int)$resourceId);

        // Prepare data for the view
        $data = [
            'title' => htmlspecialchars($resource->resource_name) . ' - TPs',
            'user_firstname' => $_SESSION['prenom'] ?? 'Utilisateur',
            'user_lastname' => $_SESSION['nom'] ?? '',
            'user_email' => $_SESSION['mail'] ?? '',
            'resource' => $resource,
            'exercises' => $exercises,
            'user_id' => $user_id,
            'db' => $db
        ];

        $this->loadView('user/resource_details', $data);
    }
}
