<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_firstname = $_SESSION['prenom'] ?? 'Utilisateur';
$user_lastname  = $_SESSION['nom'] ?? '';
$initials = strtoupper(substr($user_firstname, 0, 1) . substr($user_lastname, 0, 1));
$title = 'StudTraj - Exercices';
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
    <meta name="description" content="Liste des exercices disponibles sur StudTraj.">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
<header class="top-menu">
    <div class="logo"><h1>StudTraj</h1></div>

    <button class="burger-menu" id="burgerBtn" onclick="toggleBurgerMenu()" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>


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
        <div style="padding: 20px 20px 0; display:flex; align-items:center; gap:12px;">
            <h2>Exercices</h2>
            <span style="background:#e9ecef; padding:4px 10px; border-radius:12px; font-size:.9em;">
                <?= (int)($total ?? 0) ?> exercice(s)
            </span>
        </div>

        <div style="padding: 20px;">
            <?php if (empty($exercises)) : ?>
                <p>Aucun exercice disponible pour le moment.</p>
            <?php else : ?>
                <div class="resources-grid">
                    <?php foreach ($exercises as $exercise) : ?>
                        <div class="resource-card">
                            <div class="resource-card-content">
                                <h3><?= htmlspecialchars($exercise->getExoName()) ?></h3>
                                <?php if ($exercise->getDescription()) : ?>
                                    <p><?= htmlspecialchars($exercise->getDescription()) ?></p>
                                <?php endif; ?>
                                <?php if ($exercise->getDifficulte()) : ?>
                                    <span style="font-size:.85em; color:#666;">
                                        Difficulté : <?= htmlspecialchars($exercise->getDifficulte()) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($exercise->getFuncname()) : ?>
                                    <p style="font-size:.85em; margin-top:6px;">
                                        Fonction : <code><?= htmlspecialchars($exercise->getFuncname()) ?></code>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>
