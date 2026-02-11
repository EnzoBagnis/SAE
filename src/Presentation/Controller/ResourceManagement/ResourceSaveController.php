<?php

namespace Presentation\Controller\ResourceManagement;

use Application\ResourceManagement\UseCase\CreateResource;
use Application\ResourceManagement\UseCase\UpdateResource;
use Application\ResourceManagement\DTO\CreateResourceRequest;
use Application\ResourceManagement\DTO\UpdateResourceRequest;

/**
 * Resource Save Controller
 * Handles resource creation and update operations
 */
class ResourceSaveController
{
    private CreateResource $createResourceUseCase;
    private UpdateResource $updateResourceUseCase;

    /**
     * Constructor
     *
     * @param CreateResource $createResourceUseCase Create resource use case
     * @param UpdateResource $updateResourceUseCase Update resource use case
     */
    public function __construct(CreateResource $createResourceUseCase, UpdateResource $updateResourceUseCase)
    {
        $this->createResourceUseCase = $createResourceUseCase;
        $this->updateResourceUseCase = $updateResourceUseCase;
    }

    /**
     * Save resource (create or update)
     *
     * @return void
     */
    public function save(): void
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

        $userId = $_SESSION['id'];
        $resourceId = $_POST['resource_id'] ?? null;
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? null;
        $sharedUsers = $_POST['shared_users'] ?? [];

        // Ensure shared_users is an array
        if (!is_array($sharedUsers)) {
            $sharedUsers = $sharedUsers ? explode(',', $sharedUsers) : [];
        }

        // Convert to integers and filter
        $sharedUsers = array_filter(array_map('intval', $sharedUsers));

        // Handle image upload
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->handleImageUpload($_FILES['image']);
        }

        // Determine if create or update
        if (empty($resourceId)) {
            // Create new resource
            $request = new CreateResourceRequest(
                $name,
                $userId,
                $description,
                $imagePath,
                $sharedUsers
            );

            $response = $this->createResourceUseCase->execute($request);
        } else {
            // Update existing resource
            $request = new UpdateResourceRequest(
                (int) $resourceId,
                $name,
                $userId,
                $description,
                $imagePath,
                $sharedUsers
            );

            $response = $this->updateResourceUseCase->execute($request);
        }

        // Redirect with result
        if ($response->success) {
            header('Location: ' . BASE_URL . '/index.php?action=resources_list&success=1');
        } else {
            $_SESSION['error'] = $response->message;
            header('Location: ' . BASE_URL . '/index.php?action=resources_list&error=1');
        }
        exit;
    }

    /**
     * Handle image file upload
     *
     * @param array $file Uploaded file information
     * @return string|null Image filename or null on failure
     */
    private function handleImageUpload(array $file): ?string
    {
        try {
            // Define upload directory (relative to project root)
            $uploadDir = dirname(__DIR__, 4) . '/images/';

            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($file['tmp_name']);

            if (!in_array($fileType, $allowedTypes)) {
                error_log("Invalid file type: $fileType");
                return null;
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . uniqid() . '.' . $extension;

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                return $filename;
            }

            return null;
        } catch (\Exception $e) {
            error_log("Error uploading image: " . $e->getMessage());
            return null;
        }
    }
}

