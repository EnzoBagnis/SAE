<?php
require_once '../models/Database.php';

if(isset($_POST['ok'])){
    extract($_POST);

    $bdd = Database::getConnection();

    // Vérifier si l'email existe déjà
    $checkEmail = $bdd->prepare("SELECT COUNT(*) FROM utilisateurs WHERE mail = :mail");
    $checkEmail->execute(['mail' => $mail]);

    if ($checkEmail->fetchColumn() > 0) {
        // Rediriger vers le formulaire avec erreur
        header("Location: ../views/formulaire.php?erreur=email_existe");
        exit;
    }

    // Insérer l'utilisateur
    $hashedPassword = password_hash($mdp, PASSWORD_DEFAULT);
    $requete = $bdd->prepare("INSERT INTO utilisateurs VALUES(0, :nom, :prenom, :mdp, :mail)");

    try {
        $requete->execute(array(
            'nom' => $nom,
            'prenom' => $prenom,
            'mdp' => $hashedPassword,
            'mail' => $mail
        ));

        header("Location: ../views/accueil.php?succes=inscription");
        exit;

    } catch (PDOException $e) {
        header("Location: ../views/formulaire.php?erreur=insertion");
        exit;
    }
}
?>