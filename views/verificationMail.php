<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <meta name="description" content="Ceci est une meta description">
    <title>Vérification Email</title>
    <!-- <link rel="stylesheet" href="../public/css/style.css"> -->
</head>
<body>
<h1>Vérification de l'Email</h1>
<?php if(isset($_GET['erreur']) && $_GET['erreur'] == 'code_incorrect'): ?>
    <p style="color:red;">Code incorrect, veuillez réessayer.</p>
<?php endif; ?>
<?php if(isset($_GET['erreur']) && $_GET['erreur'] == 'inscription_expiree'): ?>
    <p style="color:red;">Votre inscription a expiré. Veuillez vous réinscrire.</p>
<?php endif; ?>
<?php if(isset($_GET['succes']) && $_GET['succes'] == 'code_renvoye'): ?>
    <p style="color:green;">Un nouveau code de vérification vous a été envoyé par email.</p>
<?php endif; ?>
<?php if(isset($_GET['erreur']) && $_GET['erreur'] == 'envoi_mail_echec'): ?>
    <p style="color:red;">Erreur lors de l'envoi de l'email. Veuillez réessayer.</p>
<?php endif; ?>

<form action="../controllers/traitement.php" method="POST">
    <label for="mailVerifcation">Code : </label>
    <input type="number" id="code" name="code" required>
    <button type="submit" name="verifier">Vérifier</button>
</form>

<br>
<p>Vous n'avez pas reçu le code ?</p>
<form action="../controllers/traitement.php" method="POST">
    <button type="submit" name="renvoyer_code">Renvoyer le code</button>
</form>
</body>
</html>