<?php

namespace Controllers\Admin;

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../Auth/AuthController.php';

/**
 * LoginController - Handles login display and authentication
 */
class AdminDashboardController extends \BaseController
{
    private $userModel;

    public function __construct()
    {
        // Vérifier si l'utilisateur est connecté en tant qu'admin
        if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
            header('Location: index.php?action=adminLogin');
            exit;
        }

        $this->userModel = new \User();
    }

    /**
     * Show login page
     */
    public function index()
    {
        $verifiedUsers = $this->userModel->showVerifiedUser();
        $pendingUsers = $this->userModel->showPendingUser();
        $blockedUsers = $this->userModel->showBlockedUser();

        $data = [
            'verifiedUsers' => $verifiedUsers,
            'pendingUsers' => $pendingUsers,
            'blockedUsers' => $blockedUsers
        ];


        $this->loadView('admin/admin-dashboard', $data);
    }

    /**
     * Process login form
     */
    public function showVerifiedUsers()
    {


        $verifiedUsers = $this->userModel->showVerifiedUser();
        $test = "test";

        $this->loadView('admin/admin-dashboard', ['verifiedUsers' => $verifiedUsers]);
    }

    public function showPendingUsers()
    {


        $pendingUsers = $this->userModel->showPendingUser();
        $test = "test";

        $this->loadView('admin/admin-dashboard', ['pendingUsers' => $pendingUsers]);
    }

    public function showBlockedUsers()
    {


        $blockedUsers = $this->userModel->showBlockedUser();
        $test = "test";

        $this->loadView('admin/admin-dashboard', ['blockedUsers' => $blockedUsers]);
    }

    public function deleteUser()
    {
        // Implementation for deleting a user
        $table = $_GET['table'];
        $id = $_GET['id'];
        $success = $this->userModel->delete($table, $id);
        if ($success) {
            header('Location: index.php?action=admin');
        }
        else {
            $this->loadView('admin/admin-dashboard');
        }
    }

    public function editUser()
    {
        $id = $_POST['id'] ?? '';
        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
        $email = $_POST['email'] ?? '';

        $success = $this->userModel->update($id, $nom, $prenom, $email);
        if ($success) {
            header('Location: index.php?action=admin');
            exit;
        }
        else {
            header('Location: /index.php?action=login');
            exit;
        }

    }

    public function validateUser()
    {
        $id = $_GET['id'];
        $success = $this->userModel->switchUser($id);
        if ($success) {
            header('Location: index.php?action=admin');
        }
        else {
            $this->loadView('admin/admin-dashboard');
        }

    }

    public function banUser()
    {
        $table = $_GET['table'];
        $id = $_POST['id'] ?? '';
        $email = $_POST['email'] ?? '';
        $duree_de_ban = $_POST['duree_de_ban'] ?? '';
        $ban_def = $_POST['ban_def'] ?? '';

        if ($table == 'B') {$this->updateBanUser($id, $email, $duree_de_ban, $ban_def);}
        else {
            $this->userModel->delete($id, $email);
            $this->firstBanUser($table, $id, $email, $duree_de_ban, $ban_def);
        }
    }

    public function updateBanUser($id, $email, $duree_de_ban, $ban_def)
    {

        $success = $this->userModel->updateBan($id, $email, $duree_de_ban, $ban_def);
        if ($success) {
            header('Location: index.php?action=admin');
            exit;
        }
        else {
            header('Location: /index.php?action=login');
            exit;
        }

    }

    public function firstBanUser($table, $id, $mail, $duree_de_ban, $ban_def)
    {
        $this->userModel->createBanUser($mail, $duree_de_ban, $ban_def, $table, $id);

        header('Location: index.php?action=adminDeleteUser&table=' . $table . '&id=' . $id);

    }


    /**
     * Get error message from error code
     */
    private function getErrorMessage($errorCode)
    {
        $messages = [
            'email_not_found' => 'Cet email n\'existe pas !',
            'password_incorrect' => 'Mot de passe incorrect',
            'empty_fields' => 'Tous les champs sont requis',
            'token_invalide' => 'Lien de réinitialisation invalide',
            'token_expire' => 'Lien de réinitialisation expiré'
        ];

        return $messages[$errorCode] ?? 'Une erreur est survenue';
    }

    /**
     * Get success message from success code
     */
    private function getSuccessMessage($successCode)
    {
        $messages = [
            'reset_envoye' => 'Un email de réinitialisation a été envoyé !',
            'mdp_reinitialise' => 'Votre mot de passe a été réinitialisé avec succès !'
        ];

        return $messages[$successCode] ?? '';
    }
}
