<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <meta name="description" content="Ceci est une meta description">
    <title>StudTraj - Inscription</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="page-wrap">

    <!-- Flèche de retour à l'accueil -->
    <a href="../index.html" class="back-arrow" title="Retour à l'accueil">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
    </a>

<?php
// Afficher les messages d'erreur ou de succés
if (isset($_GET['erreur'])) {
    switch($_GET['erreur']) {
        case 'email_existe':
            echo '<div class="error">Cet email est déjà utilisé !</div>';
            break;
        case 'attente_existe':
            echo '<div class="error">Un code de vérification a déjà été envoyé à cet email</div>';
            break;
        case 'insertion':
            echo '<div class="error">Erreur lors de l\'inscription</div>';
            break;
        case 'envoi_mail':
            echo '<div class="error">Erreur lors de l\'envoi de l\'email</div>';
            break;
    }
}
?>

    <form class="card" method="POST" action="../controllers/traitement.php">

        <label for="nom">Nom</label>
        <input type="text" id="nom" name="nom" placeholder="Valeur" required><br>

        <label for="prenom">Prénom</label>
        <input type="text" id="prenom" name="prenom" placeholder="Valeur" required><br>

        <label for="mail">Email</label>
        <input type="email" id="mail" name="mail" placeholder="Valeur" required><br>

        <label for="mdp">Mot de passe</label>
        <input type="password" id="mdp" name="mdp" placeholder="Valeur" required><br>

        <button type="submit" class="btn-submit" name="ok">Inscription</button>

        <div class="text-center mt-2">
            <a href="connexion.php">Déjà un compte ? Se connecter</a>
        </div>

    </form>
</div>
</body>
</html>
