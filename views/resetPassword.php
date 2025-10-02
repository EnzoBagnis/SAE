<?php
$token = $_GET['token'] ?? '';
if(empty($token)) {
    header("Location: ../views/connexion.php?erreur=token_manquant");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe - StudTraj</title>
    <style>
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
<h2>Créer un nouveau mot de passe</h2>

<?php if(isset($_GET['erreur'])): ?>
    <p style="color: red;">
        <?php
        if($_GET['erreur'] == 'token_invalide') echo "Lien invalide.";
        if($_GET['erreur'] == 'token_expire') echo "Ce lien a expiré.";
        ?>
    </p>
<?php endif; ?>

<form method="POST" action="../controllers/inscription.php" id="resetForm">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

    <input type="password"
           name="nouveau_mdp"
           id="password"
           placeholder="Nouveau mot de passe (min. 6 caractères)"
           required>

    <input type="password"
           id="confirm_password"
           placeholder="Confirmer le mot de passe"
           required>

    <p id="error_message" style="color: red; display: none;"></p>

    <button type="submit"
            name="reset_password"
            id="submitBtn"
            disabled>
        Réinitialiser le mot de passe
    </button>
</form>

<script>
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');
    const errorMsg = document.getElementById('error_message');

    function validateForm() {
        const password = passwordInput.value;
        const confirm = confirmInput.value;

        // Vérifier que les deux champs sont remplis
        if(password === '' || confirm === '') {
            submitBtn.disabled = true;
            errorMsg.style.display = 'none';
            return;
        }

        // Vérifier la longueur minimale
        if(password.length < 6) {
            submitBtn.disabled = true;
            errorMsg.textContent = 'Le mot de passe doit contenir au moins 6 caractères.';
            errorMsg.style.display = 'block';
            return;
        }

        // Vérifier que les mots de passe correspondent
        if(password !== confirm) {
            submitBtn.disabled = true;
            errorMsg.textContent = 'Les mots de passe ne correspondent pas.';
            errorMsg.style.display = 'block';
            return;
        }

        // Tout est OK
        submitBtn.disabled = false;
        errorMsg.style.display = 'none';
    }

    passwordInput.addEventListener('input', validateForm);
    confirmInput.addEventListener('input', validateForm);
</script>
</body>
</html>