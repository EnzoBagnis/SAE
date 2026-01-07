<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">

    <title><?= htmlspecialchars($title ?? 'Connexion Admin - StudTraj') ?></title>
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
    <form class="card" method="POST" action="/index.php?action=adminLogin">
        <h2>Connexion Admin</h2>

        <label for="ID">Identifiant</label>
        <input type="text" id="ID" name="ID" placeholder="Identifiant admin..." required><br>

        <label for="mdp">Mot de passe</label>
        <input type="password" id="mdp" name="mdp" placeholder="Mot de passe..." required><br>

        <button type="submit" class="btn-submit" name="ok">Se connecter</button>
    </form>

</div>

</body>
</html>
