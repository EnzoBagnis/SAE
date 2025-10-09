<?php
require_once __DIR__ . '/../models/database.php';
require_once __DIR__ . '/AuthController.php';

/**
 * Form Processing Script
 * Handles all authentication-related form submissions
 */

// Initialize authentication controller
$authController = new AuthController();

/**
 * Login helper function
 * Creates session and redirects to dashboard
 * @param array $user User data
 */
function loginUser($user) {
    global $authController;
    $authController->createSession($user);
    header("Location: ../views/dashboard.php");
    exit;
}

// ========== REGISTRATION - CREATE with PDO ==========
if(isset($_POST['ok']) && !isset($_POST['code'])){
    extract($_POST);
    
    // CREATE - Create pending registration
    $result = $authController->register($nom, $prenom, $mail, $mdp);

    if($result['success']) {
        session_start();
        $_SESSION['mail'] = $result['email'];
        header("Location: ../views/verificationMail.php?succes=inscription");
    } else {
        header("Location: ../views/formulaire.php?error=" . $result['error']);
    }
    exit;
}

// ========== CODE VALIDATION - CREATE user + DELETE pending registration with PDO ==========
if(isset($_POST['code'])) {
    $code = $_POST['code'];
    session_start();
    $email = $_SESSION['mail'];

    // CREATE user account + DELETE pending registration
    $result = $authController->validateCode($email, $code);

    if($result['success']) {
        loginUser($result['user']);
    } else {
        header("Location: ../views/verificationMail.php?erreur=" . $result['error']);
    }
    exit;
}

// ========== LOGIN - READ with PDO ==========
if(isset($_POST['connexion'])){
    extract($_POST);
    session_start();
    
    // READ - Verify user credentials
    $result = $authController->login($mail, $mdp);

    if($result['success']) {
        loginUser($result['user']);
    } else {
        header("Location: ../views/connexion.php?error=" . $result['error']);
    }
    exit;
}

// ========== FORGOT PASSWORD - UPDATE with PDO ==========
if(isset($_POST['forgot_password'])) {
    $email = trim($_POST['mail']);

    // UPDATE - Create reset token
    $result = $authController->requestPasswordReset($email);

    if($result['success']) {
        header("Location: ../views/connexion.php?succes=reset_envoye");
    } else {
        header("Location: ../views/forgotPassword.php?error=" . $result['error']);
    }
    exit;
}

// ========== RESEND CODE - UPDATE with PDO ==========
if(isset($_POST['renvoyer_code'])) {
    session_start();
    
    if(!isset($_SESSION['mail'])) {
        header("Location: ../views/formulaire.php?erreur=session_expiree");
        exit;
    }
    
    $email = $_SESSION['mail'];

    // UPDATE - Update verification code
    $result = $authController->resendCode($email);

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
