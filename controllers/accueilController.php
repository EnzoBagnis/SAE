<?php
require_once __DIR__ . '/baseController.php';

class accueilController extends baseController {

    public function index() {
        session_start();

        // Si l'utilisateur est connecté, rediriger vers le dashboard
        if (isset($_SESSION['id'])) {
            header('Location: views/dashboard.php');
            exit;
        } else {
            // Sinon, rediriger vers la page de connexion
            header('Location: views/connexion.php');
            exit;
        }
    }
}