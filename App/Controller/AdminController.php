<?php

namespace App\Controller;

use Core\Controller\AbstractController;
use App\Model\UserRepository;
use App\Model\EmailService;
use Core\Config\EnvLoader;
use Core\Config\DatabaseConnection;

/**
 * Admin Controller
 * Handles admin authentication and dashboard.
 *
 * Real DB tables:
 *   - utilisateurs (id, nom, prenom, mdp, mail, code_verif, reset_token, reset_expiration)
 *   - inscriptions_en_attente (id, nom, prenom, mdp, mail, verifie, code_verif, date_creation)
 *   - utilisateurs_bloques (id, mail, duree_ban, date_de_ban, ban_def, old_id, old_table)
 */
class AdminController extends AbstractController
{
    /** @var string Admin session key */
    private const SESSION_KEY = 'admin_logged_in';

    /**
     * Show admin login form
     *
     * @return void
     */
    public function loginForm(): void
    {
        if ($this->isAdminLoggedIn()) {
            $this->redirect(BASE_URL . '/admin/dashboard');
            return;
        }

        $this->renderView('admin/admin-login');
    }

    /**
     * Process admin login
     *
     * @return void
     */
    public function login(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/login');
            return;
        }

        $adminId  = $this->getPost('ID') ?? '';
        $password = $this->getPost('mdp') ?? '';

        $expectedId  = \Core\Config\EnvLoader::get('ADMIN_ID', 'admin');
        $expectedPwd = \Core\Config\EnvLoader::get('ADMIN_PASS', '');

