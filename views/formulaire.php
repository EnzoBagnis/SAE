<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>StudTraj - Inscription</title>
    <!-- <link rel="stylesheet" href="formStyle.css"> -->
</head>
<body>

<?php
// Afficher les messages d'erreur ou de succés
if (isset($_GET['erreur'])) {
    switch($_GET['erreur']) {
        case 'email_existe':
            echo '<div class="error">Erreur : Cet email est déja  utilisé !</div>';
            break;
        case 'insertion':
            echo '<div class="error">Erreur lors de l\'inscription</div>';
            break;
    }
}

?>

<div class="page-wrap">

    <form class="card" method="POST" action="../controllers/traitement.php">

        <label for="nom">Votre nom</label>
        <input type="text" id="nom" name="nom" placeholder="Entrez votre nom..." required><br>

        <label for="prenom">Votre prénom</label>
        <input type="text" id="prenom" name="prenom" placeholder="Entrez votre prénom..." required><br>

        <label for="mail">Votre mail</label>
        <input type="email" id="mail" name="mail" placeholder="Entrez votre mail..." required><br>

        <label for="mdp">Votre mot de passe</label>
        <input type="password" id="mdp" name="mdp" placeholder="Entrez votre mdp..." required><br>

        <button type="submit" class="btn-submit" name="ok">M'inscrire</button><br>

    </form>
    <button onclick="window.location.href='./connexion.php'">Se connecter</button>
</div>
</body>
</html>