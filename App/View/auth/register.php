<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/favicon.ico">

    <!-- SEO Meta Tags -->
    <meta name="description"
          content="Créez votre compte StudTraj gratuitement et commencez à suivre
                   votre trajectoire étudiante. Inscription simple et rapide.">
    <meta name="keywords" content="inscription studtraj, créer compte étudiant, inscription gratuite">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= BASE_URL ?>/index.php?action=signup">

    <title><?= htmlspecialchars($title ?? 'Inscription gratuite - StudTraj') ?></title>
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

    <?php if (isset($error)) : ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form class="card" method="POST" action="<?= BASE_URL ?>/auth/register">

        <label for="last_name">Nom</label>
        <input type="text" id="last_name" name="last_name"
               value="<?= htmlspecialchars($last_name ?? '') ?>"
               placeholder="Entrez votre nom..." required><br>

        <label for="first_name">Prénom</label>
        <input type="text" id="first_name" name="first_name"
               value="<?= htmlspecialchars($first_name ?? '') ?>"
               placeholder="Entrez votre prénom..." required><br>

        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($email ?? '') ?>"
               placeholder="Entrez votre mail..." required><br>

        <label for="password">Mot de passe</label>
        <div class="password-container">
            <input type="password" id="password" name="password"
                   placeholder="Entrez votre mot de passe..." required
                   minlength="12">
            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </button>
        </div>
        <ul id="password-rules" style="font-size:0.85em; margin:4px 0 8px 0; padding-left:1.2em; list-style:none;">
            <li id="rule-length"  style="color:#c0392b;">✗ Au moins 12 caractères</li>
            <li id="rule-upper"   style="color:#c0392b;">✗ Au moins une lettre majuscule</li>
            <li id="rule-lower"   style="color:#c0392b;">✗ Au moins une lettre minuscule</li>
            <li id="rule-special" style="color:#c0392b;">✗ Au moins un caractère spécial</li>
        </ul>
        <p id="password-error" style="color:#c0392b; font-size:0.85em; display:none;">
            Le mot de passe ne respecte pas les exigences.
        </p><br>

        <button type="submit" class="btn-submit" id="submit-btn">S'inscrire</button>

        <div class="text-center mt-2">
            <a href="<?= BASE_URL ?>/auth/login">Déjà un compte ? Se connecter</a>
        </div>

    </form>
</div>

<script>
    /**
     * Toggle password visibility.
     * @param {string} inputId - The ID of the password input to toggle.
     */
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

    (function () {
        const passwordInput = document.getElementById('password');
        const submitBtn     = document.getElementById('submit-btn');
        const errorMsg      = document.getElementById('password-error');

        const rules = {
            'rule-length':  (v) => v.length >= 12,
            'rule-upper':   (v) => /[A-Z]/.test(v),
            'rule-lower':   (v) => /[a-z]/.test(v),
            'rule-special': (v) => /[\W_]/.test(v),
        };

        /**
         * Validate password against all rules and update UI accordingly.
         */
        function validatePassword() {
            const value = passwordInput.value;
            let allValid = true;

            for (const [id, check] of Object.entries(rules)) {
                const li = document.getElementById(id);
                const ok = check(value);
                li.style.color = ok ? '#27ae60' : '#c0392b';
                li.textContent  = (ok ? '✓' : '✗') + ' ' + li.textContent.slice(2);
                if (!ok) allValid = false;
            }

            if (value.length > 0 && !allValid) {
                errorMsg.style.display = 'block';
            } else {
                errorMsg.style.display = 'none';
            }

            submitBtn.disabled = !allValid;
        }

        passwordInput.addEventListener('input', validatePassword);

        // Run once to initialise button state
        validatePassword();
    })();
</script>
</body>
</html>