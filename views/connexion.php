<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">

    <!-- SEO Meta Tags -->
    <meta name="description" content="Connectez-vous à votre compte StudTraj pour accéder à votre suivi de trajectoire étudiante et gérer votre parcours académique.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="http://studtraj.alwaysdata.net/views/connexion.php">

    <title>Connexion - StudTraj | Accédez à votre compte</title>
    <link rel="stylesheet" href="../public/css/style.css">
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
    // Afficher les messages d'erreur ou de succès
    if (isset($_GET['erreur'])) {
        switch($_GET['erreur']) {
            case 'email_existe_pas':
                echo '<div class="error">Cet email n\'existe pas !</div>';
                break;
            case 'mdp_incorrect':
                echo '<div class="error">Mot de passe incorrect</div>';
                break;
            case 'insertion':
                echo '<div class="error">Erreur lors de l\'inscription</div>';
                break;
            case 'token_invalide':
                echo '<div class="error">Lien de réinitialisation invalide</div>';
                break;
            case 'token_expire':
                echo '<div class="error">Lien de réinitialisation expiré</div>';
                break;
        }
    }

    if (isset($_GET['succes'])) {
        switch($_GET['succes']) {
            case 'reset_envoye':
                echo '<div class="success">Un email de réinitialisation a été envoyé !</div>';
                break;
            case 'mdp_reinitialise':
                echo '<div class="success">Votre mot de passe a été réinitialisé avec succès !</div>';
                break;
        }
    }
    ?>

    <form class="card" method="POST" action="../controllers/traitement.php">

        <label for="mail">Email</label>
        <input type="email" id="mail" name="mail" placeholder="Valeur" required><br>

        <label for="mdp">Mot de passe</label>
        <input type="password" id="mdp" name="mdp" placeholder="Valeur" required><br>

        <button type="submit" class="btn-submit" name="connexion">Connexion</button>

        <div class="form-links">
            <a href="forgotPassword.php">Mot de passe?</a>
            <a href="formulaire.php">Inscription</a>
        </div>

    </form>

</div>

</body>
</html>