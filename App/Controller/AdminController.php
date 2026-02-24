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

        // Utilisateurs vérifiés (account_status = 1)
        $verifiedUsers = $pdo->query(
            "SELECT mail, name, surname FROM teachers WHERE account_status = 1 ORDER BY surname ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Utilisateurs en attente de vérification (account_status = 0)
        $pendingUsers = $pdo->query(
            "SELECT mail, name, surname, account_status FROM teachers WHERE account_status = 0 ORDER BY surname ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Utilisateurs bloqués (account_status = 2)
        $blockedUsers = $pdo->query(
            "SELECT mail, name, surname FROM teachers WHERE account_status = 2 ORDER BY surname ASC"
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

        $id = $this->getQuery('id') ?? '';

        if (!empty($id)) {
            $pdo = DatabaseConnection::getInstance()->getConnection();
            $stmt = $pdo->prepare("DELETE FROM teachers WHERE mail = :mail");
            $stmt->execute(['mail' => $id]);
        }

        $this->redirect('/admin/dashboard');
    }

    /**
     * Validate a pending user (set account_status to 1)
     *
     * @return void
     */
    public function validateUser(): void
    {
        if (!$this->isAdminLoggedIn()) {
            $this->redirect('/admin/login');
            return;
        }

        $mail = $this->getQuery('id') ?? '';

        if (!empty($mail)) {
            $pdo = DatabaseConnection::getInstance()->getConnection();

            $stmt = $pdo->prepare("UPDATE teachers SET account_status = 1 WHERE mail = :mail AND account_status = 0");
            $stmt->execute(['mail' => $mail]);
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

        $mail   = $this->getPost('id') ?? '';
        $nom    = $this->getPost('nom') ?? '';
        $prenom = $this->getPost('prenom') ?? '';

        if (!empty($mail)) {
            $pdo  = DatabaseConnection::getInstance()->getConnection();
            $stmt = $pdo->prepare("
                UPDATE teachers SET surname = :surname, name = :name WHERE mail = :mail
            ");
            $stmt->execute(['surname' => $nom, 'name' => $prenom, 'mail' => $mail]);
        }

        $this->redirect('/admin/dashboard');
    }

    /**
     * Ban a user (set account_status to 2)
     *
     * @return void
     */
    public function banUser(): void
    {
        if (!$this->isAdminLoggedIn()) {
            $this->redirect('/admin/login');
            return;
        }

        $id = $this->getPost('id') ?? '';

        if (!empty($id)) {
            $pdo = DatabaseConnection::getInstance()->getConnection();
            $stmt = $pdo->prepare("UPDATE teachers SET account_status = 2 WHERE mail = :mail");
            $stmt->execute(['mail' => $id]);
        }

        $this->redirect('/admin/dashboard');
    }

    /**
     * Unban a user (set account_status back to 1)
     *
     * @return void
     */
    public function unbanUser(): void
    {
        if (!$this->isAdminLoggedIn()) {
            $this->redirect('/admin/login');
            return;
        }

        $mail = $this->getQuery('id') ?? '';

        if (!empty($mail)) {
            $pdo = DatabaseConnection::getInstance()->getConnection();
            $stmt = $pdo->prepare("UPDATE teachers SET account_status = 1 WHERE mail = :mail AND account_status = 2");
            $stmt->execute(['mail' => $mail]);
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
