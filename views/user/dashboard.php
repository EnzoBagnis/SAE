<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <title><?= htmlspecialchars($title ?? 'StudTraj - Tableau de bord') ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/dashboard.css">
     <link rel="stylesheet" href="../public/css/footer.css">
    <script src="../public/js/modules/import.js"></script>
    <script type="module" src="../public/js/dashboard-main.js"></script>


    <!-- SEO Meta Tags -->
    <meta name="description" content="Hub principal du site, vous pourrez y visionner les différents TD.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="http://studtraj.alwaysdata.net/views/dashboard.php">
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
                <svg width="20" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                    <?= htmlspecialchars($user_firstname ?? '') ?>
                    <?= htmlspecialchars($user_lastname ?? '') ?>
                </span>
            </div>
            <ul class="burger-menu-list">
                <li><a href="/index.php?action=dashboard" class="burger-link active">Tableau de bord</a></li>
                <li class="has-submenu">
                    <a href="#" class="burger-link" onclick="toggleStudentSubmenu(event)">
                        Liste des Étudiants
                        <span class="submenu-arrow">▼</span>
                    </a>
                    <ul class="burger-submenu" id="burgerStudentList">
                        <!-- Les étudiants seront chargés ici dynamiquement -->
                    </ul>
                </li>
                <li><a href="/index.php?action=mentions" class="burger-link">Mentions légales</a></li>
                <li><a href="#" onclick="confirmLogout()" class="burger-link burger-logout">Déconnexion</button></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Sidebar gauche pour les Étudiants et les TPs -->
        <aside class="sidebar">
            <div class="sidebar-tabs">
                <button class="sidebar-tab active" onclick="switchSidebarTab('students')">Étudiants</button>
                <button class="sidebar-tab" onclick="switchSidebarTab('tps')">TPs</button>
            </div>

            <div id="studentsTabContent" class="sidebar-tab-content active">
                <h2>Liste des Étudiants</h2>
                <div class="student-list" id="student-list">
                    <!-- La liste des étudiants sera ajoutée ici dynamiquement via JavaScript -->
                </div>
            </div>

            <div id="tpsTabContent" class="sidebar-tab-content">
                <h2>Liste des TPs</h2>
                <div class="tp-list" id="tp-list">
                    <!-- La liste des TPs sera ajoutée ici dynamiquement via JavaScript -->
                    <!-- Exemple de structure (à remplir dynamiquement) -->
                    <p>TP 1: Introduction au JS</p>
                    <p>TP 2: Manipulation du DOM</p>
                    <p>TP 3: Requêtes Asynchrones</p>
                </div>
            </div>
        </aside>

        <!-- Zone principale de visualisation -->
        <main class="main-content">
            <div class="data-zone">
                <p class="placeholder-message">Les données de l'étudiant seront affichées ici</p>
            </div>
        </main>
    </div>

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
                    <li><a href="/index.php?action=dashboard">Tableau de bord</a></li>
                    <li><a href="/index.php?action=login">Connexion</a></li>
                    <li><a href="/index.php?action=signup">Inscription</a></li>
                    <li><a href="/index.php?action=forgotpassword">Mot de passe oublié</a></li>
                    <li><a href="/index.php?action=mentions">Mentions légales</a></li>
                </ul>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../layouts/footer.php'; ?>

    <style>
        /* Styles pour les onglets de la sidebar */
        .sidebar-tabs {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }

        .sidebar-tab {
            flex-grow: 1;
            padding: 10px 15px;
            border: none;
            background-color: transparent;
            cursor: pointer;
            font-size: 1em;
            color: #555;
            transition: all 0.3s ease;
            position: relative;
            text-align: center;
        }

        .sidebar-tab:hover {
            color: #007bff;
        }

        .sidebar-tab.active {
            color: #007bff;
            font-weight: bold;
        }

        .sidebar-tab.active::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -1px; /* Pour chevaucher la bordure */
            width: 100%;
            height: 2px;
            background-color: #007bff;
        }

        .sidebar-tab-content {
            display: none;
        }

        .sidebar-tab-content.active {
            display: block;
        }

        .sidebar h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
        }

        .tp-list p {
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
            font-size: 0.95em;
            color: #333;
        }

        .tp-list p:last-child {
            border-bottom: none;
        }
    </style>

    <script>
        function switchSidebarTab(tabName) {
            // Désactiver tous les onglets
            document.querySelectorAll('.sidebar-tab').forEach(button => {
                button.classList.remove('active');
            });
            // Désactiver tout le contenu des onglets
            document.querySelectorAll('.sidebar-tab-content').forEach(content => {
                content.classList.remove('active');
            });

            // Activer l'onglet cliqué
            document.querySelector(`.sidebar-tab[onclick="switchSidebarTab('${tabName}')"]`).classList.add('active');
            // Activer le contenu de l'onglet cliqué
            document.getElementById(`${tabName}TabContent`).classList.add('active');
        }

        // Initialisation: s'assurer que l'onglet 'Étudiants' est actif par défaut au chargement
        document.addEventListener('DOMContentLoaded', (event) => {
            switchSidebarTab('students');
        });
    </script>
</body>
</html>
