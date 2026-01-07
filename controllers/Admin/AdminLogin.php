<?php

namespace Controllers\Admin;

require_once __DIR__ . '/../BaseController.php';

class AdminLogin extends \BaseController
{
    public function index()
    {
        $this->loadView('admin/admin-login', ['title' => 'Connexion Admin']);
    }

    public function login()
    {
        if (isset($_POST['ok'])) {
            $ID = $_POST['ID'];
            $mdp = $_POST['mdp'];

            // TODO: Récupérer les identifiants depuis le .env (ex: ADMIN_EMAIL, ADMIN_PASSWORD)
            // Pour l'instant, logique fictive ou simplifiée :
            $env = parse_ini_file(__DIR__ . '/../../config/.env');

            $ADMIN_ID= $env['ADMIN_ID'];
            $ADMIN_PASS = $env['ADMIN_PASS'];

            // Simulation de succès pour le développement
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
             }

            // Pour l'instant, on recharge juste la vue avec un message
             $this->loadView('admin/admin-login', [
                'title' => 'Connexion Admin',
                'error_message' => 'Logique de connexion à implémenter via .env'
            ]);

        } else {
            $this->index();
        }
    }
}
