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

// Calcul des initiales pour l'avatar
$initials = strtoupper(substr($user_firstname, 0, 1) . substr($user_lastname, 0, 1));

$resources = Resource::getAllAccessibleResources($db, $user_id);

// Récupération des utilisateurs pour le partage
$all_users = [];
try {
    $stmt_users = $db->prepare(
            "SELECT id, prenom, nom FROM utilisateurs WHERE id != :id ORDER BY nom ASC"
    );
    $stmt_users->execute([':id' => $user_id]);
    $all_users = $stmt_users->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    $all_users = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <title><?= htmlspecialchars($title ?? 'StudTraj - Tableau de bord') ?></title>
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="stylesheet" href="/public/css/dashboard.css">
    <link rel="stylesheet" href="/public/css/footer.css">
    <script src="/public/js/modules/import.js"></script>
    <script src="/public/js/dashboard-main.js"></script>

    <style>
        /* =========================================
           CSS DE BASE (Desktop First)
           ========================================= */

        /* Reset de box-sizing pour mieux gérer les largeurs */
        * {
            box-sizing: border-box;
        }

        /* --- GRILLE DES RESSOURCES (Inchangé ou presque) --- */
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .resource-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: relative;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s;
        }
        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .resource-link-wrapper {
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        .resource-card-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background-color: #f0f0f0;
        }
        .resource-card-content {
            padding: 15px;
        }
        .resource-card-owner {
            font-size: 0.8em;
            color: #999;
            display: block;
            text-align: right;
            margin-top: 10px;
        }
        .btn-edit-resource {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
        }
        .btn-edit-resource:hover {
            background-color: #f0f0f0;
        }

        /* --- BARRE DE FILTRES --- */
        .filter-bar {
            padding: 20px;
            background: #eef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            flex-wrap: wrap; /* Permet le retour à la ligne si nécessaire */
        }

        .filter-group-left {
            display: flex;
            gap: 10px;
            flex-grow: 1; /* Prend l'espace disponible */
            flex-wrap: wrap;
        }

        .filter-bar input, .filter-bar select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            min-width: 150px;
        }
        .filter-bar input {
            flex-grow: 1; /* La recherche s'agrandit */
        }

        /* --- BOUTONS & USER INFO --- */

        .btn-create-resource {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);

            /* CORRECTION DEMANDEE : Taille adaptée au contenu, pas tout le bloc */
            width: fit-content;
            white-space: nowrap; /* Empêche le texte de se couper */
            flex-shrink: 0; /* Empêche le bouton de s'écraser */
        }
        .btn-create-resource:hover {
            background-color: #2980b9;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background-color: #e67e22;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9em;
            border: 2px solid rgba(255,255,255,0.3);
            flex-shrink: 0;
        }

        .btn-logout {
            color: #ecf0f1;
            text-decoration: none;
            padding: 6px 12px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 4px;
            font-size: 0.9em;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }
        .btn-logout:hover {
            background-color: #c0392b;
            border-color: #c0392b;
            color: white;
        }

        /* --- MODAL (Inchangé) --- */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto; /* Permet le scroll si l'écran est petit */
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 500px;
            border-radius: 8px;
            position: relative;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"], .form-group textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .users-checklist {
            max-height: 150px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            background: #fafafa;
        }
        .btn-submit {
            background-color: #4CAF50;
            color: white;
            padding: 12px;
            border: none;
            width: 100%;
            cursor: pointer;
            border-radius: 4px;
            font-size: 16px;
            margin-top: 10px;
        }
        .btn-submit:hover { background-color: #45a049; }

        .btn-delete-trigger {
            background-color: #f44336;
            color: white;
            padding: 10px;
            border: none;
            width: 100%;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 15px;
            font-size: 14px;
        }
        .btn-delete-trigger:hover { background-color: #d32f2f; }

        .confirm-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .btn-confirm-yes {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-confirm-no {
            background-color: #ccc;
            color: black;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        /* =========================================
           RESPONSIVE (MOBILE & TABLETTE)
           ========================================= */

        /* Tablettes et petits écrans (< 900px) */
        @media (max-width: 900px) {
            .filter-bar {
                flex-direction: column;
                align-items: stretch; /* Les éléments prennent toute la largeur dispo */
            }
            .filter-group-left {
                width: 100%;
            }
            .btn-create-resource {
                align-self: flex-end; /* Bouton aligné à droite */
                width: auto;
            }
        }

        /* Mobile (< 600px) */
        @media (max-width: 600px) {
            /* Header s'empile verticalement */
            .top-menu {
                flex-direction: column;
                height: auto;
                padding: 15px;
                gap: 15px;
            }

            .user-info {
                width: 100%;
                justify-content: space-between; /* Espace entre Profil et Déconnexion */
            }

            /* Filtres en colonne */
            .filter-group-left {
                flex-direction: column;
            }

            .filter-bar input, .filter-bar select {
                width: 100%;
            }

            /* Bouton création prend toute la largeur sur très petit écran pour être facile à cliquer */
            .btn-create-resource {
                width: 100%;
                justify-content: center;
            }

            .resources-grid {
                grid-template-columns: 1fr; /* Une seule colonne */
                padding: 10px;
            }
        }

    </style>
    <script src="../public/js/dashboard-main.js"></script>
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
                <svg style="width: 20px; height: 15px;" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2">
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
            <span><?= htmlspecialchars($user_firstname) ?> <?= htmlspecialchars($user_lastname) ?></span>
        </div>
        <!-- Bouton Déconnexion -->
        <a href="/index.php?action=logout" class="btn-logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span class="logout-text">Déconnexion</span>
        </a>
    </div>
</header>

<div class="dashboard-container">
    <main class="main-content">
        <h2 style="padding: 20px 20px 0;">Tableau de bord</h2>

        <div class="filter-bar">
            <!-- Groupe de gauche pour recherche et select -->
            <div class="filter-group-left">
                <input type="text" id="searchBar" placeholder="Rechercher..." onkeyup="filterResources()">
                <select id="filterType" onchange="filterResources()">
                    <option value="all">Tout voir</option>
                    <option value="owner">Mes créations</option>
                    <option value="shared">Partagées avec moi</option>
                </select>
            </div>

            <!-- Bouton Créer (ne prend pas toute la largeur) -->
            <button onclick="openResourceModal('create')" class="btn-create-resource">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Nouvelle Ressource
            </button>
        </div>

        <div class="resources-grid" id="resourcesGrid">
            <?php if (!empty($resources)) : ?>
                <?php foreach ($resources as $resource) : ?>
                    <?php
                    $creatorId = $resource->owner_user_id ?? 0;
                    $resId = $resource->resource_id;
                    $resName = $resource->resource_name ?? 'Sans titre';
                    $resDesc = $resource->description ?? '';
                    $resImg = $resource->image_path ?? '';
                    $isOwner = ($creatorId == $user_id);

                    $ownerName = ($resource->owner_firstname ?? '') . ' ' . ($resource->owner_lastname ?? '');
                    if (trim($ownerName) == '') {
                        $ownerName = "Utilisateur #$creatorId";
                    }
                    ?>

                    <div class="resource-card"
                         data-name="<?= htmlspecialchars($resName) ?>"
                         data-owner="<?= htmlspecialchars($ownerName) ?>"
                         data-access-type="<?= $isOwner ? 'owner' : 'shared' ?>"
                         data-id="<?= $resId ?>"
                         data-description="<?= htmlspecialchars($resDesc) ?>"
                         data-image="<?= htmlspecialchars($resImg) ?>">

                        <?php if ($isOwner) : ?>
                            <button class="btn-edit-resource" onclick="openResourceModal('edit', this)"
                                    title="Modifier">✏️</button>
                        <?php endif; ?>

                        <a href="/index.php?action=dashboard&resource_id=<?= $resId ?>"
                           class="resource-link-wrapper">
                            <?php if (!empty($resImg)) : ?>
                                <img src="/images/<?= htmlspecialchars($resImg) ?>"
                                     class="resource-card-image" alt="Image">
                            <?php else : ?>
                                <div class="resource-card-image"
                                     style="background:#eee; display:flex; align-items:center;
                                            justify-content:center; color:#777;">
                                    Pas d'image
                                </div>
                            <?php endif; ?>

                            <div class="resource-card-content">
                                <h3><?= htmlspecialchars($resName) ?></h3>
                                <p><?= htmlspecialchars($resDesc) ?></p>
                                <span class="resource-card-owner">
                                    Par: <?= htmlspecialchars($ownerName) ?>
                                </span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p style="padding:20px;">Aucune ressource trouvée.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- MODAL PRINCIPAL -->
<div id="resourceModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeResourceModal()">&times;</span>
        <h2 id="modalTitle">Nouvelle Ressource</h2>

        <form id="resourceForm" action="/index.php?action=save_resource"
              method="POST" enctype="multipart/form-data">
            <input type="hidden" name="resource_id" id="formResourceId" value="">

            <div class="form-group">
                <label>Nom :</label>
                <input type="text" id="resourceName" name="name" required>
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

            <div class="form-group">
                <label>Image :</label>
                <input type="file" name="image" accept="image/*">
                <p id="currentImageName" style="font-size:0.8em; color:#666; display:none;"></p>
            </div>

            <div class="form-group">
                <label>Partager avec :</label>
                <div class="users-checklist">
                    <?php if (empty($all_users)) : ?>
                        <p style="color:#999;">Aucun autre utilisateur.</p>
                    <?php else : ?>
                        <?php foreach ($all_users as $u) : ?>
                            <label style="display:block; margin-bottom:5px;">
                                <input type="checkbox" name="shared_users[]" value="<?= $u->id ?>"
                                       class="user-checkbox">
                                <?= htmlspecialchars($u->prenom . ' ' . $u->nom) ?>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="modalSubmitBtn">Enregistrer</button>

            <button type="button" id="btnDeleteResource" class="btn-delete-trigger"
                    onclick="confirmDelete()" style="display:none;">
                🗑️ Supprimer cette ressource
            </button>
        </form>
    </div>
</div>

<!-- MODAL CONFIRMATION -->
<div id="deleteConfirmModal" class="modal" style="z-index: 200;">
    <div class="modal-content" style="max-width: 400px; border-color: #f44336;">
        <h3 style="color: #f44336; margin-top:0;">⚠️ Confirmation</h3>
        <p>Êtes-vous sûr de vouloir supprimer cette ressource ?</p>
        <p style="font-size:0.9em; color:#666;">
            Cela supprimera définitivement :
        </p>
        <ul style="font-size:0.9em; color:#666; margin-bottom:15px;">
            <li>La ressource</li>
            <li>Tous les exercices liés</li>
            <li>Toutes les tentatives des étudiants</li>
        </ul>
        <div class="confirm-buttons">
            <button class="btn-confirm-no" onclick="closeDeleteModal()">Annuler</button>
            <form action="/index.php?action=delete_resource" method="POST">
                <input type="hidden" name="resource_id" id="deleteResourceId" value="">
                <button type="submit" class="btn-confirm-yes">Oui, supprimer</button>
            </form>
        </div>
    </div>
</div>
<script>
    function openResourceModal(mode, btn = null) {
        const modal = document.getElementById('resourceModal');
        const form = document.getElementById('resourceForm');
        const hiddenId = document.getElementById('formResourceId');
        const deleteBtn = document.getElementById('btnDeleteResource');

        form.reset();
        hiddenId.value = '';
        document.getElementById('currentImageName').style.display = 'none';
        document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);

        if (mode === 'edit' && btn) {
            document.getElementById('modalTitle').textContent = "Modifier la ressource";
            document.getElementById('modalSubmitBtn').textContent = "Mettre à jour";
            deleteBtn.style.display = 'block';

            const card = btn.closest('.resource-card');
            hiddenId.value = card.dataset.id;
            document.getElementById('resourceName').value = card.dataset.name;
            document.getElementById('resourceDesc').value = card.dataset.description;

            if (card.dataset.image) {
                const p = document.getElementById('currentImageName');
                p.textContent = "Image actuelle : " + card.dataset.image;
                p.style.display = 'block';
            }
        } else {
            document.getElementById('modalTitle').textContent = "Nouvelle Ressource";
            document.getElementById('modalSubmitBtn').textContent = "Créer la ressource";
            deleteBtn.style.display = 'none';
        }
        modal.style.display = "block";
    }

    function closeResourceModal() {
        document.getElementById('resourceModal').style.display = "none";
    }

    function confirmDelete() {
        const resourceId = document.getElementById('formResourceId').value;
        document.getElementById('deleteResourceId').value = resourceId;
        document.getElementById('resourceModal').style.display = "none";
        document.getElementById('deleteConfirmModal').style.display = "block";
    }

    function closeDeleteModal() {
        document.getElementById('deleteConfirmModal').style.display = "none";
        document.getElementById('resourceModal').style.display = "block";
    }

    window.onclick = function(event) {
        const resModal = document.getElementById('resourceModal');
        const delModal = document.getElementById('deleteConfirmModal');
        if (event.target == resModal) {
            closeResourceModal();
        }
        if (event.target == delModal) {
            closeDeleteModal();
        }
    }

    function filterResources() {
        let input = document.getElementById('searchBar').value.toLowerCase();
        let type = document.getElementById('filterType').value;
        let cards = document.getElementsByClassName('resource-card');

        for (let card of cards) {
            let name = card.dataset.name.toLowerCase();
            let access = card.dataset.accessType;
            let show = true;
            if (!name.includes(input)) {
                show = false;
            }
            if (type !== 'all' && access !== type) {
                show = false;
            }
            card.style.display = show ? "flex" : "none";
        }
    }
</script>
</body>
</html>

