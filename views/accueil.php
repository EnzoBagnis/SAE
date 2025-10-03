<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>StudTraj - Accueil</title>
    <link rel="stylesheet" href="style.css">
    <script src="main.js"></script>
</head>
<body>
<div class="page-wrap">
    <h1>Ceci est la page d'accueil temporaire</h1>
    <?php
        session_start();
        if (!isset($_SESSION['id'])) {
            echo '<div class="error">Vous devez être connecté pour accéder à cette page.</div>';
            echo '<button onclick="window.location.href=\'connexion.php\'">Aller à la page de connexion</button>';
            exit;
        }
        echo '<div class="succes">Bienvenue ' . $_SESSION['nom'] . ' ' . $_SESSION['prenom'] . '</div>';
    ?>
    <button onclick="window.location.href='./page2.php'">Changer de page</button>
    <button onclick="confirmLogout()">Se déconnecter</button>
</div>
</body>
</html>