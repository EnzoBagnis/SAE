<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <title>Mot de passe oublié - StudTraj</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

<div class="page-wrap">

    <?php
    if (isset($_GET['erreur'])) {
        switch($_GET['erreur']) {
            case 'email_inexistant':
                echo '<div class="error">Aucun compte trouvé avec cet email.</div>';
                break;
            case 'envoi_mail':
                echo '<div class="error">Erreur lors de l\'envoi de l\'email. Veuillez réessayer.</div>';
                break;
        }
    }
    ?>

    <form class="card" method="POST" action="../controllers/traitement.php" id="forgotForm">
        <h2>Mot de passe oublié</h2>
        <p>Entrez votre email pour recevoir un lien de réinitialisation</p>

        <label for="email">Email</label>
        <input type="email"
               id="email"
               name="mail"
               placeholder="Valeur"
               required>

        <button type="submit"
                name="forgot_password"
                id="submitBtn"
                class="btn-submit"
                disabled>
            Envoyer le lien de réinitialisation
        </button>

        <div class="text-center mt-2">
            <a href="connexion.php">Retour à la connexion</a>
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