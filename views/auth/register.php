<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">

    <!-- SEO Meta Tags -->
    <meta name="description" content="Créez votre compte StudTraj gratuitement et commencez à suivre votre trajectoire étudiante. Inscription simple et rapide.">
    <meta name="keywords" content="inscription studtraj, créer compte étudiant, inscription gratuite">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="http://studtraj.alwaysdata.net/views/formulaire.php">

    <title><?= htmlspecialchars($title ?? 'Inscription gratuite - StudTraj') ?></title>
    <link rel="stylesheet" href="/SAE/public/css/style.css">
</head>
<body>

<div class="page-wrap">

    <!-- Flèche de retour à l'accueil -->
    <a href="../index.html" class="back-arrow" title="Retour à l'accueil">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
    </a>

    <?php if (isset($error_message)): ?>
        <div class="error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form class="card" method="POST" action="/index.php?action=signup">

        <label for="nom">Nom</label>
        <input type="text" id="nom" name="nom" placeholder="Entrez votre nom..." required><br>

        <label for="prenom">Prénom</label>
        <input type="text" id="prenom" name="prenom" placeholder="Entrez votre prénom..." required><br>

        <label for="mail">Email</label>
        <input type="email" id="mail" name="mail" placeholder="Entrez votre mail..." required><br>

        <label for="mdp">Mot de passe</label>
        <input type="password" id="mdp" name="mdp" placeholder="Entrez votre mot de passe..." required><br>

        <button type="submit" class="btn-submit" name="ok">Inscription</button>

        <div class="text-center mt-2">
            <a href="/index.php?action=login">Déjà un compte ? Se connecter</a>
        </div>

    </form>
</div>
</body>
</html>

