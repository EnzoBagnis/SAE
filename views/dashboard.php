<?php
session_start();
if (!isset($_SESSION['id'])) {
    header('Location: connexion.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <title>StudTraj - Tableau de bord</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/dashboard.css">
    <script src="../public/js/dashboard.js" defer></script>

</head>
<body>
    <!-- Menu du haut -->
    <header class="top-menu">
        <div class="logo">
            <h1>StudTraj</h1>
        </div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="active">Tableau de bord</a>
            <a href="#" onclick="openSiteMap()">Plan du site</a>
            <a href="mentions-legales.php">Mentions légales</a>
        </nav>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['prenom']); ?> <?php echo htmlspecialchars($_SESSION['nom']); ?></span>
            <button onclick="confirmLogout()" class="btn-logout">Déconnexion</button>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- Sidebar gauche pour les TPs -->
        <aside class="sidebar">
            <h2>Liste des TPs</h2>
            <div class="tp-list" id="tp-list">
                <!-- La liste des TPs sera ajoutée ici -->
            </div>
        </aside>

        <!-- Zone principale de visualisation -->
        <main class="main-content">
            <div class="data-zone">
                <p class="placeholder-message">Les jeux de données seront affichés ici</p>
            </div>
        </main>
    </div>

    <!-- Modal Plan du site -->
    <div id="sitemapModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeSiteMap()">&times;</span>
            <h2>Plan du site</h2>
            <div class="sitemap-list">
                <ul>
                    <li><a href="dashboard.php">Tableau de bord</a></li>
                    <li><a href="connexion.php">Connexion</a></li>
                    <li><a href="formulaire.php">Inscription</a></li>
                    <li><a href="forgotPassword.php">Mot de passe oublié</a></li>
                    <li><a href="mentions-legales.php">Mentions légales</a></li>
                </ul>
            </div>
        </div>
    </div>


</body>
</html>

