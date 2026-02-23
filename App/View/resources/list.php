<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_firstname = $_SESSION['prenom'] ?? 'Utilisateur';
$user_lastname  = $_SESSION['nom'] ?? '';
$initials = strtoupper(substr($user_firstname, 0, 1) . substr($user_lastname, 0, 1));
$title = 'StudTraj - Mes Ressources';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/favicon.ico">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/footer.css">
    <script type="module" src="<?= BASE_URL ?>/public/js/dashboard-main.js"></script>
    <meta name="description" content="Liste des ressources disponibles sur StudTraj.">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
<header class="top-menu">
    <div class="logo"><h1>StudTraj</h1></div>

    <button class="burger-menu" id="burgerBtn" onclick="toggleBurgerMenu()" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>

    <nav class="nav-menu">
        <a href="<?= BASE_URL ?>/index.php?action=resources_list" class="active">Ressources</a>
        <a href="<?= BASE_URL ?>/index.php?action=exercises">Exercices</a>
    </nav>

    <div class="header-right">
        <div class="user-profile">
            <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
            <span><?= htmlspecialchars($user_firstname) ?> <?= htmlspecialchars($user_lastname) ?></span>
        </div>
        <a href="<?= BASE_URL ?>/index.php?action=logout" class="btn-logout">
            <svg style="width:16px; height:16px;" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span class="logout-text">Déconnexion</span>
        </a>
    </div>
</header>

<nav class="burger-nav" id="burgerNav">
    <button class="burger-menu burger-close-internal active"
            onclick="toggleBurgerMenu()" aria-label="Fermer le menu">
        <span></span><span></span><span></span>
    </button>
    <div class="burger-nav-content">
        <div class="burger-user-info">
            <span><?= htmlspecialchars($user_firstname) ?> <?= htmlspecialchars($user_lastname) ?></span>
        </div>
        <ul class="burger-menu-list">
            <li><a href="<?= BASE_URL ?>/index.php?action=resources_list" class="burger-link active">Ressources</a></li>
            <li><a href="<?= BASE_URL ?>/index.php?action=exercises" class="burger-link">Exercices</a></li>
            <li><a href="#" onclick="confirmLogout()" class="burger-link burger-logout">Déconnexion</a></li>
        </ul>
    </div>
</nav>

<div class="dashboard-container">
    <main class="main-content">
        <h2 style="padding: 20px 20px 0;">Mes Ressources</h2>

        <?php if (!empty($owned_resources)) : ?>
            <h3 style="padding: 10px 20px 0; color:#555;">Mes créations</h3>
            <div class="resources-grid" style="padding: 10px 20px;">
                <?php foreach ($owned_resources as $resource) : ?>
                    <div class="resource-card">
                        <?php if ($resource->getImagePath()) : ?>
                            <img src="/images/<?= htmlspecialchars($resource->getImagePath()) ?>"
                                 class="resource-card-image"
                                 alt="<?= htmlspecialchars($resource->getResourceName()) ?>">
                        <?php else : ?>
                            <div class="resource-card-image"
                                 style="background:#eee; display:flex; align-items:center;
                                        justify-content:center; color:#777;">
                                Pas d'image
                            </div>
                        <?php endif; ?>
                        <div class="resource-card-content">
                            <h3><?= htmlspecialchars($resource->getResourceName()) ?></h3>
                            <?php if ($resource->getDescription()) : ?>
                                <p><?= htmlspecialchars($resource->getDescription()) ?></p>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/index.php?action=resource_details&id=<?= (int)$resource->getResourceId() ?>"
                               class="resource-link-wrapper">Voir les détails</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($shared_resources)) : ?>
            <h3 style="padding: 10px 20px 0; color:#555;">Partagées avec moi</h3>
            <div class="resources-grid" style="padding: 10px 20px;">
                <?php foreach ($shared_resources as $resource) : ?>
                    <div class="resource-card">
                        <?php if ($resource->getImagePath()) : ?>
                            <img src="/images/<?= htmlspecialchars($resource->getImagePath()) ?>"
                                 class="resource-card-image"
                                 alt="<?= htmlspecialchars($resource->getResourceName()) ?>">
                        <?php else : ?>
                            <div class="resource-card-image"
                                 style="background:#eee; display:flex; align-items:center;
                                        justify-content:center; color:#777;">
                                Pas d'image
                            </div>
                        <?php endif; ?>
                        <div class="resource-card-content">
                            <h3><?= htmlspecialchars($resource->getResourceName()) ?></h3>
                            <?php if ($resource->getDescription()) : ?>
                                <p><?= htmlspecialchars($resource->getDescription()) ?></p>
                            <?php endif; ?>
                            <?php if ($resource->getOwnerFirstname() || $resource->getOwnerLastname()) : ?>
                                <span class="resource-card-owner">
                                    Par : <?= htmlspecialchars($resource->getOwnerFullName()) ?>
                                </span>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/index.php?action=resource_details&id=<?= (int)$resource->getResourceId() ?>"
                               class="resource-link-wrapper">Voir les détails</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($owned_resources) && empty($shared_resources)) : ?>
            <p style="padding:20px;">Aucune ressource trouvée.</p>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>
