<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id'])) {
    header('Location: /SAE/index.php?action=login');
    exit;
}

require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Resource.php';
require_once __DIR__ . '/../../models/Exercise.php';

$db = Database::getConnection();

$user_id = $_SESSION['id'];
$user_firstname = $_SESSION['user_firstname'] ?? 'Utilisateur';
$user_lastname = $_SESSION['user_lastname'] ?? '';

$resourceId = $_GET['id'] ?? null;

if (!$resourceId || !is_numeric($resourceId)) {
    header('Location: /index.php?action=resources_list');
    exit;
}

$resource = Resource::getResourceById($db, (int)$resourceId);

if (!$resource) {
    header('Location: /index.php?action=resources_list');
    exit;
}

// Vérifier les permissions d'accès à la ressource
$hasAccess = false;
if ($resource->owner_user_id === $user_id) {
    $hasAccess = true;
} else {
    $stmt = $db->prepare(
        "SELECT 1 FROM resource_professors_access 
         WHERE resource_id = :resourceId AND user_id = :userId"
    );
    $stmt->execute(['resourceId' => $resourceId, 'userId' => $user_id]);
    if ($stmt->fetch()) {
        $hasAccess = true;
    }
}

if (!$hasAccess) {
    header('Location: /index.php?action=resources_list&error=access_denied');
    exit;
}

$exercises = Exercise::getExercisesByResourceId($db, (int)$resourceId);

$title = htmlspecialchars($resource->resource_name) . ' - TPs';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="/public/images/favicon.ico">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="stylesheet" href="/public/css/dashboard.css">
    <link rel="stylesheet" href="/public/css/footer.css">
    <script src="/public/js/resources_list.js" defer></script>

    <meta name="description"
          content="Liste des Travaux Pratiques pour la ressource <?=
              htmlspecialchars($resource->resource_name) ?>.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical"
          href="http://studtraj.alwaysdata.net/views/user/resource_details.php?id=<?=
              $resource->resource_id ?>">
    <style>
        /* Styles des TPs */
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
        .tp-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px dashed #eee;
        }
        .tp-item:last-child {
            border-bottom: none;
        }
        .tp-item-info h3 {
            margin: 0;
            font-size: 1.1em;
            color: #555;
        }
        .tp-item-info p {
            margin: 5px 0 0;
            font-size: 0.9em;
            color: #777;
        }
        .tp-item-actions .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            text-decoration: none;
            color: #fff;
            background-color: #007bff;
            transition: background-color 0.2s;
        }
        .tp-item-actions .btn:hover {
            background-color: #0056b3;
        }
        .resource-details-header {
            padding: 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e2e6ea;
            text-align: center;
            margin-bottom: 20px;
        }
        .resource-details-header h1 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .resource-details-header p {
            color: #666;
            font-size: 1.1em;
            max-width: 800px;
            margin: 0 auto;
        }
        .resource-details-header .owner-info {
            font-style: italic;
            color: #888;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <!-- Menu du haut -->
    <header class="top-menu">
        <div class="logo">
            <h1>StudTraj</h1>
        </div>
        <button class="burger-menu" id="burgerBtn"
                onclick="toggleBurgerMenu()" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
        <nav class="nav-menu">
            <a href="/index.php?action=dashboard">Tableau de bord</a>
            <a href="/index.php?action=resources_list" class="active">Mes Ressources</a>
            <a href="#" onclick="openSiteMap()">Plan du site</a>
            <a href="/index.php?action=mentions">Mentions légales</a>
        </nav>
        <div class="user-info">
            <span>
                <?= htmlspecialchars($user_firstname) ?>
                <?= htmlspecialchars($user_lastname) ?>
            </span>
            <button onclick="confirmLogout()" class="btn-logout">Déconnexion</button>
        </div>
    </header>

    <!-- Menu burger mobile -->
    <nav class="burger-nav" id="burgerNav">
        <div class="burger-nav-content">
            <div class="burger-user-info">
                <span>
                    <?= htmlspecialchars($user_firstname) ?>
                    <?= htmlspecialchars($user_lastname) ?>
                </span>
            </div>
            <ul class="burger-menu-list">
                <li>
                    <a href="/index.php?action=dashboard" class="burger-link">
                        Tableau de bord
                    </a>
                </li>
                <li>
                    <a href="/index.php?action=resources_list"
                       class="burger-link active">
                        Mes Ressources
                    </a>
                </li>
                <li>
                    <a href="/index.php?action=mentions" class="burger-link">
                        Mentions légales
                    </a>
                </li>
                <li>
                    <a href="#" onclick="confirmLogout()"
                       class="burger-link burger-logout">
                        Déconnexion
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="main-content">
        <div class="resource-details-header">
            <h1><?= htmlspecialchars($resource->resource_name) ?></h1>
            <p><?= htmlspecialchars(
                $resource->description ?? 'Pas de description pour cette ressource.'
            ) ?></p>
            <?php
            $ownerFullName = $resource->owner_firstname . ' ' .
                            $resource->owner_lastname;
            ?>
            <div class="owner-info">
                Créée par <?= htmlspecialchars($ownerFullName) ?>
            </div>
        </div>

        <div class="tp-list-container">
            <h2>Travaux Pratiques</h2>
            <?php if (!empty($exercises)) : ?>
                <?php foreach ($exercises as $exercise) : ?>
                    <div class="tp-item">
                        <div class="tp-item-info">
                            <h3><?= htmlspecialchars($exercise->exo_name) ?></h3>
                            <p>
                                Difficulté: <?= htmlspecialchars($exercise->difficulte ?? 'Non spécifiée') ?>
                            </p>
                        </div>
                        <div class="tp-item-actions">
                            <a href="/index.php?action=exercise_details&id=<?=
                                       $exercise->exercise_id ?>"
                               class="btn">Voir le TP</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p>Aucun travail pratique disponible pour cette ressource.</p>
            <?php endif; ?>
        </div>
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
                    <li>
                        <a href="/index.php?action=forgotpassword">
                            Mot de passe oublié
                        </a>
                    </li>
                    <li><a href="/index.php?action=mentions">Mentions légales</a></li>
                </ul>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../layouts/footer.php'; ?>
    <script>
        function toggleBurgerMenu() {
            const burgerNav = document.getElementById('burgerNav');
            burgerNav.classList.toggle('active');
            document.getElementById('burgerBtn').classList.toggle('open');
        }

        function openSiteMap() {
            document.getElementById('sitemapModal').style.display = "block";
        }

        function closeSiteMap() {
            document.getElementById('sitemapModal').style.display = "none";
        }

        function confirmLogout() {
            if (confirm("Voulez-vous vraiment vous déconnecter ?")) {
                window.location.href = "/index.php?action=logout";
            }
        }
    </script>
</body>
</html>