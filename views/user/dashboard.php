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
            <div class="sidebar-tabs-container">
                <button class="sidebar-tab active" onclick="switchSidebarTab('students')">Étudiants</button>
                <button class="sidebar-tab" onclick="switchSidebarTab('tps')">TPs</button>
            </div>

            <div id="studentsTabContent" class="sidebar-content active">
                <h2>Liste des Étudiants</h2>
                <div class="student-list" id="student-list">
                    <!-- La liste des étudiants sera ajoutée ici dynamiquement via JavaScript -->
                    <p class="placeholder-message">Chargement des étudiants...</p>
                    <!-- Example student items for scrollability -->
                    <div class="student-item">Student A</div>
                    <div class="student-item">Student B</div>
                    <div class="student-item">Student C</div>
                    <div class="student-item">Student D</div>
                    <div class="student-item">Student E</div>
                    <div class="student-item">Student F</div>
                    <div class="student-item">Student G</div>
                    <div class="student-item">Student H</div>
                    <div class="student-item">Student I</div>
                    <div class="student-item">Student J</div>
                    <div class="student-item">Student K</div>
                    <div class="student-item">Student L</div>
                    <div class="student-item">Student M</div>
                    <div class="student-item">Student N</div>
                    <div class="student-item">Student O</div>
                    <div class="student-item">Student P</div>
                    <div class="student-item">Student Q</div>
                    <div class="student-item">Student R</div>
                    <div class="student-item">Student S</div>
                    <div class="student-item">Student T</div>
                </div>
            </div>

            <div id="tpsTabContent" class="sidebar-content">
                <h2>Liste des TPs</h2>
                <div class="tp-list" id="tp-list">
                    <!-- La liste des TPs sera ajoutée ici dynamiquement via JavaScript -->
                    <p class="placeholder-message">Chargement des TPs...</p>
                    <!-- Example TP items for scrollability -->
                    <div class="tp-item">TP 1: Introduction to Programming</div>
                    <div class="tp-item">TP 2: Data Structures</div>
                    <div class="tp-item">TP 3: Algorithms Analysis</div>
                    <div class="tp-item">TP 4: Web Development Basics</div>
                    <div class="tp-item">TP 5: Database Management</div>
                    <div class="tp-item">TP 6: Object-Oriented Design</div>
                    <div class="tp-item">TP 7: Network Protocols</div>
                    <div class="tp-item">TP 8: Operating Systems</div>
                    <div class="tp-item">TP 9: Machine Learning Fundamentals</div>
                    <div class="tp-item">TP 10: Cybersecurity Essentials</div>
                    <div class="tp-item">TP 11: Cloud Computing</div>
                    <div class="tp-item">TP 12: Mobile App Development</div>
                    <div class="tp-item">TP 13: Game Development</div>
                    <div class="tp-item">TP 14: Artificial Intelligence</div>
                    <div class="tp-item">TP 15: Software Testing</div>
                    <div class="tp-item">TP 16: Project Management</div>
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
            });
            document.querySelectorAll('.sidebar-content').forEach(content => {
                content.style.display = 'none';
            });

            if (tab === 'students') {
                document.querySelector('.sidebar-tabs-container button:nth-child(1)').classList.add('active');
                document.getElementById('studentsTabContent').style.display = 'flex';
            } else if (tab === 'tps') {
                document.querySelector('.sidebar-tabs-container button:nth-child(2)').classList.add('active');
                document.getElementById('tpsTabContent').style.display = 'flex';
            }
        }

        function toggleTpSubmenu(event) {
            event.preventDefault();
            const submenu = document.getElementById('burgerTpList');
            submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
            event.target.querySelector('.submenu-arrow').textContent = submenu.style.display === 'block' ? '▲' : '▼';
        }

        function toggleStudentSubmenu(event) { // Ensure this function exists for the burger menu
             event.preventDefault();
            const submenu = document.getElementById('burgerStudentList');
            submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
            event.target.querySelector('.submenu-arrow').textContent = submenu.style.display === 'block' ? '▲' : '▼';
        }

        // Initialize the first tab as active on load
        document.addEventListener('DOMContentLoaded', () => {
            switchSidebarTab('students');
        });
    </script>
</body>
</html>
