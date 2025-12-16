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
        $this->userModel = new \User();
    }

    /**
     * Show login page
     */
    public function index()
    {
        $verifiedUsers = $this->userModel->showVerifiedUser();
        $pendingUsers = $this->userModel->showPendingUser();

        $data = [
            'verifiedUsers' => $verifiedUsers,
            'pendingUsers' => $pendingUsers
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
