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
            "SELECT mail, name, surname FROM teachers ORDER BY surname ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $pendingUsers = $pdo->query(
            "SELECT id, nom, prenom, mail, mail_verifie FROM pending_registrations ORDER BY id ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $this->renderView('admin/admin-dashboard', [
            'verifiedUsers' => $verifiedUsers,
            'pendingUsers'  => $pendingUsers,
            'blockedUsers'  => [],
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
            if ($table === 'P') {
                $stmt = $pdo->prepare("DELETE FROM pending_registrations WHERE id = :id");
            } else {
                $stmt = $pdo->prepare("DELETE FROM teachers WHERE mail = :id");
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
            $stmt = $pdo->prepare("SELECT * FROM pending_registrations WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $pending = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($pending) {
                // Move to verified users
                $ins = $pdo->prepare("
                    INSERT INTO teachers (mail, name, surname, password, code_verif, account_status, reset_token)
                    VALUES (:mail, :name, :surname, :password, :code_verif, 1, '')
                ");
                $ins->execute([
                    'mail'       => $pending['mail'],
                    'name'       => $pending['prenom'],
                    'surname'    => $pending['nom'],
                    'password'   => $pending['mdp'],
                    'code_verif' => $pending['code_verif'] ?? null,
                ]);

                // Delete from pending
                $del = $pdo->prepare("DELETE FROM pending_registrations WHERE id = :id");
                $del->execute(['id' => $id]);
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

        // La table liste_noire n'existe pas dans le schéma actuel.
        // On se contente de supprimer l'utilisateur de sa table d'origine.
        $table = $this->getQuery('table') ?? 'V';
        $id    = $this->getPost('id') ?? '';

        if (!empty($id)) {
            $pdo = DatabaseConnection::getInstance()->getConnection();
            if ($table === 'P') {
                $del = $pdo->prepare("DELETE FROM pending_registrations WHERE id = :id");
            } else {
                $del = $pdo->prepare("DELETE FROM teachers WHERE mail = :id");
            }
            $del->execute(['id' => $id]);
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
