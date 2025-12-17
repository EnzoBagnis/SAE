<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id'])) {
    header('Location: /index.php?action=login');
    exit;
}

require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Resource.php';

$db = Database::getConnection();

$user_id = $_SESSION['id'];
$user_firstname = $_SESSION['prenom'] ?? 'Utilisateur';
$user_lastname = $_SESSION['nom'] ?? '';

$title = 'StudTraj - Mes Ressources';

$resources = Resource::getAllAccessibleResources($db, $user_id);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <title><?= htmlspecialchars($title ?? 'StudTraj - Tableau de bord') ?></title>
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="stylesheet" href="/public/css/dashboard.css">
    <link rel="stylesheet" href="/public/css/footer.css">
    <script src="/public/js/modules/import.js"></script>
    <script src="/public/js/dashboard-main.js"></script>

    <meta name="description"
          content="Gérez et visualisez vos ressources pédagogiques et celles partagées.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical"
          href="http://studtraj.alwaysdata.net/views/user/resources_list.php">
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
            background-image: url('/images/placeholder_resource.jpg');
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

        <!-- Bouton burger pour mobile -->
        <button class="burger-menu" id="burgerBtn" onclick="toggleBurgerMenu()" aria-label="Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav class="nav-menu">
            <a href="/index.php?action=resources_list" class="active">Tableau de bord</a>
            <a href="#" onclick="openSiteMap()">Plan du site</a>
            <a href="/index.php?action=mentions">Mentions légales</a>
        </nav>
        <div class="user-info">
            <button onclick="openImportModal()" class="btn-import-trigger">
                <svg style="width: 20px; height: 15px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Importer
            </button>
            <span><?= htmlspecialchars($user_firstname ?? '') ?> <?= htmlspecialchars($user_lastname ?? '') ?></span>
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
                <input type="text" id="searchBar"
                       placeholder="Rechercher..." onkeyup="filterResources()">
                <select id="sortOrder" onchange="filterResources()">
                    <option value="name_asc">Trier par nom (A-Z)</option>
                    <option value="name_desc">Trier par nom (Z-A)</option>
                    <option value="owner_name_asc">Trier par propriétaire (A-Z)</option>
                </select>
            </div>

            <div class="resources-grid" id="resourcesGrid">
                <?php if (!empty($resources)) : ?>
                    <?php foreach ($resources as $resource) : ?>
                        <?php
                        $ownerFullName = $resource->owner_firstname . ' ' .
                                         $resource->owner_lastname;
                        ?>
                        <a href="/index.php?action=dashboard&resource_id=<?=
                                   $resource->resource_id ?>"
                            class="resource-card"
                            data-name="<?= htmlspecialchars($resource->resource_name) ?>"
                            data-owner="<?= htmlspecialchars($ownerFullName) ?>"
                            data-access-type="<?=
                               htmlspecialchars($resource->access_type) ?>">
                            <?php if (!empty($resource->image_path)) : ?>
                                <img src="/images/<?=
                                         htmlspecialchars($resource->image_path) ?>"
                                     alt="Image de <?=
                                         htmlspecialchars($resource->resource_name) ?>"
                                     class="resource-card-image">
                            <?php else : ?>
                                <div class="resource-card-image placeholder"></div>
                            <?php endif; ?>
                            <div class="resource-card-content">
                                <h3><?= htmlspecialchars($resource->resource_name) ?></h3>
                                <p><?= htmlspecialchars($resource->description ?? 'Pas de description.') ?></p>
                                <span class="resource-card-owner">
                                    Par: <?= htmlspecialchars($ownerFullName) ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="placeholder-message">
                        Aucune ressource disponible pour le moment.
                    </p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal Import -->
    <div id="importModal" class="modal">
        <div class="modal-content import-modal">
            <span class="close" onclick="closeImportModal()">&times;</span>
            <h2>Importer des données JSON</h2>

            <div class="import-tabs">
                <button class="import-tab active" onclick="switchImportTab('exercises')" data-tab="exercises">
                    Exercices de TP
                </button>
                <button class="import-tab" onclick="switchImportTab('attempts')" data-tab="attempts">
                    Tentatives d'élèves
                </button>
            </div>

            <!-- Onglet Exercices -->
            <div id="exercisesTab" class="import-tab-content active">
                <div class="import-zone" id="exercisesDropZone">
                    <input type="file" id="exercisesFileInput" accept=".json"
                           style="display: none;"
                           onchange="handleFileSelect(event, 'exercises')">
                    <div class="drop-zone-content"
                         onclick="document.getElementById('exercisesFileInput').click()">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <p><strong>Cliquez pour sélectionner</strong> ou glissez-déposez un fichier JSON</p>
                        <p class="file-info">Format: exercices_tp.json</p>
                    </div>
                </div>
                <div id="exercisesPreview" class="file-preview" style="display: none;">
                    <h3>Aperçu du fichier</h3>
                    <div class="preview-content"></div>
                    <button class="btn-import" onclick="importExercises()">Importer les exercices</button>
                </div>
            </div>

            <!-- Onglet Tentatives -->
            <div id="attemptsTab" class="import-tab-content">
                <div class="import-zone" id="attemptsDropZone">
                    <input type="file" id="attemptsFileInput" accept=".json"
                           style="display: none;"
                           onchange="handleFileSelect(event, 'attempts')">
                    <div class="drop-zone-content"
                         onclick="document.getElementById('attemptsFileInput').click()">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <p><strong>Cliquez pour sélectionner</strong> ou glissez-déposez un fichier JSON</p>
                        <p class="file-info">Format: tentatives_eleves.json</p>
                    </div>
                </div>
                <div id="attemptsPreview" class="file-preview" style="display: none;">
                    <h3>Aperçu du fichier</h3>
                    <div class="preview-content"></div>
                    <button class="btn-import" onclick="importAttempts()">Importer les tentatives</button>
                </div>
            </div>

            <div id="importStatus" class="import-status" style="display: none;"></div>
        </div>
    </div>

    <!-- Modal Plan du site -->
    <div id="sitemapModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeSiteMap()">&times;</span>
            <h2>Plan du site</h2>
            <div class="sitemap-list">
                <ul>
                    <li><a href="/index.php?action=resources_list">Tableau de bord</a></li>
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
        function filterResources() {
            const searchText = document.getElementById('searchBar').value.toLowerCase();
            const filterType = document.getElementById('filterType').value;
            const sortOrder = document.getElementById('sortOrder').value;
            const grid = document.getElementById('resourcesGrid');
            let cards = Array.from(grid.getElementsByClassName('resource-card'));

            cards.forEach(card => {
                const name = card.dataset.name.toLowerCase();
                const owner = card.dataset.owner.toLowerCase();
                const accessType = card.dataset.accessType;

                const matchesSearch = name.includes(searchText) ||
                                     owner.includes(searchText);
                const matchesType = (filterType === 'all' ||
                                    accessType === filterType);

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
            if (filterTypeElement) {
                filterTypeElement.addEventListener('change', filterResources);
            }

            const searchBarElement = document.getElementById('searchBar');
            if (searchBarElement) {
                searchBarElement.addEventListener('keyup', filterResources);
            }

            const sortOrderElement = document.getElementById('sortOrder');
            if (sortOrderElement) {
                sortOrderElement.addEventListener('change', filterResources);
            }

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
                window.location.href = "/index.php?action=logout";
            }
        }
    </script>

</body>
</html>