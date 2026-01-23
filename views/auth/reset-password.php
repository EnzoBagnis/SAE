<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <title><?= htmlspecialchars($title ?? 'Réinitialiser le mot de passe - StudTraj') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
    <!-- SEO Meta Tags -->
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="http://studtraj.alwaysdata.net/index.php?action=resetPassword">
</head>
<body>

<div class="page-wrap">

    <?php if (isset($error_message)) : ?>
        <div class="error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form class="card" method="POST" action="<?= BASE_URL ?>/index.php?action=resetpassword" id="resetForm">
        <h2>Créer un nouveau mot de passe</h2>
        <p>Entrez votre nouveau mot de passe</p>

        <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

        <label for="password">Nouveau mot de passe</label>
        <div class="password-container">
            <input type="password"
                   name="nouveau_mdp"
                   id="password"
                   placeholder="Minimum 6 caractères"
                   required>
            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </button>
        </div>

        <label for="confirm_password">Confirmer le mot de passe</label>
        <div class="password-container">
            <input type="password"
                   name="confirm_mdp"
                   id="confirm_password"
                   placeholder="Confirmez votre mot de passe"
                   required>
            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </button>
        </div>

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

    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const button = input.nextElementSibling;
        const icon = button.querySelector('svg');

        if (input.type === "password") {
            input.type = "text";
            // Change icon to "eye-off"
            icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 ' +
                '5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 ' +
                '0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
        } else {
            input.type = "password";
            // Change icon back to "eye"
            icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>' +
                '<circle cx="12" cy="12" r="3"></circle>';
        }
    }

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
