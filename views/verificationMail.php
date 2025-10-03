<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <meta name="description" content="Vérification de votre email">
    <title>StudTraj - Vérification Email</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="page-wrap">

    <?php
    if(isset($_GET['erreur'])) {
        switch($_GET['erreur']) {
            case 'code_incorrect':
                echo '<div class="error">Code incorrect, veuillez réessayer.</div>';
                break;
            case 'inscription_expiree':
                echo '<div class="error">Votre inscription a expiré. Veuillez vous réinscrire.</div>';
                break;
            case 'envoi_mail_echec':
                echo '<div class="error">Erreur lors de l\'envoi de l\'email. Veuillez réessayer.</div>';
                break;
            case 'session_expiree':
                echo '<div class="error">Session expirée. Veuillez vous réinscrire.</div>';
                break;
        }
    }

    if(isset($_GET['succes'])) {
        switch($_GET['succes']) {
            case 'inscription':
                echo '<div class="success">Un code de vérification a été envoyé à votre email !</div>';
                break;
            case 'code_renvoye':
                echo '<div class="success">Un nouveau code de vérification a été envoyé !</div>';
                break;
        }
    }
    ?>

    <div class="card verification-container">
        <h2>Vérification de l'email</h2>
        <p>Entrez le code à 6 chiffres envoyé par email</p>

        <form action="../controllers/traitement.php" method="POST">
            <label for="code">Code de vérification</label>
            <input type="number"
                   id="code"
                   name="code"
                   class="code-input"
                   placeholder="000000"
                   maxlength="6"
                   required>
            <button type="submit" class="btn-submit" name="verifier">Vérifier</button>
        </form>

        <p class="mt-3">Vous n'avez pas reçu le code ?</p>
        <form action="../controllers/traitement.php" method="POST">
            <button type="submit" class="btn-secondary" name="renvoyer_code">Renvoyer le code</button>
        </form>
    </div>

    <div class="back-arrow" onclick="window.location.href='../index.html';">←</div>

</div>

</body>
</html>