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


        $this->loadView('admin/admin-dashboard');
    }

    /**
     * Process login form
     */
    public function showVerifiedUsers()
    {


        $verifiedUsers = $this->userModel->showUser();
        $test = "test";

        $this->loadView('admin/admin-dashboard', ['verifiedUsers' => $verifiedUsers]);
    }

    public function deleteUser()
    {
        // Implementation for deleting a user
        $id = $_GET['id'];
        $success = $this->userModel->delete($id);
        if ($success) {
            $this->showVerifiedUsers();
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
            header('Location: index.php?action=adminSVU');
            exit;
        }
        else {
            header('Location: /index.php?action=login');
            exit;
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