        if ($adminId === $expectedId && $password === $expectedPwd) {
            session_start();
            $_SESSION[self::SESSION_KEY] = true;
            $this->redirect(BASE_URL . '/admin/dashboard');
        } else {
            $this->renderView('admin/admin-login', [
                'error_message' => 'Identifiants incorrects.',
            ]);
        }
    }

    /**
     * Show admin dashboard
     *
     * @return void
     */
    public function dashboard(): void
    {
        if (!$this->isAdminLoggedIn()) {
            $this->redirect(BASE_URL . '/admin/login');
            return;
        }

        $pdo = DatabaseConnection::getInstance()->getConnection();

        // Utilisateurs validés (dans la table utilisateurs)
        $verifiedUsers = $pdo->query(
            "SELECT id, nom, prenom, mail, 1 AS verifie
             FROM utilisateurs ORDER BY nom ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Utilisateurs en attente de validation (inscriptions_en_attente, verifie = 1 = email vérifié mais pas encore accepté)
        $pendingUsers = $pdo->query(
            "SELECT id, nom, prenom, mail, verifie
             FROM inscriptions_en_attente WHERE verifie = 1 ORDER BY date_creation DESC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Utilisateurs bloqués
        $blockedUsers = $pdo->query(
            "SELECT ub.id, ub.mail, ub.duree_ban, ub.date_de_ban, ub.ban_def
             FROM utilisateurs_bloques ub ORDER BY ub.date_de_ban DESC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $this->renderView('admin/admin-dashboard', [
            'verifiedUsers' => $verifiedUsers,
            'pendingUsers'  => $pendingUsers,
            'blockedUsers'  => $blockedUsers,
        ]);
    }

    /**
     * Logout admin
     *
     * @return void
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION[self::SESSION_KEY]);
        session_destroy();
        $this->redirect(BASE_URL . '/admin/login');
    }

    /**
     * Check if admin is logged in
     *
     * @return bool
     */
    private function isAdminLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION[self::SESSION_KEY]);
    }

    /**
     * Delete a user
     *
     * @return void
     */
    public function deleteUser(): void
    {
        if (!$this->isAdminLoggedIn()) {
            $this->redirect(BASE_URL . '/admin/login');
            return;
        }

        $id = $this->getQuery('id') ?? '';

        if (!empty($id)) {
            $pdo = DatabaseConnection::getInstance()->getConnection();
            $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
            $stmt->execute(['id' => (int) $id]);
        }

        $this->redirect(BASE_URL . '/admin/dashboard');
    }

    /**
     * Validate a pending user: move from inscriptions_en_attente to utilisateurs.
     *
     * @return void
     */
    public function validateUser(): void
    {
        if (!$this->isAdminLoggedIn()) {
            $this->redirect(BASE_URL . '/admin/login');
            return;
        }

        $id = $this->getQuery('id') ?? '';

        if (!empty($id)) {
            $pdo = DatabaseConnection::getInstance()->getConnection();

            // Get the pending registration
            $stmt = $pdo->prepare("SELECT * FROM inscriptions_en_attente WHERE id = :id");
            $stmt->execute(['id' => (int) $id]);
            $pending = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($pending) {
                // Insert into utilisateurs
                $insert = $pdo->prepare(
                    "INSERT INTO utilisateurs (nom, prenom, mdp, mail, code_verif)
                     VALUES (:nom, :prenom, :mdp, :mail, :code_verif)"
                );
                $insert->execute([
                    'nom'        => $pending['nom'],
                    'prenom'     => $pending['prenom'],
                    'mdp'        => $pending['mdp'],
                    'mail'       => $pending['mail'],
                    'code_verif' => $pending['code_verif'],
                ]);

                // Delete from inscriptions_en_attente
                $delete = $pdo->prepare("DELETE FROM inscriptions_en_attente WHERE id = :id");
                $delete->execute(['id' => (int) $id]);
            }
        }

        $this->redirect(BASE_URL . '/admin/dashboard');
    }

    /**
     * Edit a user
     *
     * @return void
     */
    public function editUser(): void
    {
        if (!$this->isAdminLoggedIn()) {
            $this->redirect(BASE_URL . '/admin/login');
            return;
        }

        $id     = $this->getPost('id') ?? '';
        $nom    = $this->getPost('nom') ?? '';
        $prenom = $this->getPost('prenom') ?? '';

        if (!empty($id)) {
            $pdo  = DatabaseConnection::getInstance()->getConnection();
            $stmt = $pdo->prepare(
                "UPDATE utilisateurs SET nom = :nom, prenom = :prenom WHERE id = :id"
            );
            $stmt->execute(['nom' => $nom, 'prenom' => $prenom, 'id' => (int) $id]);
        }

        $this->redirect(BASE_URL . '/admin/dashboard');
    }

    /**
     * Ban a user: move from utilisateurs to utilisateurs_bloques.
     *
     * @return void
     */
    public function banUser(): void
    {
        if (!$this->isAdminLoggedIn()) {
            $this->redirect(BASE_URL . '/admin/login');
            return;
        }

        $id = $this->getPost('id') ?? '';

        if (!empty($id)) {
            $pdo = DatabaseConnection::getInstance()->getConnection();

            // Get user info
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
            $stmt->execute(['id' => (int) $id]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user) {
                // Insert into utilisateurs_bloques
                $insert = $pdo->prepare(
                    "INSERT INTO utilisateurs_bloques (mail, duree_ban, ban_def, old_id, old_table)
                     VALUES (:mail, 0, 1, :old_id, 'utilisateurs')"
                );
                $insert->execute([
                    'mail'   => $user['mail'],
                    'old_id' => $user['id'],
                ]);

                // Delete from utilisateurs
                $delete = $pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
                $delete->execute(['id' => (int) $id]);
            }
        }

        $this->redirect(BASE_URL . '/admin/dashboard');
    }

    /**
     * Unban a user: remove from utilisateurs_bloques and restore to utilisateurs if possible.
     *
     * @return void
     */
    public function unbanUser(): void
    {
        if (!$this->isAdminLoggedIn()) {
            $this->redirect(BASE_URL . '/admin/login');
            return;
        }

        $id = $this->getQuery('id') ?? '';

        if (!empty($id)) {
            $pdo = DatabaseConnection::getInstance()->getConnection();
            $stmt = $pdo->prepare("DELETE FROM utilisateurs_bloques WHERE id = :id");
            $stmt->execute(['id' => (int) $id]);
        }

        $this->redirect(BASE_URL . '/admin/dashboard');
    }

    /**
     * Switch user (AJAX - no-op placeholder for tab state)
     *
     * @return void
     */
    public function switchUser(): void
    {
        if (!$this->isAdminLoggedIn()) {
            http_response_code(401);
            return;
        }
        http_response_code(200);
    }
}
