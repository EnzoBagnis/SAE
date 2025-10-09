<?php
$token = $_GET['token'] ?? '';
if(empty($token)) {
    header("Location: connexion.php?erreur=token_manquant");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <title>Réinitialiser le mot de passe - StudTraj</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

<div class="page-wrap">
    <form class="card" method="POST" action="../controllers/traitement.php" id="resetForm">
        <h2>Créer un nouveau mot de passe</h2>
        <p>Entrez votre nouveau mot de passe</p>

        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <label for="password">Nouveau mot de passe</label>
        <input type="password"
               name="nouveau_mdp"
               id="password"
               placeholder="Minimum 6 caractères"
               required>

        <label for="confirm_password">Confirmer le mot de passe</label>
        <input type="password"
               id="confirm_password"
               placeholder="Confirmez votre mot de passe"
               required>

        <p id="error_message" style="display: none;"></p>

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

        if(password.length < 6) {
            submitBtn.disabled = true;
            errorMsg.textContent = 'Le mot de passe doit contenir au moins 6 caractères.';
            errorMsg.style.display = 'block';
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