<?php
if (!defined('BASE_URL')) { define('BASE_URL', ''); }
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Support both old session keys (prenom/nom) and new keys (user_firstname/user_lastname)
$user_firstname = $user_firstname ?? $_SESSION['user_firstname'] ?? $_SESSION['prenom'] ?? 'Utilisateur';
$user_lastname  = $user_lastname  ?? $_SESSION['user_lastname']  ?? $_SESSION['nom']    ?? '';
$title          = $title ?? 'StudTraj - Tableau de bord';

// Resource context (set by ResourcesController::show)
$resource_id = $resource_id ?? null;
if ($resource_id === null && isset($_GET['resource_id'])) {
    $resource_id = (int)$_GET['resource_id'];
}

$initials = strtoupper(substr($user_firstname, 0, 1) . substr($user_lastname, 0, 1));
$current_resource_id = $resource_id ?? 'null';
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/charts.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/footer.css">
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script src="<?= BASE_URL ?>/public/js/modules/import.js"></script>
    <script src="<?= BASE_URL ?>/public/js/modules/charts.js"></script>
    <script src="<?= BASE_URL ?>/public/js/modules/detailedCharts.js"></script>
    <script type="module" src="<?= BASE_URL ?>/public/js/dashboard-main.js"></script>
    <script>
        // Inject server-side context for JS modules
        window.BASE_URL     = '<?= BASE_URL ?>';
        window.RESOURCE_ID  = <?= $resource_id !== null ? (int)$resource_id : 'null' ?>;
    </script>
    <meta name="description" content="Hub principal du site, vous pourrez y visionner les différents TD.">
    <meta name="robots" content="noindex, nofollow">
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
        <button onclick="openImportModal(<?= $current_resource_id ?>)" class="btn-import-trigger">
            <svg style="width:20px;height:15px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            Importer
        </button>
        <div class="user-profile">
            <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
            <span><?= htmlspecialchars($user_firstname) ?> <?= htmlspecialchars($user_lastname) ?></span>
        </div>
        <a href="<?= BASE_URL ?>/auth/logout" class="btn-logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span class="logout-text">Déconnexion</span>
        </a>
    </div>
</header>

<nav class="burger-nav" id="burgerNav">
    <button class="burger-menu burger-close-internal active" onclick="toggleBurgerMenu()" aria-label="Fermer le menu">
        <span></span><span></span><span></span>
    </button>
    <div class="burger-nav-content">
        <div class="burger-user-info">
            <span><?= htmlspecialchars($user_firstname) ?> <?= htmlspecialchars($user_lastname) ?></span>
        </div>
        <ul class="burger-menu-list">
            <li><a href="<?= BASE_URL ?>/resources" class="burger-link">Ressources</a></li>
            <li class="has-submenu">
                <a href="#" class="burger-link" onclick="toggleStudentSubmenu(event)">
                    Liste des Étudiants <span class="submenu-arrow">▼</span>
                </a>
                <ul class="burger-submenu" id="burgerStudentList"></ul>
            </li>
            <li class="has-submenu">
                <a href="#" class="burger-link" onclick="toggleExerciseSubmenu(event)">
                    Liste des TP <span class="submenu-arrow">▼</span>
                </a>
                <ul class="burger-submenu" id="burgerExerciseList"></ul>
            </li>
            <li>
                <a href="#" class="burger-link burger-import"
                   onclick="openImportModal(<?= $current_resource_id ?>); toggleBurgerMenu(); return false;">
                    Importer
                </a>
            </li>
            <li><a href="#" onclick="confirmLogout()" class="burger-link burger-logout">Déconnexion</a></li>
        </ul>
    </div>
</nav>

<div class="dashboard-container">
    <aside class="sidebar sidebar-mobile-style">
        <div class="view-selector-header">
            <button class="view-tab active" id="btnStudents" onclick="switchListView('students')">
                Liste des Étudiants
            </button>
            <button class="view-tab" id="btnExercises" onclick="switchListView('exercises')">
                Liste des TP
            </button>
        </div>
        <div class="sidebar-list" id="sidebar-list"></div>
    </aside>

    <main class="main-content">
        <div class="data-zone">
            <p class="placeholder-message">Les données de l'étudiant seront affichées ici</p>
        </div>
    </main>
</div>

<!-- Modal Import -->
<div id="importModal" class="modal">
    <div class="modal-content import-modal">
        <span class="close" onclick="closeImportModal()">&times;</span>
        <h2>Importer des données JSON</h2>
        <div class="import-tabs">
            <button class="import-tab active" onclick="switchImportTab('exercises')" data-tab="exercises">Exercices de TP</button>
            <button class="import-tab" onclick="switchImportTab('attempts')" data-tab="attempts">Tentatives d'élèves</button>
        </div>
        <div id="exercisesTab" class="import-tab-content active">
            <div class="import-zone" id="exercisesDropZone">
                <input type="file" id="exercisesFileInput" accept=".json" style="display:none;" onchange="handleFileSelect(event, 'exercises')">
                <div class="drop-zone-content" onclick="document.getElementById('exercisesFileInput').click()">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <p><strong>Cliquez pour sélectionner</strong> ou glissez-déposez un fichier JSON</p>
                    <p class="file-info">Format: exercices_tp.json</p>
                </div>
            </div>
            <div id="exercisesPreview" class="file-preview" style="display:none;">
                <h3>Aperçu du fichier</h3>
                <div class="preview-content"></div>
                <button class="btn-import" onclick="importExercises()">Importer les exercices</button>
            </div>
        </div>
        <div id="attemptsTab" class="import-tab-content">
            <div class="import-zone" id="attemptsDropZone">
                <input type="file" id="attemptsFileInput" accept=".json" style="display:none;" onchange="handleFileSelect(event, 'attempts')">
                <div class="drop-zone-content" onclick="document.getElementById('attemptsFileInput').click()">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <p><strong>Cliquez pour sélectionner</strong> ou glissez-déposez un fichier JSON</p>
                    <p class="file-info">Format: tentatives_eleves.json</p>
                </div>
            </div>
            <div id="attemptsPreview" class="file-preview" style="display:none;">
                <h3>Aperçu du fichier</h3>
                <div class="preview-content"></div>
                <button class="btn-import" onclick="importAttempts()">Importer les tentatives</button>
            </div>
        </div>
        <div id="importStatus" class="import-status" style="display:none;"></div>
    </div>
</div>

<!-- Footer -->
<footer class="main-footer">
    <div class="footer-content">
        <p>&copy; 2024 StudTraj - Tous droits réservés</p>
        <ul class="footer-links">
            <li><a href="<?= BASE_URL ?>/index.php?action=mentions">Mentions légales</a></li>
        </ul>
    </div>
</footer>

<script>
    function confirmLogout() {
        if (confirm('Voulez-vous vraiment vous déconnecter ?')) {
            window.location.href = window.BASE_URL + '/auth/logout';
        }
    }
</script>
</body>
</html>