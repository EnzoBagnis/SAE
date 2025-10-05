<?php
require_once '../models/Database.php';

require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';
require '../phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Connexion unique à la base de données
$bdd = Database::getConnection();



function envoyerEmail($destinataire, $code_verif) {
    $mail = new PHPMailer(true);
    $env = parse_ini_file(__DIR__ . '/../../config/.env');

    try {
        $mail->isSMTP();
        $mail->Host       = $env['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $env['MAIL_USERNAME'];
        $mail->Password   = $env['MAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $env['MAIL_PORT'];

        // ParamÃ¨tres du mail
        $mail->setFrom($env['MAIL_USERNAME'], $env['MAIL_FROM_NAME']);
        $mail->addAddress($destinataire);

        // Contenu du mail simple
        $mail->Subject = 'Code de verification - StudTraj';
        $mail->Body    = "Bonjour,\n\nVotre code de vérification est : $code_verif\n\nCordialement,\nL'équipe StudTraj";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log l'erreur (optionnel)
        error_log("Erreur envoi mail : " . $mail->ErrorInfo);
        return false;
    }
}

function connexion($id, $bdd)
{
    $conex = $bdd->prepare("SELECT * FROM utilisateurs WHERE id = :id");
    $conex->execute(['id' => $id]);
    $session = $conex->fetch(PDO::FETCH_ASSOC);

    $_SESSION['id'] = $id;
    $nom = $session['nom'];
    $_SESSION['nom'] = $nom;
    $prenom = $session['prenom'];
    $_SESSION['prenom'] = $prenom;
    header("Location: ../views/accueil.php");

}

if(isset($_POST['ok']) && !isset($_POST['code'])){
    extract($_POST);

    $checkEmail = $bdd->prepare("SELECT COUNT(*) FROM utilisateurs WHERE mail = :mail");
    $checkEmail->execute(['mail' => $mail]);
    if ($checkEmail->fetchColumn() > 0) {
        header("Location: ../views/formulaire.php?erreur=email_existe");
        exit;
    }

    $deleteExpired = $bdd->prepare("DELETE FROM inscriptions_en_attente WHERE date_creation < DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $deleteExpired->execute();

    // Vérifier si déjà en attente
    $checkAttente = $bdd->prepare("SELECT COUNT(*) FROM inscriptions_en_attente WHERE mail = :mail");
    $checkAttente->execute(['mail' => $mail]);
    if ($checkAttente->fetchColumn() > 0) {
        header("Location: ../views/formulaire.php?erreur=attente_existe");
        exit;
    }

    $code_verif = rand(100000, 999999);
    $hashedPassword = password_hash($mdp, PASSWORD_DEFAULT);

    // Insérer dans inscription_en_attente
    $requete = $bdd->prepare("INSERT INTO inscriptions_en_attente (nom, prenom, mdp, mail, code_verif, date_creation) VALUES (:nom, :prenom, :mdp, :mail, :code_verif, NOW())");
    try{
        $requete->execute(array(
            'nom' => $nom,
            'prenom' => $prenom,
            'mdp' => $hashedPassword,
            'mail' => $mail,
            'code_verif' => $code_verif
        ));

        session_start();
        $_SESSION['mail'] = $mail;

        if (envoyerEmail($mail, $code_verif)) {
            header("Location: ../views/verificationMail.php?succes=inscription");
        } else {
            header("Location: ../views/formulaire.php?erreur=envoi_mail");
        }
        exit;

    } catch (PDOException $e) {
        echo "Erreur lors de l'insertion : " . $e->getMessage();
        exit;
    }
}


if(isset($_POST['code'])) {
    $code = $_POST['code'];
    session_start();
    $mail = $_SESSION['mail'];

    // Récupérer l'inscription en attente
    $verif = $bdd->prepare("SELECT * FROM inscriptions_en_attente WHERE mail = :mail");
    $verif->execute(['mail' => $mail]);
    $userAttente = $verif->fetch(PDO::FETCH_ASSOC);

    if(!$userAttente) {
        header("Location: ../views/verificationMail.php?erreur=inscription_expiree");
        exit;
    }

    if($code == $userAttente['code_verif']) {
        // Insérer dans utilisateurs
        $insertUser = $bdd->prepare("INSERT INTO utilisateurs (nom, prenom, mdp, mail, code_verif, verifie) VALUES (:nom, :prenom, :mdp, :mail, :code_verif, 1)");
        $insertUser->execute([
            'nom' => $userAttente['nom'],
            'prenom' => $userAttente['prenom'],
            'mdp' => $userAttente['mdp'],
            'mail' => $userAttente['mail'],
            'code_verif' => $userAttente['code_verif']
        ]);

        // Supprimer de inscription_en_attente
        $deleteAttente = $bdd->prepare("DELETE FROM inscriptions_en_attente WHERE mail = :mail");
        $deleteAttente->execute(['mail' => $mail]);

        // Récupérer l'ID de l'utilisateur créé
        $rid = $bdd->prepare("SELECT id FROM utilisateurs WHERE mail = :mail");
        $rid->execute(['mail' => $mail]);
        $id = $rid->fetchColumn();

        connexion($id, $bdd);
        exit;
    } else {
        header("Location: ../views/verificationMail.php?erreur=code_incorrect");
        exit;
    }
}



if(isset($_POST['connexion'])){
    extract($_POST);
    session_start();

    // Utilisation de la classe Database au lieu de refaire la connexion
    $check = $bdd->prepare("SELECT mail, mdp, id FROM utilisateurs WHERE mail = :mail");
    $check->execute(['mail' => $mail]);
    $ligne = $check->fetch(PDO::FETCH_ASSOC);
    $id = $ligne['id'];

    //Verification mail existe
    if ($check->rowCount() > 0) {
    }
    else {
        // Rediriger vers le formulaire avec erreur
        header("Location: ../views/connexion.php?erreur=email_existe_pas");
        exit;
    }

    //Verification mot de passe
    $password = $ligne['mdp'];
    if (password_verify($mdp, $password)) {}
    else {
        header("Location: ../views/connexion.php?erreur=mdp_incorrect");
        exit;
    }
    //Mise en $_SESSION des infos principales
    connexion($id, $bdd);
}

function envoyerEmailReset($destinataire, $token) {
    $mail = new PHPMailer(true);
    $env = parse_ini_file(__DIR__ . '/../../config/.env');

    try {
        $mail->isSMTP();
        $mail->Host       = $env['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $env['MAIL_USERNAME'];
        $mail->Password   = $env['MAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $env['MAIL_PORT'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($env['MAIL_USERNAME'], $env['MAIL_FROM_NAME']);
        $mail->addAddress($destinataire);

        // Adapter l'URL selon votre configuration
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $lien = $protocol . "://" . $host . "/views/resetPassword.php?token=" . $token;

        $mail->Subject = 'Réinitialisation de mot de passe - StudTraj';
        $mail->Body    = "Bonjour,\n\n";
        $mail->Body   .= "Vous avez demandé à réinitialiser votre mot de passe sur StudTraj.\n\n";
        $mail->Body   .= "Cliquez sur le lien suivant pour créer un nouveau mot de passe :\n";
        $mail->Body   .= $lien . "\n\n";
        $mail->Body   .= "Ce lien est valable pendant 1 heure.\n\n";
        $mail->Body   .= "Si vous n'avez pas demandé cette réinitialisation, ignorez ce message.\n\n";
        $mail->Body   .= "Cordialement,\n";
        $mail->Body   .= "L'équipe StudTraj";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Erreur envoi mail reset : " . $mail->ErrorInfo);
        return false;
    }
}

// Fonction pour gérer la demande de réinitialisation
function forgotPassword($mail) {
    global $bdd;

    // Vérifier si l'email existe
    $checkEmail = $bdd->prepare("SELECT id FROM utilisateurs WHERE mail = :mail");
    $checkEmail->execute(['mail' => $mail]);

    if ($checkEmail->rowCount() == 0) {
        return ['success' => false, 'error' => 'email_inexistant'];
    }

    // Générer un token unique
    $token = bin2hex(random_bytes(32));
    $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Stocker le token en base de données
    $updateToken = $bdd->prepare("UPDATE utilisateurs SET reset_token = :token, reset_expiration = :expiration WHERE mail = :mail");
    $updateToken->execute([
        'token' => $token,
        'expiration' => $expiration,
        'mail' => $mail
    ]);

    // Envoyer l'email avec le lien
    if (envoyerEmailReset($mail, $token)) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => 'envoi_mail'];
    }
}

// Traitement de la demande de réinitialisation
if(isset($_POST['forgot_password'])) {
    $mail = trim($_POST['mail']);

    $result = forgotPassword($mail);

    if($result['success']) {
        header("Location: ../views/connexion.php?succes=reset_envoye");
    } else {
        header("Location: ../views/forgotPassword.php?erreur=" . $result['error']);
    }
    exit;
}

// Traitement du renvoi de code
if(isset($_POST['renvoyer_code'])) {
    session_start();

    if(!isset($_SESSION['mail'])) {
        header("Location: ../views/formulaire.php?erreur=session_expiree");
        exit;
    }

    $mail = $_SESSION['mail'];

    // Vérifier si l'inscription en attente existe encore
    $checkAttente = $bdd->prepare("SELECT * FROM inscriptions_en_attente WHERE mail = :mail");
    $checkAttente->execute(['mail' => $mail]);
    $userAttente = $checkAttente->fetch(PDO::FETCH_ASSOC);

    if(!$userAttente) {
        header("Location: ../views/verificationMail.php?erreur=inscription_expiree");
        exit;
    }

    // Générer un nouveau code
    $nouveau_code = rand(100000, 999999);

    // Mettre à jour le code et le temps dans la base de données
    $updateCode = $bdd->prepare("UPDATE inscriptions_en_attente SET code_verif = :code, date_creation = NOW() WHERE mail = :mail");
    $updateCode->execute([
        'code' => $nouveau_code,
        'mail' => $mail
    ]);

    // Envoyer le nouveau code par email
    if (envoyerEmail($mail, $nouveau_code)) {
        header("Location: ../views/verificationMail.php?succes=code_renvoye");
    } else {
        header("Location: ../views/verificationMail.php?erreur=envoi_mail_echec");
    }
    exit;
}

// Traitement du nouveau mot de passe
if(isset($_POST['reset_password'])) {
    extract($_POST);

    // Vérifier le token
    $checkToken = $bdd->prepare("SELECT id, reset_expiration FROM utilisateurs WHERE reset_token = :token");
    $checkToken->execute(['token' => $token]);

    if($checkToken->rowCount() == 0) {
        header("Location: ../views/connexion.php?erreur=token_invalide");
        exit;
    }

    $user = $checkToken->fetch(PDO::FETCH_ASSOC);

    // Vérifier si le token n'est pas expiré
    if(strtotime($user['reset_expiration']) < time()) {
        header("Location: ../views/connexion.php?erreur=token_expire");
        exit;
    }

    // Mettre à jour le mot de passe
    $hashedPassword = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
    $updatePassword = $bdd->prepare("UPDATE utilisateurs SET mdp = :mdp, reset_token = NULL, reset_expiration = NULL WHERE id = :id");
    $updatePassword->execute([
        'mdp' => $hashedPassword,
        'id' => $user['id']
    ]);

    header("Location: ../views/connexion.php?succes=mdp_reinitialise");
    exit;
}
?>
