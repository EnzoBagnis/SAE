<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <meta name="description" content="Ceci est une meta description">
    <title>StudTraj - Connexion</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .password-container {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            color: #666;
        }
        .toggle-password:hover {
            color: #333;
        }
    </style>
</head>
<body>

<div class="page-wrap">

    <!-- Flèche de retour à l'accueil -->
    <a href="../index.html" class="back-arrow" title="Retour à l'accueil">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
    </a>

    <?php
    // Afficher les messages d'erreur ou de succès
    if (isset($_GET['erreur'])) {
        switch($_GET['erreur']) {
            case 'email_existe_pas':
                echo '<div class="error">Cet email n\'existe pas !</div>';
                break;
            case 'mdp_incorrect':
                echo '<div class="error">Mot de passe incorrect</div>';
                break;
            case 'insertion':
                echo '<div class="error">Erreur lors de l\'inscription</div>';
                break;
            case 'token_invalide':
                echo '<div class="error">Lien de réinitialisation invalide</div>';
                break;
            case 'token_expire':
                echo '<div class="error">Lien de réinitialisation expiré</div>';
                break;
        }
    }

    if (isset($_GET['succes'])) {
        switch($_GET['succes']) {
            case 'reset_envoye':
                echo '<div class="success">Un email de réinitialisation a été envoyé !</div>';
                break;
            case 'mdp_reinitialise':
                echo '<div class="success">Votre mot de passe a été réinitialisé avec succès !</div>';
                break;
        }
    }
    ?>

    <form class="card" method="POST" action="../controllers/traitement.php">

        <label for="mail">Email</label>
        <input type="email" id="mail" name="mail" placeholder="Valeur" required><br>

        <label for="mdp">Mot de passe</label>
        <div class="password-container" style="position: relative;">
            <input type="password" id="mdp" name="mdp" placeholder="Valeur" required style="width: 100%; padding-right: 40px;">
            <button type="button" class="toggle-password" onclick="togglePassword('mdp', this)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </button>
        </div>
        <br>

        <button type="submit" class="btn-submit" name="connexion">Connexion</button>

        <div class="form-links">
            <a href="forgotPassword.php">Mot de passe?</a>
            <a href="formulaire.php">Inscription</a>
        </div>

    </form>

</div>

<script>
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const svg = button.querySelector('svg');

    if (input.type === 'password') {
        input.type = 'text';
        svg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
    } else {
        input.type = 'password';
        svg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
    }
}
</script>

</body>
</html>