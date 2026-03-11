<?php

namespace App\Controller;

use Core\Controller\AbstractController;
use App\Model\ResourceRepositoryInterface;
use App\Model\AuthenticationServiceInterface;

/**
 * Resources Controller
 *
 * Handles resource management (list, create, update, delete, share).
 * Depends on interfaces for repository and authentication, enabling
 * testability and respecting the Dependency Inversion Principle.
 */
class ResourcesController extends AbstractController
{
    private ResourceRepositoryInterface $resourceRepository;
    private AuthenticationServiceInterface $authService;

    /** Allowed MIME types for resource images */
    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /** Max image size in bytes (2 MB) */
    private const MAX_IMAGE_SIZE = 2 * 1024 * 1024;

    /** Upload directory relative to project root */
    private const UPLOAD_DIR = 'images/resources/';

    /**
     * Constructor
     *
     * @param ResourceRepositoryInterface    $resourceRepository Resource repository
     * @param AuthenticationServiceInterface $authService        Authentication service
     */
    public function __construct(
        ResourceRepositoryInterface $resourceRepository,
        AuthenticationServiceInterface $authService
    ) {
        $this->resourceRepository = $resourceRepository;
        $this->authService        = $authService;
    }

    /**
     * List all resources accessible by the authenticated teacher.
     * Also loads all other teachers for the sharing form.
     *
     * @return void
     */
    public function index(): void
    {
        $this->authService->requireAuth('/auth/login');

        $email = $this->authService->getUserEmail();
        if ($email === null) {
            $this->redirect('/auth/login');
            return;
        }

        try {
            $ownedResources = $this->resourceRepository->findByOwnerMail($email);
        } catch (\Throwable $e) {
            error_log('[ResourcesController::index] findByOwnerMail: ' . $e->getMessage());
            $ownedResources = [];
        }

        try {
            $sharedResources = $this->resourceRepository->findSharedWithMail($email);
        } catch (\Throwable $e) {
            error_log('[ResourcesController::index] findSharedWithMail: ' . $e->getMessage());
            $sharedResources = [];
        }

        try {
            $allTeachers = $this->resourceRepository->findAllTeachersExcept($email);
        } catch (\Throwable $e) {
            error_log('[ResourcesController::index] findAllTeachersExcept: ' . $e->getMessage());
            $allTeachers = [];
        }

        $this->renderView('resources/list', [
            'owned_resources'  => $ownedResources,
            'shared_resources' => $sharedResources,
            'all_teachers'     => $allTeachers,
        ]);
    }

    /**
     * Handle resource creation (POST from modal form).
     * Supports image file upload and sharing list.
     *
     * @return void
     */
    public function store(): void
    {
        $this->authService->requireAuth('/auth/login');

        $email = $this->authService->getUserEmail();
        if ($email === null) {
            $this->redirect('/auth/login');
            return;
        }

        $name        = trim($this->getPost('name', ''));
        $description = trim($this->getPost('description', ''));

        if ($name === '') {
            $this->redirect('/resources?error=Le+nom+de+la+ressource+est+obligatoire.');
            return;
        }

        // Handle image upload
        $imagePath = $this->handleImageUpload();

        $resource = new \App\Model\Entity\Resource();
        $resource->setOwnerMail($email);
        $resource->setResourceName($name);
        $resource->setDescription($description !== '' ? $description : null);
        $resource->setImagePath($imagePath);

        try {
            $this->resourceRepository->save($resource);

            // Sync sharing list
            $sharedMails = $_POST['shared_teachers'] ?? [];
            if (!empty($sharedMails) && is_array($sharedMails)) {
                $this->resourceRepository->syncSharing($resource->getResourceId(), $sharedMails);
            }
        } catch (\Throwable $e) {
            error_log('[ResourcesController::store] ' . $e->getMessage());
            $this->redirect('/resources?error=' . urlencode('Erreur lors de la création : ' . $e->getMessage()));
            return;
        }

        $this->redirect('/resources');
    }

