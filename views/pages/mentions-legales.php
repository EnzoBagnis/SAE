<?php
// La session est déjà démarrée par index.php, pas besoin de la redémarrer
// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <title>StudTraj - Mentions légales</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="stylesheet" href="/public/css/dashboard.css">
    <link rel="stylesheet" href="/public/css/mentions-legales.css">
    <link rel="stylesheet" href="/public/css/footer.css">
    <!-- SEO Meta Tags -->
    <meta name="mentions-legales" content="Je vous assure qu'on est ici en toute légalité.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="http://studtraj.alwaysdata.net/index.php?action=mentions">
</head>
<body<?php echo $isLoggedIn ? ' class="logged-in"' : ''; ?>>
    <?php if ($isLoggedIn): ?>
    <!-- Menu du haut pour utilisateurs connectés -->
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
            <a href="/index.php?action=dashboard">Tableau de bord</a>
            <a href="#" onclick="openSiteMap()">Plan du site</a>
            <a href="/index.php?action=mentions" class="active">Mentions légales</a>
        </nav>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['prenom']); ?> <?php echo htmlspecialchars($_SESSION['nom']); ?></span>
            <button onclick="confirmLogout()" class="btn-logout">Déconnexion</button>
        </div>
    </header>

    <!-- Menu burger mobile -->
    <nav class="burger-nav" id="burgerNav">
        <div class="burger-nav-content">
            <div class="burger-user-info">
                <span><?php echo htmlspecialchars($_SESSION['prenom']); ?> <?php echo htmlspecialchars($_SESSION['nom']); ?></span>
            </div>
            <ul class="burger-menu-list">
                <li><a href="/index.php?action=dashboard" class="burger-link">Tableau de bord</a></li>
                <li><a href="#" onclick="openSiteMap()" class="burger-link">Plan du site</a></li>
                <li><a href="/index.php?action=mentions" class="burger-link active">Mentions légales</a></li>
                <li><a href="#" onclick="confirmLogout()" class="burger-link burger-logout">Déconnexion</a></li>
            </ul>
        </div>
    </nav>
    <?php else: ?>
    <!-- Bouton retour simple pour utilisateurs non connectés -->
    <a href="/index.html" class="back-arrow">←</a>
    <?php endif; ?>

    <div class="legal-container">
        <h1>Mentions légales</h1>

        <section class="legal-section">
            <h2>1. Éditeur du site</h2>
            <p><strong>Nom du site :</strong> StudTraj</p>
            <p><strong>Responsable de publication :</strong> L'équipe StudTraj</p>
            <p><strong>Adresse :</strong> Chez nous</p>
            <p><strong>Email :</strong> StudTraj.amu@gmail.com</p>
            <p><strong>Téléphone :</strong> +330123456789</p>
        </section>

        <section class="legal-section">
            <h2>2. Hébergement</h2>
            <p><strong>Hébergeur :</strong> Alwaysdata</p>
            <p><strong>Adresse :</strong> A Paris je crois</p>
            <p><strong>Téléphone :</strong> +330123456789</p>
        </section>

        <section class="legal-section">
            <h2>3. Propriété intellectuelle</h2>
            <p>L'ensemble de ce site relève de la législation française et internationale sur le droit d'auteur et la propriété intellectuelle. Tous les droits de reproduction sont réservés, y compris pour les documents téléchargeables et les représentations iconographiques et photographiques.</p>
            <p>La reproduction de tout ou partie de ce site sur un support électronique quel qu'il soit est formellement interdite sauf autorisation expresse du directeur de la publication.</p>
        </section>

        <section class="legal-section">
            <h2>4. Protection des données personnelles</h2>
            <p>Conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés, vous disposez d'un droit d'accès, de rectification, de suppression et d'opposition aux données personnelles vous concernant.</p>
            <p>Pour exercer ces droits, vous pouvez nous contacter à l'adresse email suivante : studtraj.amu@gmail.com</p>
            <p>Les données collectées sur ce site sont utilisées uniquement dans le cadre du service proposé et ne sont en aucun cas cédées à des tiers.</p>
        </section>

        <section class="legal-section">
            <h2>5. Cookies</h2>
            <p>Ce site utilise des cookies techniques nécessaires à son bon fonctionnement, notamment pour la gestion des sessions utilisateur.</p>
            <p>Ces cookies ne collectent aucune information personnelle et ne sont pas utilisés à des fins publicitaires.</p>
        </section>

        <section class="legal-section">
            <h2>6. Liens hypertextes</h2>
            <p>Les liens hypertextes mis en place dans le cadre du présent site internet en direction d'autres sites et/ou de pages personnelles et d'une manière générale vers toutes ressources existantes sur Internet ne sauraient engager la responsabilité de l'éditeur.</p>
        </section>

        <section class="legal-section">
            <h2>7. Limitation de responsabilité</h2>
            <p>L'éditeur s'efforce d'assurer l'exactitude et la mise à jour des informations diffusées sur ce site. Toutefois, il ne peut garantir l'exactitude, la précision ou l'exhaustivité des informations mises à disposition sur ce site.</p>
            <p>En conséquence, l'éditeur décline toute responsabilité pour toute imprécision, inexactitude ou omission portant sur des informations disponibles sur ce site.</p>
        </section>

        <section class="legal-section">
            <h2>8. Droit applicable</h2>
            <p>Le présent site et les mentions légales sont régis par le droit français. En cas de litige et à défaut d'accord amiable, le litige sera porté devant les tribunaux français conformément aux règles de compétence en vigueur.</p>
        </section>

        <div class="last-updated">
            <p><em>Dernière mise à jour : <?php echo date('d/m/Y'); ?></em></p>
        </div>
    </div>

    <!-- Modal Plan du site -->
    <?php if ($isLoggedIn): ?>
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
    <?php endif; ?>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/views/partials/footer.php'; ?>

    <script>
        // Fonctions pour le menu burger
        function toggleBurgerMenu() {
            const burgerNav = document.getElementById('burgerNav');
            const burgerBtn = document.getElementById('burgerBtn');
            if (burgerNav && burgerBtn) {
                burgerNav.classList.toggle('active');
                burgerBtn.classList.toggle('active');
            }
        }

        // Fonction pour ouvrir le sitemap
        function openSiteMap() {
            const modal = document.getElementById('sitemapModal');
            if (modal) {
                modal.style.display = 'block';
            }
        }

        // Fonction pour fermer le sitemap
        function closeSiteMap() {
            const modal = document.getElementById('sitemapModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // Fonction de déconnexion
        function confirmLogout() {
            if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                window.location.href = '/index.php?action=logout';
            }
        }

        // Fermer les modals en cliquant en dehors
        window.onclick = function(event) {
            const sitemapModal = document.getElementById('sitemapMap');
            if (event.target === sitemapModal) {
                closeSiteMap();
            }
        }
    </script>
</body>
</html>
