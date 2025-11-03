<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <title><?= htmlspecialchars($title ?? 'StudTraj - Tableau de bord') ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/dashboard.css">
     <link rel="stylesheet" href="../public/css/footer.css">
    <script src="/public/js/dashboard-main.js" type="module" defer></script>

    <!-- SEO Meta Tags -->
    <meta name="description" content="Hub principal du site, vous pourrez y visionner les différents TD.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="http://studtraj.alwaysdata.net/views/dashboard.php">
</head>
<body>
    <!-- Menu du haut -->
    <header class="top-menu">
        <div class="logo">
            <h1>StudTraj</h1>
        </div>

        <!-- Bouton burger pour mobile -->
        <button class="burger-menu" id="burgerBtn" onclick="toggleBurgerMenu()" aria-label="Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav class="nav-menu">
            <a href="/index.php?action=dashboard" class="active">Tableau de bord</a>
            <a href="#" onclick="openSiteMap()">Plan du site</a>
            <a href="/index.php?action=mentions">Mentions légales</a>
        </nav>
        <div class="user-info">
            <span><?= htmlspecialchars($user_firstname ?? '') ?> <?= htmlspecialchars($user_lastname ?? '') ?></span>
            <button onclick="confirmLogout()" class="btn-logout">Déconnexion</button>
        </div>
    </header>

    <!-- Menu burger mobile -->
    <nav class="burger-nav" id="burgerNav">
        <div class="burger-nav-content">
            <div class="burger-user-info">
                <span>
                    <?= htmlspecialchars($user_firstname ?? '') ?>
                    <?= htmlspecialchars($user_lastname ?? '') ?>
                </span>
            </div>
            <ul class="burger-menu-list">
                <li><a href="/index.php?action=dashboard" class="burger-link active">Tableau de bord</a></li>
                <li class="has-submenu">
                    <a href="#" class="burger-link" onclick="toggleStudentSubmenu(event)">
                        Liste des Étudiants
                        <span class="submenu-arrow">▼</span>
                    </a>
                    <ul class="burger-submenu" id="burgerStudentList">
                        <!-- Les étudiants seront chargés ici dynamiquement -->
                    </ul>
                </li>
                <li><a href="/index.php?action=mentions" class="burger-link">Mentions légales</a></li>
                <li><a href="#" onclick="confirmLogout()" class="burger-link burger-logout">Déconnexion</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Sidebar gauche pour les Étudiants -->
        <aside class="sidebar">
            <h2>Liste des Étudiants</h2>
            <div class="student-list" id="student-list">
                <!-- La liste des étudiants sera ajoutée ici dynamiquement via JavaScript -->
            </div>
        </aside>

        <!-- Zone principale de visualisation -->
        <main class="main-content">
            <div class="data-zone">
                <p class="placeholder-message">Les données de l'étudiant seront affichées ici</p>
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
                    <li><a href="/index.php?action=dashboard">Tableau de bord</a></li>
                    <li><a href="/index.php?action=login">Connexion</a></li>
                    <li><a href="/index.php?action=signup">Inscription</a></li>
                    <li><a href="/index.php?action=forgotpassword">Mot de passe oublié</a></li>
                    <li><a href="/index.php?action=mentions">Mentions légales</a></li>
                </ul>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../layouts/footer.php'; ?>

</body>
</html>
