<?php
require_once '../models/Database.php';

require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';
require '../phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;



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

if(isset($_POST['ok']) && !isset($_POST['code'])){
    extract($_POST);

    // Utilisation de la classe Database au lieu de refaire la connexion
    $bdd = Database::getConnection();

    $checkEmail = $bdd->prepare("SELECT COUNT(*) FROM utilisateurs WHERE mail = :mail");
    $checkEmail->execute(['mail' => $mail]);

    if ($checkEmail->fetchColumn() > 0) {
        // Rediriger vers le formulaire avec erreur
        header("Location: ../views/formulaire.php?erreur=email_existe");
        exit;
    }

    $code_verif = rand(100000, 999999);

    $hashedPassword = password_hash($mdp, PASSWORD_DEFAULT);
    $requete = $bdd->prepare("INSERT INTO utilisateurs (nom, prenom, mdp, mail, code_verif, verifie) VALUES (:nom, :prenom, :mdp, :mail, :code_verif, 0)");

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
    $mail = $_SESSION['mail']; // Stocker l'email en session lors de l'inscription

    $bdd = Database::getConnection();
    $verif = $bdd->prepare("SELECT code_verif FROM utilisateurs WHERE mail = :mail");
    $verif->execute(['mail' => $mail]);
    $code_enregistre = $verif->fetchColumn();

    if($code == $code_enregistre) {
        // Mettre Ã  jour la vÃ©rification
        $update = $bdd->prepare("UPDATE utilisateurs SET verifie = 1 WHERE mail = :mail");
        $update->execute(['mail' => $mail]);
        header("Location: ../views/accueil.php?succes=verifie");
        exit;
    } else {
        header("Location: ../views/verificationMail.php?erreur=code_incorrect");
        exit;
    }
}



if(isset($_POST['connexion'])){
    extract($_POST);

    // Utilisation de la classe Database au lieu de refaire la connexion
    $bdd = Database::getConnection();

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
    $conex = $bdd->prepare("SELECT * FROM utilisateurs WHERE id = :id");
    $conex->execute(['id' => $id]);
    $session = $conex->fetch(PDO::FETCH_ASSOC);

    $_SESSION['id'] = $id;
    $nom = $session['nom'];
    $_SESSION['nom'] = $nom;
    $prenom = $session['prenom'];
    $_SESSION['prenom'] = $prenom;
    header("Location: ../views/accueil.php?succes=connexion");




}

?>


