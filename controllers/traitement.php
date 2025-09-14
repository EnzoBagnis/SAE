<?php
require_once '../models/Database.php';

if(isset($_POST['ok'])){
    extract($_POST);

    // Utilisation de la classe Database au lieu de refaire la connexion
    $bdd = Database::getConnection();

    $hashedPassword = password_hash($mdp, PASSWORD_DEFAULT);
    $requete = $bdd->prepare("INSERT INTO utilisateurs VALUES(0, :nom, :prenom, :mdp, :mail)");
    $requete->execute(array(
        'nom' => $nom,
        'prenom' => $prenom,
        'mdp' => $hashedPassword,
        'mail' => $mail
    ));
    echo "Inscription réussie !";
}
?>