<?php
namespace Controllers\User;

require_once __DIR__ . '/../BaseController.php';

/**
 * DashboardController - Handles user dashboard
 */
class DashboardController extends \BaseController {

    /**
     * Show dashboard page
     */
    public function index() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is authenticated
//        if (!isset($_SESSION['id'])) {
//            header('Location: /index.php?action=login');
//            exit;
//        }

        // Prepare data for the view
        $data = [
            'title' => 'StudTraj - Tableau de bord',
            'user_firstname' => $_SESSION['prenom'] ?? 'Utilisateur',
            'user_lastname' => $_SESSION['nom'] ?? '',
            'user_email' => $_SESSION['mail'] ?? ''
        ];

        $this->loadView('user/dashboard', $data);
    }
}