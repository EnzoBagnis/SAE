<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_firstname = $_SESSION['prenom'] ?? 'Utilisateur';
$user_lastname  = $_SESSION['nom'] ?? '';
$initials = strtoupper(substr($user_firstname, 0, 1) . substr($user_lastname, 0, 1));
$exoTitle = isset($exercise) ? htmlspecialchars($exercise->getExoName()) : 'Exercice';
$title = 'StudTraj - ' . $exoTitle;
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
    <meta name="description" content="Détail de l'exercice <?= $exoTitle ?>.">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
<header class="top-menu">
    <div class="logo"><h1>StudTraj</h1></div>

    <button class="burger-menu" id="burgerBtn" onclick="toggleBurgerMenu()" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>

    <nav class="nav-menu">
        <a href="<?= BASE_URL ?>/index.php?action=resources_list">Ressources</a>
        <a href="<?= BASE_URL ?>/index.php?action=exercises" class="active">Exercices</a>
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
            <li><a href="<?= BASE_URL ?>/index.php?action=resources_list" class="burger-link">Ressources</a></li>
            <li><a href="<?= BASE_URL ?>/index.php?action=exercises" class="burger-link active">Exercices</a></li>
            <li><a href="#" onclick="confirmLogout()" class="burger-link burger-logout">Déconnexion</a></li>
        </ul>
    </div>
</nav>

<div class="dashboard-container">
    <main class="main-content">
        <div style="padding: 20px 20px 0;">
            <a href="<?= BASE_URL ?>/index.php?action=exercises"
               style="color:#666; text-decoration:none; font-size:.9em;">
                &larr; Retour aux exercices
            </a>
            <h2 style="margin-top:10px;"><?= $exoTitle ?></h2>
        </div>

        <div class="tp-list-container">
            <?php if ($exercise->getDescription()) : ?>
                <div style="margin-bottom:20px;">
                    <h3>Description</h3>
                    <p><?= nl2br(htmlspecialchars($exercise->getDescription())) ?></p>
                </div>
            <?php endif; ?>

            <ul style="list-style:none; padding:0;">
                <?php if ($exercise->getDifficulte()) : ?>
                    <li style="padding:8px 0; border-bottom:1px dashed #eee;">
                        <strong>Difficulté :</strong> <?= htmlspecialchars($exercise->getDifficulte()) ?>
                    </li>
                <?php endif; ?>
                <?php if ($exercise->getFuncname()) : ?>
                    <li style="padding:8px 0; border-bottom:1px dashed #eee;">
                        <strong>Fonction :</strong>
                        <code><?= htmlspecialchars($exercise->getFuncname()) ?></code>
                    </li>
                <?php endif; ?>
                <?php if ($exercise->getDateCreation()) : ?>
                    <li style="padding:8px 0; border-bottom:1px dashed #eee;">
                        <strong>Créé le :</strong> <?= htmlspecialchars($exercise->getDateCreation()) ?>
                    </li>
                <?php endif; ?>
                <li style="padding:8px 0;">
                    <strong>Ressource :</strong>
                    <a href="<?= BASE_URL ?>/index.php?action=resource_details
                        &id=<?= (int)$exercise->getResourceId() ?>">
                        Voir la ressource associée
                    </a>
                </li>
            </ul>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>
