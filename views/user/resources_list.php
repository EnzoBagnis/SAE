<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /SAE/index.php?action=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Resource.php';

$db = connectDB();

$user_id = $_SESSION['user_id'];
$user_firstname = $_SESSION['user_firstname'] ?? 'Utilisateur';
$user_lastname = $_SESSION['user_lastname'] ?? '';
// $user_role n'est plus nécessaire ici

$title = 'StudTraj - Mes Ressources';

$resources = Resource::getAllAccessibleResources($db, $user_id);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="/SAE/public/images/favicon.ico">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="/SAE/public/css/style.css">
    <link rel="stylesheet" href="/SAE/public/css/dashboard.css">
    <link rel="stylesheet" href="/SAE/public/css/footer.css">
    <script src="/SAE/public/js/resources_list.js" defer></script>

    <meta name="description" content="Gérez et visualisez vos ressources pédagogiques et celles qui vous sont partagées.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="http://studtraj.alwaysdata.net/views/user/resources_list.php">
    <style>
        /* Styles pour les cartes de ressources (inchangés) */
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            padding: 20px;
            max-width: 1200px;
            margin: 20px auto;
        }

        .resource-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            display: flex;
            flex-direction: column;
        }

        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .resource-card-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background-color: #f0f0f0;
            display: block;
        }
        .resource-card-image.placeholder {
            background-image: url('/SAE/public/images/placeholder_resource.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .resource-card-content {
            padding: 15px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .resource-card-content h3 {
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 1.2em;
            color: #333;
        }

        .resource-card-content p {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 10px;
            flex-grow: 1;
        }

        .resource-card-owner {
            font-size: 0.8em;
            color: #999;
            text-align: right;
        }

        .filter-bar {
            display: flex;
            justify-content: flex-start;
            gap: 10px;
            padding: 20px;
            background-color: #eef;
            border-bottom: 1px solid #ddd;
            flex-wrap: wrap;
        }

        .filter-bar select,
        .filter-bar input[type="text"] {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
        }
    </style>
</head>
<body>
    <!-- Menu du haut -->
    <header class="top-menu">
        <div class="logo">
            <h1>StudTraj</h1>
        </div>
        <button class="burger-menu" id="burgerBtn" onclick="toggleBurgerMenu()" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
        <nav class="nav-menu">
            <a href="/SAE/index.php?action=dashboard">Tableau de bord</a>
            <a href="/SAE/index.php?action=resources_list" class="active">Mes Ressources</a>
            <a href="#" onclick="openSiteMap()">Plan du site</a>
            <a href="/SAE/index.php?action=mentions">Mentions légales</a>
        </nav>
        <div class="user-info">
            <span><?= htmlspecialchars($user_firstname) ?> <?= htmlspecialchars($user_lastname) ?></span>
            <button onclick="confirmLogout()" class="btn-logout">Déconnexion</button>
        </div>
    </header>

    <!-- Menu burger mobile -->
    <nav class="burger-nav" id="burgerNav">
        <div class="burger-nav-content">
            <div class="burger-user-info">
                <span><?= htmlspecialchars($user_firstname) ?> <?= htmlspecialchars($user_lastname) ?></span>
            </div>
            <ul class="burger-menu-list">
                <li><a href="/SAE/index.php?action=dashboard" class="burger-link">Tableau de bord</a></li>
                <li><a href="/SAE/index.php?action=resources_list" class="burger-link active">Mes Ressources</a></li>
                <li><a href="/SAE/index.php?action=mentions" class="burger-link">Mentions légales</a></li>
                <li><a href="#" onclick="confirmLogout()" class="burger-link burger-logout">Déconnexion</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <main class="main-content">
            <h2 style="padding: 20px 20px 0;">Vos ressources et celles partagées</h2>

            <!-- Bar de filtres et recherche -->
            <div class="filter-bar">
                <select id="filterType" onchange="filterResources()">
                    <option value="all">Toutes les ressources</option>
                    <option value="owner">Mes ressources</option>
                    <option value="shared">Ressources partagées</option>
                </select>
                <input type="text" id="searchBar" placeholder="Rechercher..." onkeyup="filterResources()">
                <select id="sortOrder" onchange="filterResources()">
                    <option value="name_asc">Trier par nom (A-Z)</option>
                    <option value="name_desc">Trier par nom (Z-A)</option>
                    <option value="owner_name_asc">Trier par propriétaire (A-Z)</option>
                </select>
                <!-- <select id="filterCategory" onchange="filterResources()">
                    <option value="all">Catégorie</option>
                    <option value="Info">Informatique</option>
                    <option value="Math">Mathématiques</option>
                </select> -->
            </div>

            <div class="resources-grid" id="resourcesGrid">
                <?php if (!empty($resources)): ?>
                    <?php foreach ($resources as $resource): ?>
                        <a href="/SAE/index.php?action=resource_details&id=<?= $resource->resource_id ?>"
                           class="resource-card"
                           data-name="<?= htmlspecialchars($resource->resource_name) ?>"
                           data-owner="<?= htmlspecialchars($resource->owner_firstname . ' ' . $resource->owner_lastname) ?>"
                           data-access-type="<?= htmlspecialchars($resource->access_type) ?>"
                           >
                            <?php if (!empty($resource->image_path)): ?>
                                <img src="/SAE/public/images/<?= htmlspecialchars($resource->image_path) ?>" alt="Image de <?= htmlspecialchars($resource->resource_name) ?>" class="resource-card-image">
                            <?php else: ?>
                                <div class="resource-card-image placeholder"></div>
                            <?php endif; ?>
                            <div class="resource-card-content">
                                <h3><?= htmlspecialchars($resource->resource_name) ?></h3>
                                <p><?= htmlspecialchars($resource->description ?? 'Pas de description.') ?></p>
                                <span class="resource-card-owner">Par: <?= htmlspecialchars($resource->owner_firstname . ' ' . $resource->owner_lastname) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="placeholder-message">Aucune ressource disponible pour le moment.</p>
                <?php endif; ?>
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
                    <li><a href="/SAE/index.php?action=dashboard">Tableau de bord</a></li>
                    <li><a href="/SAE/index.php?action=login">Connexion</a></li>
                    <li><a href="/SAE/index.php?action=signup">Inscription</a></li>
                    <li><a href="/SAE/index.php?action=forgotpassword">Mot de passe oublié</a></li>
                    <li><a href="/SAE/index.php?action=mentions">Mentions légales</a></li>
                </ul>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../layouts/footer.php'; ?>

    <script>
        // Mettre cette fonction dans public/js/resources_list.js
        function filterResources() {
            const searchText = document.getElementById('searchBar').value.toLowerCase();
            const filterType = document.getElementById('filterType').value; // 'all', 'owner', 'shared'
            const sortOrder = document.getElementById('sortOrder').value;
            const grid = document.getElementById('resourcesGrid');
            let cards = Array.from(grid.getElementsByClassName('resource-card'));

            cards.forEach(card => {
                const name = card.dataset.name.toLowerCase();
                const owner = card.dataset.owner.toLowerCase();
                const accessType = card.dataset.accessType; // 'owner' ou 'shared'

                const matchesSearch = name.includes(searchText) || owner.includes(searchText);
                const matchesType = (filterType === 'all' || accessType === filterType);

                if (matchesSearch && matchesType) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });

            cards.sort((a, b) => {
                const nameA = a.dataset.name.toLowerCase();
                const nameB = b.dataset.name.toLowerCase();
                const ownerA = a.dataset.owner.toLowerCase();
                const ownerB = b.dataset.owner.toLowerCase();

                if (sortOrder === 'name_asc') {
                    return nameA.localeCompare(nameB);
                } else if (sortOrder === 'name_desc') {
                    return nameB.localeCompare(nameA);
                } else if (sortOrder === 'owner_name_asc') {
                    return ownerA.localeCompare(ownerB);
                }
                return 0;
            });

            cards.forEach(card => grid.appendChild(card));
        }

        document.addEventListener('DOMContentLoaded', () => {
            const filterTypeElement = document.getElementById('filterType');
            if (filterTypeElement) filterTypeElement.addEventListener('change', filterResources);

            const searchBarElement = document.getElementById('searchBar');
            if (searchBarElement) searchBarElement.addEventListener('keyup', filterResources);

            const sortOrderElement = document.getElementById('sortOrder');
            if (sortOrderElement) sortOrderElement.addEventListener('change', filterResources);

            filterResources();
        });

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
                window.location.href = "/SAE/index.php?action=logout";
            }
        }
    </script>

</body>
</html>