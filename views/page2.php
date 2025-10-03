<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <meta name="description" content="Ceci est une meta description">
    <title>StudTraj - Page2</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="page-wrap">
    <div class="welcome-container">
        <!-- CECI EST UNE PAGE DE TEST POUR VOIR SI LES CONNEXIONS SONT TOUJOURS EFFECTIVES -->
        <?php
            session_start();
            if(isset($_SESSION['nom']) && isset($_SESSION['prenom'])) {
                echo '<div class="welcome-message">';
                echo '<h1>Page 2</h1>';
                echo '<p>Bienvenue ' . htmlspecialchars($_SESSION['prenom']) . ' ' . htmlspecialchars($_SESSION['nom']) . '</p>';
                echo '</div>';
            }
        ?>
        <button class="btn-submit" onclick="window.location.href='accueil.php'">Retour à l'accueil</button>
    </div>

    <div class="back-arrow" onclick="window.location.href='../index.html';">←</div>
</div>
</body>
</html>
