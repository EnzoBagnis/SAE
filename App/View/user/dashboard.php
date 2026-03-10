﻿﻿<?php
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
    <script>
        // Inject server-side context for JS modules — doit être AVANT dashboard-main.js
        window.BASE_URL    = '<?= BASE_URL ?>';
        window.RESOURCE_ID = <?= $resource_id !== null ? (int)$resource_id : 'null' ?>;
    </script>
    <script type="module" src="<?= BASE_URL ?>/public/js/dashboard-main.js"></script>
    <style>
        /* Nouveau layout sans sidebar */
        .dashboard-container { display: block; height: auto; overflow: visible; }
        .viz-main-content {
            padding: 1.5rem 2rem 5rem;
            background: #f0f2f5;
            min-height: calc(100vh - 80px);
            margin-top: 80px;
        }
        .viz-data-zone {
            background: #fff;
            border-radius: 10px;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            min-height: 400px;
        }
        /* Breadcrumb */
        .viz-breadcrumb {
            display: flex; align-items: center; flex-wrap: wrap;
            gap: 2px; padding: 0.5rem 0 1rem;
            font-size: 0.9rem; color: #7f8c8d;
        }
        .viz-bc-btn { background: none; border: none; color: #3498db; cursor: pointer; font-size: 0.9rem; padding: 0; text-decoration: underline; }
        .viz-bc-btn:hover { color: #2980b9; }
        .viz-bc-sep { color: #bdc3c7; }
        .viz-bc-current { color: #2c3e50; font-weight: 600; }
        /* Titres et hints */
        .viz-title { color: #2c3e50; margin: 0 0 0.5rem; font-size: 1.4rem; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem; }
        .viz-hint { color: #7f8c8d; font-size: 0.88rem; margin: 0 0 1.5rem; }
        /* Grilles de graphiques */
        .viz-top-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
        @media (max-width: 768px) { .viz-top-grid { grid-template-columns: 1fr; } }
        .viz-chart-card { background: #f8f9fa; border-radius: 8px; padding: 1rem 1.25rem 1.25rem; box-shadow: 0 1px 4px rgba(0,0,0,.07); overflow: hidden; }
        .viz-chart-full { grid-column: 1 / -1; max-width: 480px; margin: 0 auto; width: 100%; }
        .viz-chart-title { color: #34495e; font-size: 1rem; margin: 0 0 0.75rem; font-weight: 600; }
        .viz-chart-header { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.75rem; }
        .viz-sort-select {
            padding: 4px 8px;
            font-size: 0.8rem;
            border: 1px solid #d0d7de;
            border-radius: 4px;
            background: #fff;
            color: #2c3e50;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .viz-sort-select:hover, .viz-sort-select:focus { border-color: #3498db; outline: none; }
        .viz-no-data { color: #95a5a6; font-size: 0.9rem; text-align: center; padding: 1rem 0; }
        /* Cartes de stats */
        .viz-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .viz-stat-card { background: #f8f9fa; border-radius: 8px; padding: 1rem 1.25rem; border-left: 4px solid #3498db; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
        .viz-stat-value { font-size: 1.8rem; font-weight: bold; }
        .viz-stat-label { color: #7f8c8d; font-size: 0.85rem; margin-top: 0.25rem; }
        /* Grade badges */
        .grade-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 0.9rem; font-weight: bold; margin-left: 8px; }
        .grade-a { background: #d5f5e3; color: #1e8449; }
        .grade-b { background: #fef9e7; color: #b7950b; }
        .grade-c { background: #fadbd8; color: #922b21; }
        /* Loading */
        .viz-loading { text-align: center; padding: 3rem; color: #7f8c8d; font-size: 1.1rem; }
        /* En-tête ressource */
        .viz-resource-header { background: #fff; border-radius: 10px; padding: 1rem 2rem; margin-bottom: 1rem; box-shadow: 0 1px 4px rgba(0,0,0,.06); border-left: 4px solid #3498db; }
        .viz-resource-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #3498db; font-weight: 600; }
        .viz-resource-name { margin: 0.2rem 0 0; color: #2c3e50; font-size: 1.5rem; }
        .viz-resource-desc { margin: 0.3rem 0 0; color: #7f8c8d; font-size: 0.9rem; }
    </style>
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

<div class="viz-main-content">
    <?php if (isset($resource)) : ?>
    <div class="viz-resource-header">
        <span class="viz-resource-label">Ressource</span>
        <h1 class="viz-resource-name"><?= htmlspecialchars($resource->getResourceName()) ?></h1>
        <?php if ($resource->getDescription()) : ?>
            <p class="viz-resource-desc"><?= htmlspecialchars($resource->getDescription()) ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ── Barre de recherche (Élève / TP) ── -->
    <div style="background:#fff;border-radius:10px;padding:14px 20px;margin-bottom:1rem;
                box-shadow:0 1px 4px rgba(0,0,0,.06);display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <select id="resourceSearchType"
                style="padding:8px 10px;border:1px solid #ddd;border-radius:4px;font-size:.9em;background:#f8f9fa;">
            <option value="exercises">Travaux Pratiques</option>
            <option value="students">Élève</option>
        </select>
        <input type="text" id="resourceSearchInput"
               placeholder="Rechercher un TP ou un étudiant par mot-clé…"
               style="flex:1;min-width:200px;padding:8px 10px;border:1px solid #ddd;
                      border-radius:4px;font-size:.9em;" />
        <button id="resourceClearBtn"
                style="padding:8px 14px;border-radius:4px;border:1px solid #ddd;
                       background:#f5f5f5;font-size:.9em;cursor:pointer;">
            Effacer
        </button>
    </div>
    <!-- Résultats de recherche -->
    <div id="resourceSearchResults"
         style="background:#fff;border-radius:10px;padding:14px 20px;margin-bottom:1rem;
    <!-- Barre de recherche -->
    <div id="resourceSearchResults" style="display:none;background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:8px;margin-bottom:8px;">
        <strong id="rsr-label" style="font-size:.85rem;color:#555;"></strong>
        <ul id="rsr-list"
            style="list-style:none;padding:0;margin:0;max-height:280px;overflow-y:auto;"></ul>
    </div>

    <div class="viz-data-zone">
        <div class="viz-loading">⏳ Chargement des données…</div>
    </div>
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
        <p>&copy; <?= date('Y') ?> StudTraj - Tous droits réservés</p>
        <ul class="footer-links">
            <li><a href="<?= BASE_URL ?>/mentions-legales">Mentions légales</a></li>
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

<script>
(function () {
    var input     = document.getElementById('resourceSearchInput');
    var typeSelect = document.getElementById('resourceSearchType');
    var clearBtn  = document.getElementById('resourceClearBtn');
    var resDiv    = document.getElementById('resourceSearchResults');
    var resLabel  = document.getElementById('rsr-label');
    var resList   = document.getElementById('rsr-list');

    if (!input) return;

    function debounce(fn, delay) {
        var t;
        return function () { clearTimeout(t); t = setTimeout(fn, delay); };
    }

    function setList(items) {
        resList.innerHTML = '';
        items.forEach(function (item) {
            var li = document.createElement('li');
            li.textContent = item.text;
            li.style.padding = '9px 10px';
            li.style.borderBottom = '1px solid #f0f0f0';
            li.style.fontSize = '.9em';
            if (item.click) {
                li.style.cursor = 'pointer';
                li.style.color = '#3498db';
                li.addEventListener('click', item.click);
                li.addEventListener('mouseenter', function () { li.style.background = '#f0f7ff'; });
                li.addEventListener('mouseleave', function () { li.style.background = ''; });
            }
            resList.appendChild(li);
        });
    }

    async function doSearch() {
        var q    = (input.value || '').trim().toLowerCase();
        var type = typeSelect.value;

        if (!q) {
            resDiv.style.display = 'none';
            resList.innerHTML = '';
            return;
        }

        resDiv.style.display  = 'block';
        resLabel.textContent  = type === 'exercises' ? 'Travaux Pratiques trouvés :' : 'Étudiants trouvés :';
        resList.innerHTML     = '<li style="color:#888;padding:8px;font-style:italic;">Chargement…</li>';

        var BASE = window.BASE_URL  || '';
        var RID  = window.RESOURCE_ID || null;

        if (type === 'exercises') {
            var url = BASE + '/api/dashboard/exercises' + (RID ? '?resource_id=' + RID : '');
            try {
                var resp = await fetch(url);
                var json = await resp.json();
                var exercises = (json.data && json.data.exercises) ? json.data.exercises : [];
                var matches = exercises.filter(function (e) {
                    return ((e.funcname || '') + ' ' + (e.exo_name || '') + ' ' + (e.extention || ''))
                        .toLowerCase().indexOf(q) !== -1;
                });
                if (!matches.length) {
                    setList([{ text: 'Aucun TP trouvé.', click: null }]);
                } else {
                    setList(matches.map(function (e) {
                        var id   = e.exercise_id || e.exercice_id;
                        var name = e.funcname || e.exo_name || 'TP sans titre';
                        var rate = e.success_rate != null ? ' — ' + e.success_rate + '% réussite' : '';
                        return {
                            text:  name + rate,
                            click: function () { window.location.href = BASE + '/exercises/' + id; }
                        };
                    }));
                }
            } catch (err) {
                setList([{ text: 'Erreur lors du chargement des exercices.', click: null }]);
                console.error(err);
            }

        } else {
            var url = BASE + '/api/dashboard/students?page=1&perPage=100000' + (RID ? '&resource_id=' + RID : '');
            try {
                var resp = await fetch(url);
                var json = await resp.json();
                var students = (json.data && json.data.students) ? json.data.students : [];
                var matches = students.filter(function (s) {
                    return (s.title || s.identifier || s.id || '').toLowerCase().indexOf(q) !== -1;
                });
                if (!matches.length) {
                    setList([{ text: 'Aucun étudiant trouvé.', click: null }]);
                } else {
                    setList(matches.map(function (s) {
                        var label = s.title || s.identifier || s.id;
                        var sid   = s.id || s.identifier || s.title;
                        return {
                            text:  label,
                            click: function () {
                                resDiv.style.display = 'none';
                                resList.innerHTML = '';
                                input.value = '';
                                var dataZone = document.querySelector('.viz-data-zone');
                                if (dataZone && window.vizManager) {
                                    window.vizManager.renderLevel2Student(dataZone, sid);
                                    dataZone.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                } else if (typeof window.navigateToStudent === 'function') {
                                    window.navigateToStudent(sid);
                                }
                            }
                        };
                    }));
                }
            } catch (err) {
                setList([{ text: 'Erreur lors du chargement des étudiants.', click: null }]);
                console.error(err);
            }
        }
    }

    var debouncedSearch = debounce(doSearch, 300);
    input.addEventListener('input', debouncedSearch);
    typeSelect.addEventListener('change', debouncedSearch);
    clearBtn.addEventListener('click', function () {
        input.value = '';
        resDiv.style.display = 'none';
        resList.innerHTML = '';
    });
})();
</script>
</body>
</html>