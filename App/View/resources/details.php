<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_firstname = $_SESSION['user_firstname'] ?? 'Utilisateur';
$user_lastname  = $_SESSION['user_lastname']  ?? '';
$initials = strtoupper(substr($user_firstname, 0, 1) . substr($user_lastname, 0, 1));
$resTitle = isset($resource) ? htmlspecialchars($resource->getResourceName()) : 'Ressource';
$title = 'StudTraj - ' . $resTitle;
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
    <meta name="description" content="Détails de la ressource <?= $resTitle ?>.">
    <meta name="robots" content="noindex, nofollow">
    <style>
        .tp-list-container {
            padding: 20px;
            max-width: 900px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .tp-list-container h2 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .resource-details-header {
            padding: 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e2e6ea;
            text-align: center;
            margin-bottom: 20px;
        }
        .resource-details-header h1 { margin: 0 0 10px 0; color: #333; }
        .resource-details-header p { color: #666; font-size: 1.1em; max-width: 800px; margin: 0 auto; }
        .resource-details-header .owner-info { font-style: italic; color: #888; margin-top: 10px; }
    </style>
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

<div class="main-content">
    <div style="padding: 20px 20px 0;">
        <a href="<?= BASE_URL ?>/index.php?action=resources_list"
           style="color:#666; text-decoration:none; font-size:.9em;">
            &larr; Retour aux ressources
        </a>
    </div>

    <div class="resource-details-header">
        <h1><?= $resTitle ?></h1>
        <?php if ($resource->getDescription()) : ?>
            <p><?= htmlspecialchars($resource->getDescription()) ?></p>
        <?php endif; ?>
        <?php if ($resource->getOwnerFirstname() || $resource->getOwnerLastname()) : ?>
            <div class="owner-info">
                Créée par <?= htmlspecialchars($resource->getOwnerFullName()) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="tp-list-container">
        <h2>Exercices associés</h2>
        <p>
            <a href="<?= BASE_URL ?>/index.php?action=exercises&resource_id=<?= (int)$resource->getResourceId() ?>"
               class="btn" style="display:inline-block; padding:8px 16px; background:#007bff;
               color:#fff; border-radius:4px; text-decoration:none;">
                Voir les exercices de cette ressource
            </a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>

