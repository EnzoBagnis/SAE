<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">

    <!-- SEO Meta Tags -->
    <meta name="description"
          content="Connectez-vous à votre compte StudTraj pour accéder à votre tableau de bord
                   et suivre votre trajectoire étudiante.">
    <meta name="keywords" content="connexion studtraj, login étudiant, se connecter">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="http://studtraj.alwaysdata.net/index.php?action=login">

    <title><?= htmlspecialchars($title ?? 'Connexion - StudTraj') ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

<div class="page-wrap">

    <!-- Flèche de retour à l'accueil -->
    <a href="/index.html" class="back-arrow" title="Retour à l'accueil">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
    </a>

    <?php if (isset($error_message)) : ?>
        <div class="error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if (isset($success_message)) : ?>
        <div class="success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <form class="card" method="POST" action="/index.php?action=login">
        <h2>Connexion</h2>

        <label for="mail">Email</label>
        <input type="email" id="mail" name="mail" placeholder="Entrez votre email..." required><br>

        <label for="mdp">Mot de passe</label>
        <input type="password" id="mdp" name="mdp" placeholder="Entrez votre mot de passe..." required><br>

        <button type="submit" class="btn-submit" name="ok">Se connecter</button>

        <div class="text-center mt-2">
            <a href="/index.php?action=forgotpassword">Mot de passe oublié ?</a>
        </div>

        <div class="text-center mt-2">
            <a href="/index.php?action=signup">Pas encore de compte ? S'inscrire</a>
        </div>
    </form>

</div>

</body>
</html>