    /**
     * Show resource details page.
     *
     * @param int $resourceId Resource ID
     * @return void
     */
    public function show(int $resourceId): void
    {
        $this->authService->requireAuth('/auth/login');

        try {
            $resource = $this->resourceRepository->findById($resourceId);
        } catch (\Throwable $e) {
            error_log('[ResourcesController::show] findById error: ' . $e->getMessage());
            $resource = null;
        }

        if (!$resource) {
            http_response_code(404);
            $this->renderView('errors/404');
            return;
        }

        // Retrieve user names via the authentication service (no direct session access)
        $firstname = $this->authService->getUserFirstName() ?? '';
        $lastname  = $this->authService->getUserLastName() ?? '';

        try {
            $this->renderView('user/dashboard', [
                'resource'       => $resource,
                'resource_id'    => $resourceId,
                'user_firstname' => $firstname,
                'user_lastname'  => $lastname,
                'title'          => 'StudTraj - ' . htmlspecialchars($resource->getResourceName()),
            ]);
        } catch (\Throwable $e) {
            error_log('[ResourcesController::show] renderView error: ' . $e->getMessage());
            http_response_code(500);
            $this->renderView('errors/500');
        }
    }

    /**
     * Handle resource update (POST from modal edit form).
     * Supports image file upload and sharing list sync.
     *
     * @param int $resourceId Resource ID
     * @return void
     */
    public function update(int $resourceId): void
    {
        $this->authService->requireAuth('/auth/login');

        $email = $this->authService->getUserEmail();
        if ($email === null) {
            $this->redirect('/auth/login');
            return;
        }

        try {
            $resource = $this->resourceRepository->findById($resourceId);
        } catch (\Throwable $e) {
            error_log('[ResourcesController::update] findById: ' . $e->getMessage());
            $resource = null;
        }

        if (!$resource) {
            $this->redirect('/resources?error=Ressource+introuvable.');
            return;
        }

        if ($resource->getOwnerMail() !== $email) {
            $this->redirect('/resources?error=Action+non+autorisée.');
            return;
        }

        $name        = trim($this->getPost('name', ''));
        $description = trim($this->getPost('description', ''));

        if ($name === '') {
            $this->redirect('/resources?error=Le+nom+de+la+ressource+est+obligatoire.');
            return;
        }

        // Handle image upload (keep existing if no new file)
        $newImagePath = $this->handleImageUpload();
        $imagePath = $newImagePath ?? $resource->getImagePath();

        $resource->setResourceName($name);
        $resource->setDescription($description !== '' ? $description : null);
        $resource->setImagePath($imagePath);

        try {
            $this->resourceRepository->save($resource);

            // Sync sharing list
            $sharedMails = $_POST['shared_teachers'] ?? [];
            $this->resourceRepository->syncSharing($resourceId, is_array($sharedMails) ? $sharedMails : []);
        } catch (\Throwable $e) {
            error_log('[ResourcesController::update] save: ' . $e->getMessage());
            $this->redirect('/resources?error=' . urlencode('Erreur lors de la mise à jour : ' . $e->getMessage()));
            return;
        }

        $this->redirect('/resources');
    }

    /**
     * Delete a resource (POST).
     *
     * @param int $resourceId Resource ID
     * @return void
     */
    public function delete(int $resourceId): void
    {
        $this->authService->requireAuth('/auth/login');

        $email = $this->authService->getUserEmail();
        if ($email === null) {
            $this->redirect('/auth/login');
            return;
        }

        try {
            $resource = $this->resourceRepository->findById($resourceId);
        } catch (\Throwable $e) {
            error_log('[ResourcesController::delete] findById: ' . $e->getMessage());
            $resource = null;
        }

        if (!$resource) {
            $this->redirect('/resources?error=Ressource+introuvable.');
            return;
        }

        if ($resource->getOwnerMail() !== $email) {
            $this->redirect('/resources?error=Action+non+autorisée.');
            return;
        }

        try {
            $this->resourceRepository->delete($resourceId);
        } catch (\Throwable $e) {
            // Suppression échouée silencieusement — on redirige quand même
            error_log('[ResourcesController::delete] ' . $e->getMessage());
        }

        $this->redirect('/resources');
    }

    /**
     * Handle image file upload from $_FILES['image'].
     * Returns the stored relative path or null if no file was uploaded.
     *
     * @return string|null Relative path to uploaded image, or null
     */
    private function handleImageUpload(): ?string
    {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $file = $_FILES['image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        if ($file['size'] > self::MAX_IMAGE_SIZE) {
            return null;
        }

        // Vérification du type MIME via finfo (extension fileinfo intégrée à PHP)
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, self::ALLOWED_IMAGE_TYPES, true)) {
            return null;
        }

        $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename  = uniqid('res_', true) . '.' . strtolower($ext);
        $uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . '/' . self::UPLOAD_DIR;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            return self::UPLOAD_DIR . $filename;
        }

        return null;
    }
}
