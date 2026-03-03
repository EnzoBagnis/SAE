<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/favicon.ico">

    <!-- SEO Meta Tags -->
    <meta name="description"
          content="Connectez-vous à votre compte StudTraj pour accéder à votre tableau de bord
                   et suivre votre trajectoire étudiante.">
    <meta name="keywords" content="connexion studtraj, login étudiant, se connecter">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://studtraj.alwaysdata.net/auth/login">

    <title><?= htmlspecialchars($title ?? 'Connexion - StudTraj') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
</head>
<body>

<div class="page-wrap">

    <!-- Flèche de retour à l'accueil -->
    <a href="<?= BASE_URL ?>/" class="back-arrow" title="Retour à l'accueil">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
    </a>

    <?php if (isset($success_message)) : ?>
        <div class="success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <?php if (isset($error)) : ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form class="card" method="POST" action="<?= BASE_URL ?>/auth/login">
        <h2>Connexion</h2>

        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($email ?? '') ?>"
               placeholder="Entrez votre email..." required><br>

        <label for="password">Mot de passe</label>
        <div class="password-container">
            <input type="password" id="password" name="password"
                   placeholder="Entrez votre mot de passe..." required>
            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </button>
        </div>

        <button type="submit" class="btn-submit">Se connecter</button>

        <div class="text-center mt-2">
            <a href="<?= BASE_URL ?>/auth/forgot-password">Mot de passe oublié ?</a>
        </div>

        <div class="text-center mt-2">
            <a href="<?= BASE_URL ?>/auth/register">Pas encore de compte ? S'inscrire</a>
        </div>
    </form>

</div>

<script src="<?= BASE_URL ?>/public/js/password-toggle.js"></script>
</body>
</html>
