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
                 <li class="has-submenu">
                    <a href="#" class="burger-link" onclick="toggleTpSubmenu(event)">
                        Liste des TPs
                        <span class="submenu-arrow">▼</span>
                    </a>
                    <ul class="burger-submenu" id="burgerTpList">
                        <!-- Les TPs seront chargés ici dynamiquement -->
                    </ul>
                </li>
                <li><a href="/index.php?action=mentions" class="burger-link">Mentions légales</a></li>
                <li><a href="#" onclick="confirmLogout()" class="burger-link burger-logout">Déconnexion</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Sidebar gauche pour les Étudiants et TPs -->
        <aside class="sidebar">
            <div class="sidebar-tabs-container" style="display: flex; justify-content: space-around; border-bottom: 1px solid #ddd; margin-bottom: 10px;">
                <button class="sidebar-tab active" onclick="switchSidebarTab('students')" style="flex-grow: 1; padding: 10px; border: none; background: none; cursor: pointer; font-size: 1em; color: #555; border-bottom: 2px solid transparent; transition: all 0.3s ease;">Étudiants</button>
                <button class="sidebar-tab" onclick="switchSidebarTab('tps')" style="flex-grow: 1; padding: 10px; border: none; background: none; cursor: pointer; font-size: 1em; color: #555; border-bottom: 2px solid transparent; transition: all 0.3s ease;">TPs</button>
            </div>

            <div id="studentsTabContent" class="sidebar-content active" style="flex-grow: 1; overflow-y: auto;">
                <h2>Liste des Étudiants</h2>
                <div class="student-list" id="student-list" style="max-height: calc(100% - 40px); overflow-y: auto;">
                    <!-- La liste des étudiants sera ajoutée ici dynamiquement via JavaScript -->
                    <p class="placeholder-message">Chargement des étudiants...</p>
                    <!-- Example student items for scrollability -->
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student A</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student B</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student C</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student D</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student E</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student F</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student G</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student H</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student I</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student J</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student K</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student L</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student M</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student N</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student O</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student P</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student Q</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student R</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student S</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">Student T</div>
                </div>
            </div>

            <div id="tpsTabContent" class="sidebar-content" style="display:none; flex-grow: 1; overflow-y: auto;">
                <h2>Liste des TPs</h2>
                <div class="tp-list" id="tp-list" style="max-height: calc(100% - 40px); overflow-y: auto;">
                    <!-- La liste des TPs sera ajoutée ici dynamiquement via JavaScript -->
                    <p class="placeholder-message">Chargement des TPs...</p>
                    <!-- Example TP items for scrollability -->
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">TP 1: Introduction to Programming</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">TP 2: Data Structures</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">TP 3: Algorithms Analysis</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">TP 4: Web Development Basics</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">TP 5: Database Management</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">TP 6: Object-Oriented Design</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">TP 7: Network Protocols</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">TP 8: Operating Systems</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">TP 9: Machine Learning Fundamentals</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">TP 10: Cybersecurity Essentials</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">TP 11: Cloud Computing</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">TP 12: Mobile App Development</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">TP 13: Game Development</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">TP 14: Artificial Intelligence</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">TP 15: Software Testing</div>
                    <div style="padding: 8px; border-bottom: 1px solid #eee;">TP 16: Project Management</div>
                </div>
            </div>
        </aside>

        <!-- Zone principale de visualisation -->
        <main class="main-content">
            <div class="data-zone">
                <p class="placeholder-message">Les données de l'étudiant ou du TP seront affichées ici</p>
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

    <script>
        function switchSidebarTab(tab) {
            document.querySelectorAll('.sidebar-tab').forEach(button => {
                button.classList.remove('active');
                button.style.color = '#555';
                button.style.borderBottom = '2px solid transparent';
            });
            document.querySelectorAll('.sidebar-content').forEach(content => {
                content.style.display = 'none';
            });

            if (tab === 'students') {
                const studentTabButton = document.querySelector('.sidebar-tab-container button:nth-child(1)');
                studentTabButton.classList.add('active');
                studentTabButton.style.color = '#007bff'; // Example active color
                studentTabButton.style.borderBottom = '2px solid #007bff'; // Example active border
                document.getElementById('studentsTabContent').style.display = 'flex'; // Use flex to allow internal scrolling
            } else if (tab === 'tps') {
                const tpTabButton = document.querySelector('.sidebar-tab-container button:nth-child(2)');
                tpTabButton.classList.add('active');
                tpTabButton.style.color = '#007bff'; // Example active color
                tpTabButton.style.borderBottom = '2px solid #007bff'; // Example active border
                document.getElementById('tpsTabContent').style.display = 'flex'; // Use flex to allow internal scrolling
            }
        }

        // Add a function for the TP submenu in the burger menu
        function toggleTpSubmenu(event) {
            event.preventDefault();
            const submenu = document.getElementById('burgerTpList');
            submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
            event.target.querySelector('.submenu-arrow').textContent = submenu.style.display === 'block' ? '▲' : '▼';
        }

        // Initialize the first tab as active on load
        document.addEventListener('DOMContentLoaded', () => {
            switchSidebarTab('students');
        });
    </script>
    <style>
        /* Basic styling for the new tab structure */
        .sidebar {
            display: flex;
            flex-direction: column;
            /* Adjust these values based on your dashboard.css sidebar styling */
            /* For example, if your sidebar has a fixed width or height,
               ensure these new flex properties align with it */
        }

        .sidebar-tabs-container button.active {
            color: #007bff; /* Highlight active tab */
            border-bottom: 2px solid #007bff; /* Underline active tab */
        }

        .sidebar-content {
            display: flex; /* Changed from 'block' to 'flex' */
            flex-direction: column;
            height: 100%; /* Make content take full height of its container */
        }

        /* Ensure scrollable lists have a defined height */
        .student-list, .tp-list {
            /* This max-height needs to be calculated based on the total sidebar height
               minus the height of the tab container, title (h2), and any padding/margins.
               For now, a dynamic calc() value is used, but you might need to fine-tune it. */
            flex-grow: 1; /* Allow the list to take available space */
            overflow-y: auto; /* Enable vertical scrolling */
            padding-right: 5px; /* Add some padding for the scrollbar */
        }
    </style>
</body>
</html>
