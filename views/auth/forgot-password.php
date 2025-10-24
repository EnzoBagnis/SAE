<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <title><?= htmlspecialchars($title ?? 'Mot de passe oublié - StudTraj') ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
    <!-- SEO Meta Tags -->
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="http://studtraj.alwaysdata.net/views/forgotPassword.php">
</head>
<body>

<div class="page-wrap">

    <?php if (isset($error_message)): ?>
        <div class="error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form class="card" method="POST" action="/index.php?action=forgotpassword" id="forgotForm">
        <h2>Mot de passe oublié</h2>
        <p>Entrez votre email pour recevoir un lien de réinitialisation</p>

        <label for="email">Email</label>
        <input type="email"
               id="email"
               name="mail"
               placeholder="Votre email"
               required>

        <button type="submit"
                name="forgot_password"
                id="submitBtn"
                class="btn-submit"
                disabled>
            Envoyer le lien de réinitialisation
        </button>

        <div class="text-center mt-2">
            <a href="/index.php?action=login">Retour à la connexion</a>
        </div>
    </form>

    <div class="back-arrow" onclick="window.location.href='../index.html';">←</div>

</div>

<script>
    const emailInput = document.getElementById('email');
    const submitBtn = document.getElementById('submitBtn');

    emailInput.addEventListener('input', function() {
        if(this.value.trim() !== '' && this.value.includes('@')) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    });
</script>
</body>
</html>
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

    <title><?= htmlspecialchars($title ?? 'Connexion - StudTraj') ?></title>
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

    <?php if (isset($error_message)): ?>
        <div class="error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if (isset($success_message)): ?>
        <div class="success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <form class="card" method="POST" action="/index.php?action=login">

        <label for="mail">Email</label>
        <input type="email" id="mail" name="mail" placeholder="Entrez votre mail..." required><br>

        <label for="mdp">Mot de passe</label>
        <input type="password" id="mdp" name="mdp" placeholder="Entrez votre mot de passe..." required><br>

        <button type="submit" class="btn-submit" name="connexion">Connexion</button>

        <div class="form-links">
            <a href="/index.php?action=forgotpassword">Mot de passe oublié ?</a>
            <a href="/index.php?action=signup">Inscription</a>
        </div>

    </form>

</div>

</body>
</html>

