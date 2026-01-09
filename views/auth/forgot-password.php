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

    <?php if (isset($error_message)) : ?>
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
