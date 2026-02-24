<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('BASE_URL')) {
    define('BASE_URL', '');
}

$user_firstname = $_SESSION['user_firstname'] ?? ($_SESSION['name'] ?? 'Utilisateur');
$user_lastname  = $_SESSION['user_lastname'] ?? ($_SESSION['surname'] ?? '');
$initials = strtoupper(substr($user_firstname, 0, 1) . substr($user_lastname, 0, 1));
$title = 'StudTraj - Mes Ressources';

// Search / filter from GET
$search = htmlspecialchars(trim($_GET['search'] ?? ''));
$filter = $_GET['filter'] ?? 'all';

// Apply search/filter in PHP
$allResources = array_merge($owned_resources ?? [], $shared_resources ?? []);
if ($search !== '') {
    $allResources = array_filter(
        $allResources,
        fn($r) => stripos($r->getResourceName(), $search) !== false
            || stripos((string)$r->getDescription(), $search) !== false
    );
}
if ($filter === 'mine') {
    $allResources = array_filter($allResources, fn($r) => $r->getAccessType() === 'owner');
} elseif ($filter === 'shared') {
    $allResources = array_filter($allResources, fn($r) => $r->getAccessType() === 'shared');
}
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
    <style>
        /* ── Toolbar ── */
        .resources-toolbar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px 24px;
            background: #eef1fb;
            border-radius: 8px;
            margin: 20px 20px 0;
            flex-wrap: wrap;
        }
        .resources-toolbar input[type="text"] {
            flex: 1;
            min-width: 180px;
            padding: 9px 14px;
            border: 1px solid #cdd4e0;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
        }
        .resources-toolbar select {
            padding: 9px 14px;
            border: 1px solid #cdd4e0;
            border-radius: 6px;
            font-size: 14px;
            background: #fff;
            outline: none;
        }
        .btn-new-resource {
            margin-left: auto;
            padding: 9px 20px;
            background: #2196f3;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-new-resource:hover { background: #1976d2; }

        /* ── Resource cards grid ── */
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .resource-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .resource-card-image {
            width: 100%;
            height: 140px;
            object-fit: cover;
        }
        .resource-card-content {
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }
        .resource-card-content h3 { margin: 0; font-size: 16px; }
        .resource-card-content p  { margin: 0; font-size: 13px; color: #555; }
        .resource-link-wrapper {
            margin-top: auto;
            color: #2196f3;
            font-size: 13px;
            text-decoration: none;
            font-weight: 600;
        }
        .resource-card-owner { font-size: 12px; color: #888; }
        .resource-badge {
            display: inline-block;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 600;
        }
        .badge-owner  { background: #e3f2fd; color: #1565c0; }
        .badge-shared { background: #fce4ec; color: #880e4f; }
        .empty-msg { padding: 30px 20px; color: #777; text-align: center; }
    </style>
</head>
<body>
<header class="top-menu">
    <div class="logo"><h1>StudTraj</h1></div>
    <button class="burger-menu" id="burgerBtn" onclick="toggleBurgerMenu()" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
    <nav class="nav-menu">
        <a href="<?= BASE_URL ?>/resources" class="active">Ressources</a>
    </nav>
    <div class="header-right">
        <div class="user-profile">
            <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
            <span><?= htmlspecialchars($user_firstname) ?> <?= htmlspecialchars($user_lastname) ?></span>
        </div>
        <a href="<?= BASE_URL ?>/auth/logout" class="btn-logout">
            <svg style="width:16px;height:16px;" viewBox="0 0 24 24" fill="none"
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
            <li><a href="<?= BASE_URL ?>/resources" class="burger-link active">Ressources</a></li>
            <li><a href="#" onclick="confirmLogout()" class="burger-link burger-logout">Déconnexion</a></li>
        </ul>
    </div>
</nav>

<div class="dashboard-container">
    <main class="main-content">
        <h2 style="padding: 20px 20px 0; text-align:center;">Tableau de bord</h2>

        <!-- Toolbar : recherche + filtre + bouton -->
        <form method="get" action="<?= BASE_URL ?>/resources" class="resources-toolbar">
            <input type="text" name="search" placeholder="Rechercher..."
                   value="<?= htmlspecialchars($search) ?>">
            <select name="filter">
                <option value="all"    <?= $filter === 'all'    ? 'selected' : '' ?>>Tout voir</option>
                <option value="mine"   <?= $filter === 'mine'   ? 'selected' : '' ?>>Mes ressources</option>
                <option value="shared" <?= $filter === 'shared' ? 'selected' : '' ?>>Partagées</option>
            </select>
            <button type="submit" style="display:none;"></button>
            <a href="<?= BASE_URL ?>/resources/create" class="btn-new-resource">
                + Nouvelle Ressource
            </a>
        </form>

        <!-- Grille de ressources -->
        <?php if (!empty($allResources)) : ?>
            <div class="resources-grid">
                <?php foreach ($allResources as $resource) : ?>
                    <div class="resource-card">
                        <?php if ($resource->getImagePath()) : ?>
                            <img src="<?= htmlspecialchars($resource->getImagePath()) ?>"
                                 class="resource-card-image"
                                 alt="<?= htmlspecialchars($resource->getResourceName()) ?>">
                        <?php else : ?>
                            <div class="resource-card-image"
                                 style="background:#e8eaf6;display:flex;align-items:center;
                                        justify-content:center;color:#9fa8da;font-size:13px;">
                                Pas d'image
                            </div>
                        <?php endif; ?>
                        <div class="resource-card-content">
                            <span class="resource-badge <?= $resource->getAccessType() === 'owner'
                                ? 'badge-owner' : 'badge-shared' ?>">
                                <?= $resource->getAccessType() === 'owner' ? 'Ma ressource' : 'Partagée' ?>
                            </span>
                            <h3><?= htmlspecialchars($resource->getResourceName()) ?></h3>
                            <?php if ($resource->getDescription()) : ?>
                                <p><?= htmlspecialchars($resource->getDescription()) ?></p>
                            <?php endif; ?>
                            <?php if ($resource->getAccessType() === 'shared') : ?>
                                <span class="resource-card-owner">
                                    Par : <?= htmlspecialchars($resource->getOwnerMail()) ?>
                                </span>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/resources/<?= (int)$resource->getResourceId() ?>"
                               class="resource-link-wrapper">Voir les détails →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p class="empty-msg">Aucune ressource trouvée.</p>
        <?php endif; ?>
    </main>
</div>

<script>
// Recherche en temps réel (soumet le formulaire à la frappe)
document.querySelector('input[name="search"]').addEventListener('input', function () {
    clearTimeout(this._t);
    this._t = setTimeout(() => this.closest('form').submit(), 400);
});
// Soumet le formulaire au changement de filtre
document.querySelector('select[name="filter"]').addEventListener('change', function () {
    this.closest('form').submit();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>
