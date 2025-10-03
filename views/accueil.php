<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <meta name="description" content="Ceci est une meta description">
    <title>StudTraj - Accueil</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="page-wrap">
    <div class="welcome-container">
        <h1>Bienvenue sur StudTraj</h1>
        <?php
            session_start();
            if(isset($_SESSION['nom']) && isset($_SESSION['prenom'])) {
                echo '<div class="welcome-message">';
                echo '<h2>Bonjour ' . htmlspecialchars($_SESSION['prenom']) . ' ' . htmlspecialchars($_SESSION['nom']) . ' !</h2>';
                echo '<p>Vous êtes maintenant connecté à votre compte.</p>';
                echo '</div>';
            }
        ?>
        <button class="btn-submit" onclick="window.location.href='./page2.php'">Accéder à la page 2</button>

        <?php if(isset($_GET['succes']) && $_GET['succes'] == 'verifie'): ?>
            <div class="success mt-3">Votre compte a été vérifié avec succès !</div>
        <?php endif; ?>
        <?php if(isset($_GET['succes']) && $_GET['succes'] == 'connexion'): ?>
            <div class="success mt-3">Connexion réussie !</div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>