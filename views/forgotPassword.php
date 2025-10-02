<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - StudTraj</title>
    <style>
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
<h2>Mot de passe oublié</h2>

<?php if(isset($_GET['erreur'])): ?>
    <p style="color: red;">
        <?php
        if($_GET['erreur'] == 'email_inexistant') echo "Aucun compte trouvé avec cet email.";
        if($_GET['erreur'] == 'envoi_mail') echo "Erreur lors de l'envoi de l'email.";
        ?>
    </p>
<?php endif; ?>

<form method="POST" action="../controllers/inscription.php" id="forgotForm">
    <input type="email"
           name="mail"
           id="email"
           placeholder="Votre email"
           required>

    <button type="submit"
            name="forgot_password"
            id="submitBtn"
            disabled>
        Envoyer le lien
    </button>
</form>

<script>
    const emailInput = document.getElementById('email');
    const submitBtn = document.getElementById('submitBtn');

    emailInput.addEventListener('input', function() {
        // Activer le bouton seulement si l'email est rempli
        if(this.value.trim() !== '') {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    });
</script>
</body>
</html>