<?php

namespace App\Controller;

use Core\Controller\AbstractController;
use App\Model\UserRepository;
use App\Model\EmailService;
use Core\Config\EnvLoader;
use Core\Config\DatabaseConnection;

/**
 * Admin Controller
 * Handles admin authentication and dashboard
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
            $this->redirect('/admin/dashboard');
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
            $this->redirect('/admin/dashboard');
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
            $this->redirect('/admin/login');
            return;
        }

        $pdo = DatabaseConnection::getInstance()->getConnection();

        $verifiedUsers = $pdo->query(
            "SELECT id, nom, prenom, mail FROM utilisateurs ORDER BY nom ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $pendingUsers = $pdo->query(
            "SELECT id, nom, prenom, mail, verifie FROM inscription_en_attente ORDER BY id ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $blockedUsers = $pdo->query(
            "SELECT mail, date_de_ban, ban_def, duree_ban FROM liste_noire ORDER BY date_de_ban DESC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Add index as pseudo-id for blocked users display
        foreach ($blockedUsers as $i => &$bu) {
            $bu['id'] = $i + 1;
        }
        unset($bu);

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
        $this->redirect('/admin/login');
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
            $this->redirect('/admin/login');
            return;
        }

        $table = $this->getQuery('table') ?? 'V';
        $id    = $this->getQuery('id') ?? '';

        if (!empty($id)) {
            $pdo = DatabaseConnection::getInstance()->getConnection();
            if ($table === 'B') {
                $stmt = $pdo->prepare("DELETE FROM liste_noire WHERE mail = :id");
            } elseif ($table === 'P') {
                $stmt = $pdo->prepare("DELETE FROM inscription_en_attente WHERE id = :id");
            } else {
                $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
            }
            $stmt->execute(['id' => $id]);
        }

        $this->redirect('/admin/dashboard');
    }

    /**
     * Validate a pending user
     *
     * @return void
     */
    public function validateUser(): void
    {
        if (!$this->isAdminLoggedIn()) {
            $this->redirect('/admin/login');
            return;
        }

        $id = $this->getQuery('id') ?? '';

        if (!empty($id)) {
            $pdo = DatabaseConnection::getInstance()->getConnection();

            // Fetch pending user
            $stmt = $pdo->prepare("SELECT * FROM inscription_en_attente WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $pending = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($pending) {
                // Move to verified users
                $ins = $pdo->prepare("
                    INSERT INTO utilisateurs (nom, prenom, mail, mdp, code_verif, mail_verifie, date_creation)
                    VALUES (:nom, :prenom, :mail, :mdp, :code_verif, 1, NOW())
                ");
                $ins->execute([
                    'nom'        => $pending['nom'],
                    'prenom'     => $pending['prenom'],
                    'mail'       => $pending['mail'],
                    'mdp'        => $pending['mdp'],
                    'code_verif' => $pending['code_verif'] ?? null,
                ]);

                // Delete from pending
                $del = $pdo->prepare("DELETE FROM inscription_en_attente WHERE id = :id");
                $del->execute(['id' => $id]);

                // Send notification
                $emailService = new EmailService();
                $emailService->sendVerificationCode($pending['mail'], $pending['prenom'] ?? '', '');
            }
        }

        $this->redirect('/admin/dashboard');
    }

    /**
     * Edit a user
     *
     * @return void
     */
    public function editUser(): void
    {
        if (!$this->isAdminLoggedIn()) {
            $this->redirect('/admin/login');
            return;
        }

        $id     = $this->getPost('id') ?? '';
        $nom    = $this->getPost('nom') ?? '';
        $prenom = $this->getPost('prenom') ?? '';
        $email  = $this->getPost('email') ?? '';

        if (!empty($id)) {
            $pdo  = DatabaseConnection::getInstance()->getConnection();
            $stmt = $pdo->prepare("
                UPDATE utilisateurs SET nom = :nom, prenom = :prenom, mail = :mail WHERE id = :id
            ");
            $stmt->execute(['nom' => $nom, 'prenom' => $prenom, 'mail' => $email, 'id' => $id]);
        }

        $this->redirect('/admin/dashboard');
    }

    /**
     * Ban a user
     *
     * @return void
     */
    public function banUser(): void
    {
        if (!$this->isAdminLoggedIn()) {
            $this->redirect('/admin/login');
            return;
        }

        $table   = $this->getQuery('table') ?? 'V';
        $id      = $this->getPost('id') ?? '';
        $email   = $this->getPost('email') ?? '';
        $ban_def = $this->getPost('ban_def') ?? 1;

        if (!empty($email)) {
            $pdo = DatabaseConnection::getInstance()->getConnection();

            // Insert into blacklist
            $stmt = $pdo->prepare("
                INSERT INTO liste_noire (mail, date_de_ban, ban_def)
                VALUES (:mail, NOW(), :ban_def)
                ON DUPLICATE KEY UPDATE date_de_ban = NOW(), ban_def = :ban_def
            ");
            $stmt->execute(['mail' => $email, 'ban_def' => $ban_def]);

            // Remove from original table
            if ($table === 'V') {
                $del = $pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
                $del->execute(['id' => $id]);
            } elseif ($table === 'P') {
                $del = $pdo->prepare("DELETE FROM inscription_en_attente WHERE id = :id");
                $del->execute(['id' => $id]);
            }
        }

        $this->redirect('/admin/dashboard');
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
