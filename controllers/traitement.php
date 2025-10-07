<?php
require_once __DIR__ . '/../models/database.php';
require_once __DIR__ . '/authController.php';

// Initialiser le controller
$authController = new authController();

// Fonction de connexion simplifiée
function connexion($user) {
    global $authController;
    $authController->createSession($user);
    header("Location: ../views/accueil.php");
    exit;
}

// INSCRIPTION - CREATE avec PDO
if(isset($_POST['ok']) && !isset($_POST['code'])){
    extract($_POST);
    
    // CREATE - Créer une inscription en attente
    $result = $authController->inscription($nom, $prenom, $mail, $mdp);
    
    if($result['success']) {
        session_start();
        $_SESSION['mail'] = $result['mail'];
        header("Location: ../views/verificationMail.php?succes=inscription");
    } else {
        header("Location: ../views/formulaire.php?erreur=" . $result['error']);
    }
    exit;
}

// VALIDATION CODE - CREATE utilisateur + DELETE inscription en attente avec PDO
if(isset($_POST['code'])) {
    $code = $_POST['code'];
    session_start();
    $mail = $_SESSION['mail'];
    
    // CREATE utilisateur + DELETE inscription
    $result = $authController->validerCode($mail, $code);
    
    if($result['success']) {
        connexion($result['user']);
    } else {
        header("Location: ../views/verificationMail.php?erreur=" . $result['error']);
    }
    exit;
}

// CONNEXION - READ avec PDO
if(isset($_POST['connexion'])){
    extract($_POST);
    session_start();
    
    // READ - Vérifier les credentials
    $result = $authController->connexion($mail, $mdp);
    
    if($result['success']) {
        connexion($result['user']);
    } else {
        header("Location: ../views/connexion.php?erreur=" . $result['error']);
    }
    exit;
}

// FORGOT PASSWORD - UPDATE avec PDO
if(isset($_POST['forgot_password'])) {
    $mail = trim($_POST['mail']);
    
    // UPDATE - Créer un token de reset
    $result = $authController->demanderResetPassword($mail);
    
    if($result['success']) {
        header("Location: ../views/connexion.php?succes=reset_envoye");
    } else {
        header("Location: ../views/forgotPassword.php?erreur=" . $result['error']);
    }
    exit;
}

// RENVOYER CODE - UPDATE avec PDO
if(isset($_POST['renvoyer_code'])) {
    session_start();
    
    if(!isset($_SESSION['mail'])) {
        header("Location: ../views/formulaire.php?erreur=session_expiree");
        exit;
    }
    
    $mail = $_SESSION['mail'];
    
    // UPDATE - Mettre à jour le code de vérification
    $result = $authController->renvoyerCode($mail);
    
    if($result['success']) {
        header("Location: ../views/verificationMail.php?succes=code_renvoye");
    } else {
        header("Location: ../views/verificationMail.php?erreur=" . $result['error']);
    }
    exit;
}

// RESET PASSWORD - UPDATE avec PDO
if(isset($_POST['reset_password'])) {
    extract($_POST);
    
    // UPDATE - Réinitialiser le mot de passe
    $result = $authController->resetPassword($token, $nouveau_mdp);
    
    if($result['success']) {
        header("Location: ../views/connexion.php?succes=mdp_reinitialise");
    } else {
        header("Location: ../views/connexion.php?erreur=" . $result['error']);
    }
    exit;
}
?>
