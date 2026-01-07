<?php

namespace Controllers\Admin;

require_once __DIR__ . '/../BaseController.php';

class AdminLogin extends \BaseController
{
    public function index()
    {
        // Si déjà connecté, redirection vers le dashboard
        if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
            header('Location: index.php?action=admin');
            exit;
        }
        $this->loadView('admin/admin-login', ['title' => 'Connexion Admin']);
    }

    public function login()
    {
        if (isset($_POST['ok'])) {
            $ID = $_POST['ID'];
            $mdp = $_POST['mdp'];

            // TODO: Récupérer les identifiants depuis le .env (ex: ADMIN_EMAIL, ADMIN_PASSWORD)
            // Pour l'instant, logique fictive ou simplifiée :
            $envPath = __DIR__ . '/../../../config/.env';
            if (file_exists($envPath)) {
                $env = parse_ini_file($envPath);
                $ADMIN_ID = $env['ADMIN_ID'] ?? 'admin'; // Valeur par défaut de secours
                $ADMIN_PASS = $env['ADMIN_PASS'] ?? 'admin';
            }

            // Vérification des identifiants
            if ($ID === $ADMIN_ID && $mdp === $ADMIN_PASS) {

                 $_SESSION['admin'] = true;
                 session_write_close();
                 header('Location: index.php?action=admin');
                 exit;

             } else {
                 $this->loadView('admin/admin-login', [
                     'title' => 'Connexion Admin',
                     'error_message' => 'Identifiants incorrects.'
                 ]);
                 return;
             }

        } else {
            $this->index();
        }
    }

    public function logout()
    {
        // Démarrer la session si nécessaire
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Détruire la session
        session_destroy();

        // Rediriger vers la page de connexion admin
        header('Location: index.php?action=adminLogin');
        exit;
    }
}
