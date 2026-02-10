<?php

namespace Presentation\Controller\Administration;

use Domain\Authentication\Repository\UserRepositoryInterface;
use Domain\Authentication\Repository\PendingRegistrationRepositoryInterface;

/**
 * AdminController - Handles administration panel
 *
 * This controller manages the admin interface for user management,
 * including viewing, editing, deleting, and approving users.
 */
class AdminController
{
    private UserRepositoryInterface $userRepository;
    private PendingRegistrationRepositoryInterface $pendingRepository;

    /**
     * Constructor
     *
     * @param UserRepositoryInterface $userRepository User repository
     * @param PendingRegistrationRepositoryInterface $pendingRepository Pending registration repository
     */
    public function __construct(
        UserRepositoryInterface $userRepository,
        PendingRegistrationRepositoryInterface $pendingRepository
    ) {
        $this->userRepository = $userRepository;
        $this->pendingRepository = $pendingRepository;
    }

    /**
     * Display admin login page
     *
     * @return void
     */
    public function showLogin(): void
    {
        $title = 'Connexion Admin - StudTraj';
        require_once SRC_PATH . '/Presentation/Views/admin/admin-login.php';
    }

    /**
     * Handle admin authentication
     *
     * @return void
     */
    public function authenticate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?action=admin');
            exit;
        }

        $id = $_POST['ID'] ?? '';
        $password = $_POST['mdp'] ?? '';

        // Load admin credentials from .env file
        $env = $this->loadEnv();
        $adminId = $env['ADMIN_ID'] ?? '';
        $adminPass = $env['ADMIN_PASS'] ?? '';

        // Verify admin credentials
        if ($id !== $adminId || $password !== $adminPass) {
            $error_message = 'Identifiant ou mot de passe incorrect';
            $title = 'Connexion Admin - StudTraj';
            require_once SRC_PATH . '/Presentation/Views/admin/admin-login.php';
            return;
        }

        // Set admin session
        $_SESSION['user_id'] = 'admin';
        $_SESSION['nom'] = 'Admin';
        $_SESSION['prenom'] = $adminId;
        $_SESSION['email'] = 'admin@studtraj.com';
        $_SESSION['is_admin'] = true;

        header('Location: ' . BASE_URL . '/index.php?action=adminDashboard');
        exit;
    }

    /**
     * Display admin dashboard
     *
     * @return void
     */
    public function dashboard(): void
    {
        // Check if user is authenticated as admin
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            header('Location: ' . BASE_URL . '/index.php?action=admin');
            exit;
        }

        // Get all users
        $allUsers = $this->userRepository->findAll();

        // Convert User entities to array format expected by the view
        $verifiedUsers = array_map(function($user) {
            return [
                'id' => $user->getId(),
                'nom' => $user->getLastName(),
                'prenom' => $user->getFirstName(),
                'mail' => $user->getEmail()
            ];
        }, $allUsers);

        // Get pending registrations
        $allPending = $this->pendingRepository->findAll();

        // Convert PendingRegistration entities to array format expected by the view
        $pendingUsers = array_map(function($pending) {
            return [
                'id' => $pending->getId(),
                'nom' => $pending->getLastName(),
                'prenom' => $pending->getFirstName(),
                'mail' => $pending->getEmail(),
                'verifie' => $pending->isVerified() ? 1 : 0
            ];
        }, $allPending);

        // Get blocked/banned users
        $blockedUsers = $this->getBannedUsers();

        $title = 'Panel Admin - StudTraj';
        require_once SRC_PATH . '/Presentation/Views/admin/admin-dashboard.php';
    }

    /**
     * Handle user deletion
     *
     * @return void
     */
    public function deleteUser(): void
    {
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            header('Location: ' . BASE_URL . '/index.php?action=admin');
            exit;
        }

        $userId = $_GET['id'] ?? null;
        $table = $_GET['table'] ?? 'V';

        if (!$userId) {
            header('Location: ' . BASE_URL . '/index.php?action=adminDashboard&error=user_not_found');
            exit;
        }

        // Prevent self-deletion
        if ($table !== 'B' && $userId == $_SESSION['user_id']) {
            header('Location: ' . BASE_URL . '/index.php?action=adminDashboard&error=cannot_delete_self');
            exit;
        }

        $success = false;

        if ($table === 'P') {
            // Delete from pending registrations
            $success = $this->pendingRepository->delete((int)$userId);
        } elseif ($table === 'B') {
            // Unban user - delete from banned users table
            $success = $this->unbanUser($userId);
        } else {
            // Delete from users
            $success = $this->userRepository->delete((int)$userId);
        }

        if ($success) {
            $message = $table === 'B' ? 'unbanned' : 'deleted';
            header('Location: ' . BASE_URL . '/index.php?action=adminDashboard&success=' . $message);
        } else {
            header('Location: ' . BASE_URL . '/index.php?action=adminDashboard&error=delete_failed');
        }
        exit;
    }

    /**
     * Handle user validation (approve pending registration)
     *
     * @return void
     */
    public function validateUser(): void
    {
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            header('Location: ' . BASE_URL . '/index.php?action=admin');
            exit;
        }

        $userId = $_GET['id'] ?? null;

        if (!$userId) {
            header('Location: ' . BASE_URL . '/index.php?action=adminDashboard&error=user_not_found');
            exit;
        }

        // Get pending registration
        $pendingUser = $this->pendingRepository->findById((int)$userId);

        if (!$pendingUser) {
            header('Location: ' . BASE_URL . '/index.php?action=adminDashboard&error=user_not_found');
            exit;
        }

        // Create user from pending registration
        $user = new \Domain\Authentication\Entity\User(
            null,
            $pendingUser->getLastName(),
            $pendingUser->getFirstName(),
            $pendingUser->getEmail(),
            $pendingUser->getPasswordHash(),
            null,  // verificationCode - null because already verified
            true,  // isVerified - true because admin is validating
            $pendingUser->getCreatedAt()
        );

        // Save user
        $this->userRepository->save($user);

        // Delete from pending
        $this->pendingRepository->delete((int)$userId);

        header('Location: ' . BASE_URL . '/index.php?action=adminDashboard&success=validated');
        exit;
    }

    /**
     * Handle user edit
     *
     * @return void
     */
    public function editUser(): void
    {
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            header('Location: ' . BASE_URL . '/index.php?action=admin');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?action=adminDashboard');
            exit;
        }

        // Implementation for editing user
        header('Location: ' . BASE_URL . '/index.php?action=adminDashboard');
        exit;
    }

    /**
     * Handle user ban
     *
     * @return void
     */
    public function banUser(): void
    {
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            header('Location: ' . BASE_URL . '/index.php?action=admin');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?action=adminDashboard');
            exit;
        }

        $userId = $_POST['id'] ?? null;
        $email = $_POST['email'] ?? null;

        if (!$userId || !$email) {
            header('Location: ' . BASE_URL . '/index.php?action=adminDashboard&error=missing_data');
            exit;
        }

        // Prevent self-ban
        if ($userId == $_SESSION['user_id']) {
            header('Location: ' . BASE_URL . '/index.php?action=adminDashboard&error=cannot_ban_self');
            exit;
        }

        try {
            // Get user info before deletion
            $user = $this->userRepository->findById((int)$userId);

            if (!$user) {
                header('Location: ' . BASE_URL . '/index.php?action=adminDashboard&error=user_not_found');
                exit;
            }

            // Insert into banned users table
            $this->insertBannedUser($userId, $email);

            // Delete from users table
            $this->userRepository->delete((int)$userId);

            header('Location: ' . BASE_URL . '/index.php?action=adminDashboard&success=banned');
        } catch (\Exception $e) {
            error_log("Ban user error: " . $e->getMessage());
            header('Location: ' . BASE_URL . '/index.php?action=adminDashboard&error=ban_failed');
        }
        exit;
    }

    /**
     * Insert banned user into utilisateurs_bannis table
     *
     * @param int|string $userId User ID
     * @param string $email User email
     * @return void
     */
    private function insertBannedUser($userId, string $email): void
    {
        // Get PDO connection from user repository using reflection
        $reflection = new \ReflectionClass($this->userRepository);
        $property = $reflection->getProperty('pdo');
        $property->setAccessible(true);
        $pdo = $property->getValue($this->userRepository);

        // Create table if not exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS utilisateurs_bannis (
            id INT PRIMARY KEY,
            mail VARCHAR(255) NOT NULL,
            date_de_ban DATE NOT NULL,
            ban_definitif TINYINT(1) DEFAULT 1
        )");

        // Insert banned user
        $stmt = $pdo->prepare(
            "INSERT INTO utilisateurs_bannis (id, mail, date_de_ban, ban_definitif) 
             VALUES (:id, :mail, :date_ban, 1)
             ON DUPLICATE KEY UPDATE date_de_ban = :date_ban"
        );

        $stmt->execute([
            'id' => $userId,
            'mail' => $email,
            'date_ban' => date('Y-m-d')
        ]);
    }

    /**
     * Handle admin logout
     *
     * @return void
     */
    public function logout(): void
    {
        unset($_SESSION['is_admin']);
        unset($_SESSION['user_id']);
        unset($_SESSION['nom']);
        unset($_SESSION['prenom']);
        unset($_SESSION['email']);

        header('Location: ' . BASE_URL . '/index.php?action=admin');
        exit;
    }

    /**
     * Get banned users from database
     *
     * @return array Array of banned users
     */
    private function getBannedUsers(): array
    {
        try {
            // Get PDO connection from user repository using reflection
            $reflection = new \ReflectionClass($this->userRepository);
            $property = $reflection->getProperty('pdo');
            $property->setAccessible(true);
            $pdo = $property->getValue($this->userRepository);

            // Check if table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'utilisateurs_bannis'");
            if ($stmt->rowCount() === 0) {
                return [];
            }

            // Get all banned users
            $stmt = $pdo->query("SELECT * FROM utilisateurs_bannis ORDER BY date_de_ban DESC");
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return array_map(function($row) {
                return [
                    'id' => $row['id'],
                    'mail' => $row['mail'],
                    'date_de_ban' => $row['date_de_ban']
                ];
            }, $data);
        } catch (\Exception $e) {
            error_log("Get banned users error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Unban a user (remove from banned users table)
     *
     * @param int|string $userEmail User email (used as identifier in banned table)
     * @return bool True if unbanned successfully
     */
    private function unbanUser($userEmail): bool
    {
        try {
            // Get PDO connection from user repository using reflection
            $reflection = new \ReflectionClass($this->userRepository);
            $property = $reflection->getProperty('pdo');
            $property->setAccessible(true);
            $pdo = $property->getValue($this->userRepository);

            // Delete from banned users table
            $stmt = $pdo->prepare("DELETE FROM utilisateurs_bannis WHERE mail = :mail");
            return $stmt->execute(['mail' => $userEmail]);
        } catch (\Exception $e) {
            error_log("Unban user error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get system statistics (AJAX endpoint)
     *
     * @return void
     */
    public function getStats(): void
    {
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        // Return stats as JSON
        header('Content-Type: application/json');
        echo json_encode([
            'total_users' => count($this->userRepository->findAll()),
            'pending_users' => count($this->pendingRepository->findAll())
        ]);
        exit;
    }

    /**
     * Load environment variables from .env file
     *
     * @return array Environment variables
     */
    private function loadEnv(): array
    {
        // Try config/.env outside the project root first (production)
        $envFile = __DIR__ . '/../../../../../config/.env';
        if (file_exists($envFile)) {
            return parse_ini_file($envFile);
        }

        // Fallback to config/.env inside the project root (development)
        $envFile = __DIR__ . '/../../../config/.env';
        if (file_exists($envFile)) {
            return parse_ini_file($envFile);
        }

        return [];
    }
}

