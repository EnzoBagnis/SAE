<?php
require_once __DIR__ . '/../models/database.php';
require_once __DIR__ . '/../models/user.php';
require_once __DIR__ . '/../models/inscriptionEnAttente.php';
require_once __DIR__ . '/../models/emailService.php';

/**
 * Controller authController - Gestion de l'authentification avec CRUD
 */
class authController {
    private $userModel;
    private $inscriptionModel;
    private $emailService;

    public function __construct() {
        $this->userModel = new user();
        $this->inscriptionModel = new inscriptionEnAttente();
        $this->emailService = new emailService();
    }

    /**
     * CREATE - Inscription d'un nouvel utilisateur
     */
    public function inscription($nom, $prenom, $mail, $mdp) {
        $this->inscriptionModel->deleteExpired();

        if ($this->userModel->emailExists($mail)) {
            return ['success' => false, 'error' => 'email_existe'];
        }

        if ($this->inscriptionModel->emailExists($mail)) {
            return ['success' => false, 'error' => 'attente_existe'];
        }

        $code_verif = rand(100000, 999999);

        if ($this->inscriptionModel->create($nom, $prenom, $mail, $mdp, $code_verif)) {
            if ($this->emailService->envoyerCodeVerification($mail, $code_verif)) {
                return ['success' => true, 'mail' => $mail];
            }
            return ['success' => false, 'error' => 'envoi_mail'];
        }

        return ['success' => false, 'error' => 'creation_echouee'];
    }

    /**
     * CREATE - Validation du code et création de l'utilisateur
     */
    public function validerCode($mail, $code) {
        $inscription = $this->inscriptionModel->findByEmail($mail);

        if (!$inscription) {
            return ['success' => false, 'error' => 'inscription_expiree'];
        }

        if ($this->inscriptionModel->verifyCode($mail, $code)) {
            $this->userModel->create(
                $inscription['nom'],
                $inscription['prenom'],
                $inscription['mail'],
                $inscription['mdp'],
                $inscription['code_verif'],
                1
            );

            $this->inscriptionModel->delete($mail);
            $user = $this->userModel->findByEmail($mail);

            return ['success' => true, 'user' => $user];
        }

        return ['success' => false, 'error' => 'code_incorrect'];
    }

    /**
     * READ - Connexion d'un utilisateur
     */
    public function connexion($mail, $mdp) {
        $user = $this->userModel->verifyCredentials($mail, $mdp);

        if (!$user) {
            if (!$this->userModel->emailExists($mail)) {
                return ['success' => false, 'error' => 'email_existe_pas'];
            }
            return ['success' => false, 'error' => 'mdp_incorrect'];
        }

        return ['success' => true, 'user' => $user];
    }

    /**
     * UPDATE - Renvoyer un code de vérification
     */
    public function renvoyerCode($mail) {
        $inscription = $this->inscriptionModel->findByEmail($mail);

        if (!$inscription) {
            return ['success' => false, 'error' => 'inscription_expiree'];
        }

        $nouveau_code = rand(100000, 999999);

        if ($this->inscriptionModel->updateCode($mail, $nouveau_code)) {
            if ($this->emailService->envoyerCodeVerification($mail, $nouveau_code)) {
                return ['success' => true];
            }
            return ['success' => false, 'error' => 'envoi_mail_echec'];
        }

        return ['success' => false, 'error' => 'update_echec'];
    }

    /**
     * UPDATE - Demande de réinitialisation de mot de passe
     */
    public function demanderResetPassword($mail) {
        if (!$this->userModel->emailExists($mail)) {
            return ['success' => false, 'error' => 'email_inexistant'];
        }

        $token = bin2hex(random_bytes(32));
        $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

        if ($this->userModel->setResetToken($mail, $token, $expiration)) {
            if ($this->emailService->envoyerLienReset($mail, $token)) {
                return ['success' => true];
            }
            return ['success' => false, 'error' => 'envoi_mail'];
        }

        return ['success' => false, 'error' => 'update_echec'];
    }

    /**
     * UPDATE - Réinitialiser le mot de passe
     */
    public function resetPassword($token, $nouveauMdp) {
        $user = $this->userModel->findByResetToken($token);

        if (!$user) {
            return ['success' => false, 'error' => 'token_invalide'];
        }

        if (strtotime($user['reset_expiration']) < time()) {
            return ['success' => false, 'error' => 'token_expire'];
        }

        if ($this->userModel->updatePassword($user['id'], $nouveauMdp)) {
            $this->userModel->clearResetToken($user['id']);
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'update_echec'];
    }

    /**
     * Créer une session pour l'utilisateur
     */
    public function createSession($user) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['id'] = $user['id'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['prenom'] = $user['prenom'];
        $_SESSION['mail'] = $user['mail'];
    }
}

