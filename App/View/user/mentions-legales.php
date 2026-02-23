<?php
// V├®rifier si l'utilisateur est connect├®
$isLoggedIn = isset($_SESSION['id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/favicon.ico">
    <title>StudTraj - Mentions l├®gales</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/mentions-legales.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/footer.css">
    <!-- SEO Meta Tags -->
    <meta name="mentions-legales" content="Je vous assure qu'on est ici en toute l├®galit├®.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="http://studtraj.alwaysdata.net/views/mentions-legales.php">
</head>
<body<?php echo $isLoggedIn ? ' class="logged-in"' : ''; ?>>
<?php if ($isLoggedIn) : ?>
    <!-- Menu du haut pour utilisateurs connect├®s -->
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
        </nav>
        <div class="user-info">
            <span>
                <?php echo htmlspecialchars($_SESSION['prenom']); ?>
                <?php echo htmlspecialchars($_SESSION['nom']); ?>
            </span>
            <button onclick="confirmLogout()" class="btn-logout">D├®connexion</button>
        </div>
    </header>

    <!-- Menu burger mobile -->
    <nav class="burger-nav" id="burgerNav">
        <div class="burger-nav-content">
            <div class="burger-user-info">
                <span>
                    <?php echo htmlspecialchars($_SESSION['prenom']); ?>
                    <?php echo htmlspecialchars($_SESSION['nom']); ?>
                </span>
            </div>
            <ul class="burger-menu-list">
                <li><a href="#" onclick="confirmLogout()" class="burger-link burger-logout">D├®connexion</a></li>
            </ul>
        </div>
    </nav>
<?php endif; ?>

<div class="legal-container">
    <a href="javascript:void(0)"
       onclick="if(window.history.length > 1){
           window.history.back();
       }else{
           window.location.href='<?= BASE_URL ?>/index.php';
       }"
       class="back-link"
       style="display: inline-block; margin-bottom: 20px; text-decoration: none;
              color: inherit; font-weight: bold; font-size: 1.2em;">
        ÔåÉ Retour
    </a>
    <h1>Mentions l├®gales</h1>

    <section class="legal-section">
        <h2>1. ├ëditeur du site</h2>
        <p><strong>Nom du site :</strong> StudTraj</p>
        <p><strong>Responsable de publication :</strong> L'├®quipe StudTraj</p>
        <p><strong>Adresse :</strong> Chez nous</p>
        <p><strong>Email :</strong> StudTraj.amu@gmail.com</p>
        <p><strong>T├®l├®phone :</strong> +330123456789</p>
    </section>

    <section class="legal-section">
        <h2>2. H├®bergement</h2>
        <p><strong>H├®bergeur :</strong> Alwaysdata</p>
        <p><strong>Adresse :</strong> A Paris je crois</p>
        <p><strong>T├®l├®phone :</strong> +330123456789</p>
    </section>

    <section class="legal-section">
        <h2>3. Propri├®t├® intellectuelle</h2>
        <p>L'ensemble de ce site rel├¿ve de la l├®gislation fran├ºaise et internationale sur le droit d'auteur
        et la propri├®t├® intellectuelle. Tous les droits de reproduction sont r├®serv├®s, y compris pour les documents
        t├®l├®chargeables et les repr├®sentations iconographiques et photographiques.</p>
        <p>La reproduction de tout ou partie de ce site sur un support ├®lectronique quel qu'il soit est
        formellement interdite sauf autorisation expresse du directeur de la publication.</p>
    </section>

    <section class="legal-section">
        <h2>4. Protection des donn├®es personnelles</h2>
        <p>Conform├®ment au R├¿glement G├®n├®ral sur la Protection des Donn├®es (RGPD) et ├á la loi Informatique et Libert├®s,
        vous disposez d'un droit d'acc├¿s, de rectification, de suppression et d'opposition aux donn├®es personnelles
        vous concernant.</p>
        <p>Pour exercer ces droits, vous pouvez nous contacter ├á l'adresse email suivante : studtraj.amu@gmail.com</p>
        <p>Les donn├®es collect├®es sur ce site sont utilis├®es uniquement dans le cadre du service propos├® et ne sont
        en aucun cas c├®d├®es ├á des tiers.</p>
    </section>

    <section class="legal-section">
        <h2>5. Cookies</h2>
        <p>Ce site utilise des cookies techniques n├®cessaires ├á son bon fonctionnement, notamment pour la gestion
        des sessions utilisateur.</p>
        <p>Ces cookies ne collectent aucune information personnelle et ne sont pas utilis├®s ├á des fins
        publicitaires.</p>
    </section>

    <section class="legal-section">
        <h2>6. Liens hypertextes</h2>
        <p>Les liens hypertextes mis en place dans le cadre du pr├®sent site internet en direction d'autres sites
        et/ou de pages personnelles et d'une mani├¿re g├®n├®rale vers toutes ressources existantes sur Internet
        ne sauraient engager la responsabilit├® de l'├®diteur.</p>
    </section>

    <section class="legal-section">
        <h2>7. Limitation de responsabilit├®</h2>
        <p>L'├®diteur s'efforce d'assurer l'exactitude et la mise ├á jour des informations diffus├®es sur ce site.
        Toutefois, il ne peut garantir l'exactitude, la pr├®cision ou l'exhaustivit├® des informations mises ├á
        disposition sur ce site.</p>
        <p>En cons├®quence, l'├®diteur d├®cline toute responsabilit├® pour toute impr├®cision, inexactitude ou omission
        portant sur des informations disponibles sur ce site.</p>
    </section>

    <section class="legal-section">
        <h2>8. Droit applicable</h2>
        <p>Le pr├®sent site et les mentions l├®gales sont r├®gis par le droit fran├ºais. En cas de litige et ├á d├®faut
        d'accord amiable, le litige sera port├® devant les tribunaux fran├ºais conform├®ment aux r├¿gles de comp├®tence
        en vigueur.</p>
    </section>

    <div class="last-updated">
        <p><em>Derni├¿re mise ├á jour : <?php echo date('d/m/Y'); ?></em></p>
    </div>
</div>

<!-- Modal Plan du site -->
<?php if ($isLoggedIn) : ?>
    <div id="sitemapModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeSiteMap()">&times;</span>
            <h2>Plan du site</h2>
            <div class="sitemap-list">
                <ul>
                    <li><a href="<?= BASE_URL ?>/index.php?action=dashboard">Tableau de bord</a></li>
                    <li><a href="<?= BASE_URL ?>/index.php?action=login">Connexion</a></li>
                    <li><a href="<?= BASE_URL ?>/index.php?action=signup">Inscription</a></li>
                    <li><a href="<?= BASE_URL ?>/index.php?action=forgotpassword">Mot de passe oubli├®</a></li>
                    <li><a href="<?= BASE_URL ?>/index.php?action=mentions">Mentions l├®gales</a></li>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

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

    // Fonction de d├®connexion
    function confirmLogout() {
        if (confirm('├ètes-vous s├╗r de vouloir vous d├®connecter ?')) {
            window.location.href = '<?= BASE_URL ?>/index.php?action=logout';
        }
    }

    // Fermer les modals en cliquant en dehors
    window.onclick = function(event) {
        const sitemapModal = document.getElementById('sitemapModal');
        if (event.target === sitemapModal) {
            closeSiteMap();
        }
    }
</script>
</body>
</html>