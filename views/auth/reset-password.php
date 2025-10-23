<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <title><?= htmlspecialchars($title ?? 'Réinitialiser le mot de passe - StudTraj') ?></title>
    <link rel="stylesheet" href="/SAE/public/css/style.css">
    <!-- SEO Meta Tags -->
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="http://studtraj.alwaysdata.net/index.php?action=resetPassword">
</head>
<body>

<div class="page-wrap">

    <?php if (isset($error_message)): ?>
        <div class="error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form class="card" method="POST" action="/index.php?action=resetpassword" id="resetForm">
        <h2>Créer un nouveau mot de passe</h2>
        <p>Entrez votre nouveau mot de passe</p>

        <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

        <label for="password">Nouveau mot de passe</label>
        <input type="password"
               name="nouveau_mdp"
               id="password"
               placeholder="Minimum 6 caractères"
               required>

        <label for="confirm_password">Confirmer le mot de passe</label>
        <input type="password"
               name="confirm_mdp"
               id="confirm_password"
               placeholder="Confirmez votre mot de passe"
               required>

        <p id="error_message" style="display: none; color: red;"></p>

        <button type="submit"
                name="reset_password"
                id="submitBtn"
                class="btn-submit"
                disabled>
            Réinitialiser le mot de passe
        </button>
    </form>
</div>

<script>
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');
    const errorMsg = document.getElementById('error_message');

    function validateForm() {
        const password = passwordInput.value;
        const confirm = confirmInput.value;

        if(password === '' || confirm === '') {
            submitBtn.disabled = true;
            errorMsg.style.display = 'none';
            return;
        }

        if(password !== confirm) {
            submitBtn.disabled = true;
            errorMsg.textContent = 'Les mots de passe ne correspondent pas.';
            errorMsg.style.display = 'block';
            return;
        }

        submitBtn.disabled = false;
        errorMsg.style.display = 'none';
    }

    passwordInput.addEventListener('input', validateForm);
    confirmInput.addEventListener('input', validateForm);
</script>
</body>
</html>
