<?php
// Page de détails d'une ressource - v2 (schema BD: exercices, ressources, teachers)
if (!defined('BASE_URL')) {
    define('BASE_URL', '');
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_firstname = $_SESSION['user_firstname'] ?? 'Utilisateur';
$user_lastname  = $_SESSION['user_lastname']  ?? '';
$initials = strtoupper(substr($user_firstname, 0, 1) . substr($user_lastname, 0, 1));
$resTitle = isset($resource) ? htmlspecialchars($resource->getResourceName()) : 'Ressource';
$title = 'StudTraj - ' . $resTitle;
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
    <script src="<?= BASE_URL ?>/public/js/modules/import.js"></script>
    <script src="https://cdn.plot.ly/plotly-2.32.0.min.js"></script>
    <script src="<?= BASE_URL ?>/public/js/modules/resourceIaChart.js"></script>
    <script>
        window.BASE_URL    = '<?= BASE_URL ?>';
        window.RESOURCE_ID = <?= (int)$resource->getResourceId() ?>;
    </script>
    <meta name="description" content="Détails de la ressource <?= $resTitle ?>.">
    <meta name="robots" content="noindex, nofollow">
    <style>
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
        .tp-item:last-child { border-bottom: none; }
        .tp-item-info h3 { margin: 0; font-size: 1.1em; color: #555; }
        .tp-item-info p { margin: 5px 0 0; font-size: 0.9em; color: #777; }
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
        .tp-item-actions .btn:hover { background-color: #0056b3; }
        .resource-details-header {
            padding: 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e2e6ea;
            text-align: center;
            margin-bottom: 20px;
        }
        .resource-details-header h1 { margin: 0 0 10px 0; color: #333; }
        .resource-details-header p { color: #666; font-size: 1.1em; max-width: 800px; margin: 0 auto; }
        .resource-details-header .owner-info { font-style: italic; color: #888; margin-top: 10px; }
        .success-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            margin-left: 8px;
        }
        .badge-high { background: #d4edda; color: #155724; }
        .badge-mid  { background: #fff3cd; color: #856404; }
        .badge-low  { background: #f8d7da; color: #721c24; }
        .badge-none { background: #e2e3e5; color: #383d41; }

        @keyframes ia-spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
        #ia-generate-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        #ia-generate-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        #ia-generate-btn.loading {
            opacity: 0.8;
        }
        #ia-generate-btn.loading #ia-generate-icon { display: none; }
        #ia-generate-btn.loading #ia-generate-spinner { display: inline-block !important; }
        .ia-info-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85em;
        }
        .ia-info-ready  { background: #d4edda; color: #155724; }
        .ia-info-missing { background: #fff3cd; color: #856404; }
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
        <a href="<?= BASE_URL ?>/exercises">Exercices</a>
        <a href="<?= BASE_URL ?>/ia">IA</a>
    </nav>
    <div class="header-right">
        <button onclick="openImportModal(<?= (int)$resource->getResourceId() ?>)" class="btn-import-trigger">
            <svg style="width:20px;height:15px;" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2">
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
            <li><a href="<?= BASE_URL ?>/exercises" class="burger-link">Exercices</a></li>
            <li><a href="#" onclick="confirmLogout()" class="burger-link burger-logout">Déconnexion</a></li>
        </ul>
    </div>
</nav>

<div class="main-content">
    <div style="padding: 20px 20px 0;">
        <a href="<?= BASE_URL ?>/resources"
           style="color:#666; text-decoration:none; font-size:.9em;">
            &larr; Retour aux ressources
        </a>
    </div>

    <div class="resource-details-header">
        <h1><?= $resTitle ?></h1>
        <?php if ($resource->getDescription()) : ?>
            <p><?= htmlspecialchars($resource->getDescription()) ?></p>
        <?php endif; ?>
        <?php if ($resource->getOwnerFirstname() || $resource->getOwnerLastname()) : ?>
            <div class="owner-info">
                Créée par <?= htmlspecialchars($resource->getOwnerFullName()) ?>
            </div>
        <?php endif; ?>
        <div style="margin-top: 15px;">
            <button class="btn" onclick="openImportModal(<?= (int)$resource->getResourceId() ?>)"
                    style="display:inline-block; padding:8px 16px; background:#007bff;
                    color:#fff; border-radius:4px; border:none; cursor:pointer;">
                Importer des données
            </button>
        </div>
    </div>

    <div class="tp-list-container">
        <h2>Travaux Pratiques</h2>
        <?php if (!empty($exercises)) : ?>
            <?php foreach ($exercises as $exercise) : ?>
                <div class="tp-item">
                    <div class="tp-item-info">
                        <h3>
                            <?= htmlspecialchars($exercise['exercice_name'] ?? 'Exercice sans titre') ?>
                            <?php if ($exercise['total_attempts'] > 0) : ?>
                                <?php
                                    $rate = $exercise['success_rate'];
                                    $badgeClass = $rate >= 70 ? 'badge-high' : ($rate >= 40 ? 'badge-mid' : 'badge-low');
                                ?>
                                <span class="success-badge <?= $badgeClass ?>">
                                    <?= $rate ?>% de réussite
                                </span>
                            <?php else : ?>
                                <span class="success-badge badge-none">Aucune tentative</span>
                            <?php endif; ?>
                        </h3>
                        <p>
                            <?php if (!empty($exercise['extention'])) : ?>
                                Extension : <code><?= htmlspecialchars($exercise['extention']) ?></code>
                            <?php endif; ?>
                            &nbsp;·&nbsp; Date : <?= htmlspecialchars($exercise['date'] ?? '') ?>
                        </p>
                        <?php if ($exercise['total_attempts'] > 0) : ?>
                            <p style="font-size:0.85em; color:#999;">
                                <?= (int)$exercise['successful_attempts'] ?> / <?= (int)$exercise['total_attempts'] ?> tentatives réussies
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="tp-item-actions">
                        <a href="<?= BASE_URL ?>/exercises/<?= (int)$exercise['exercice_id'] ?>"
                           class="btn">Voir le TP</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p style="color:#888;">Aucun travail pratique disponible pour cette ressource.</p>
        <?php endif; ?>
    </div>

    <!-- ══ Section IA Macro — Cartographie globale ══ -->
    <div id="ia-macro-section" class="ia-macro-section" style="max-width: 900px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:10px;">
            <h2 style="margin:0; color:#333;">🤖 Cartographie IA des codes</h2>
            <button id="ia-generate-btn" class="btn"
                    style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px;
                           background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                           color:#fff; border-radius:6px; border:none; cursor:pointer;
                           font-size:0.95em; font-weight:600; transition:all 0.3s;"
                    disabled>
                <svg id="ia-generate-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polygon points="10 8 16 12 10 16 10 8" fill="currentColor" stroke="none"></polygon>
                </svg>
                <svg id="ia-generate-spinner" width="18" height="18" viewBox="0 0 24 24"
                     style="display:none; animation: ia-spin 1s linear infinite; flex-shrink:0;"
                     fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M12 2 A10 10 0 0 1 22 12" stroke-linecap="round"></path>
                </svg>
                <span>Générer la cartographie IA</span>
            </button>
        </div>

        <div id="ia-data-info" style="margin-bottom:10px; font-size:0.9em;"></div>
        <div id="ia-macro-status" style="display:none; padding:12px; background:#f8f9fa; border-radius:6px; color:#555; font-size:0.9em; margin-bottom:10px;"></div>
        <div id="ia-macro-meta" style="display:none; margin-bottom:10px; font-size:0.9em; color:#666;"></div>
        <div id="ia-macro-plot" style="display:none; width:100%; min-height:500px; border:1px solid #eee; border-radius:6px;"></div>
    </div>
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
                       style="display:none;"
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
                    <p class="file-info">Format : exercices_tp.json</p>
                </div>
            </div>
            <div id="exercisesPreview" class="file-preview" style="display:none;">
                <h3>Aperçu du fichier</h3>
                <div class="preview-content"></div>
                <button class="btn-import" onclick="importExercises()">Importer les exercices</button>
            </div>
        </div>

        <!-- Onglet Tentatives -->
        <div id="attemptsTab" class="import-tab-content">
            <div class="import-zone" id="attemptsDropZone">
                <input type="file" id="attemptsFileInput" accept=".json"
                       style="display:none;"
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
                    <p class="file-info">Format : tentatives_eleves.json</p>
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
    function toggleBurgerMenu() {
        document.getElementById('burgerNav').classList.toggle('active');
        document.getElementById('burgerBtn').classList.toggle('open');
    }
    function confirmLogout() {
        if (confirm('Voulez-vous vraiment vous déconnecter ?')) {
            window.location.href = window.BASE_URL + '/auth/logout';
        }
    }
</script>
</body>
</html>
