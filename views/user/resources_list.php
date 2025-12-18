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
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/dashboard.css">

    <style>
        /* --- GLOBAL & LAYOUT --- */
        body {
            background-color: #f4f6f9;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* --- HEADER AMÉLIORÉ --- */
        .top-menu {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #2c3e50;
            color: white;
            padding: 0 30px;
            height: 70px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .logo h1 {
            margin: 0;
            font-size: 1.5em;
            color: #ecf0f1;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background-color: #3498db;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1em;
            border: 2px solid rgba(255,255,255,0.2);
        }

        .user-name {
            font-weight: 500;
            font-size: 1em;
        }

        .btn-logout {
            background-color: rgba(255,255,255,0.1);
            color: #ecf0f1;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-logout:hover {
            background-color: #e74c3c;
            color: white;
        }

        /* --- BARRE DE FILTRES ET BOUTON ACTION --- */
        .filter-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            gap: 20px;
            flex-wrap: wrap; /* Permet de passer à la ligne sur mobile */
        }

        .search-group {
            display: flex;
            gap: 10px;
            flex: 1; /* Prend toute la place dispo à gauche */
            min-width: 300px;
        }

        .search-group input {
            flex-grow: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-group input:focus {
            border-color: #3498db;
        }

        .search-group select {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f8f9fa;
            cursor: pointer;
        }

        .btn-create-resource {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px rgba(52, 152, 219, 0.2);
            transition: transform 0.2s, box-shadow 0.2s;
            white-space: nowrap; /* Empêche le texte de passer à la ligne */
        }

        .btn-create-resource:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(52, 152, 219, 0.3);
        }

        /* --- GRILLE DES RESSOURCES --- */
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .resource-card {
            background-color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: relative;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            border: 1px solid #eee;
        }

        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            border-color: #b3d7ff;
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
            background-color: #f1f3f5;
        }

        .resource-card-content {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .resource-card-content h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 1.25em;
        }

        .resource-card-content p {
            color: #7f8c8d;
            font-size: 0.95em;
            line-height: 1.5;
            margin-bottom: 15px;
            flex-grow: 1;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #eee;
            padding-top: 15px;
            font-size: 0.85em;
            color: #95a5a6;
        }

        .badge-shared {
            background-color: #e1f5fe;
            color: #039be5;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.8em;
        }

        .btn-edit-resource {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            color: #2c3e50;
            transition: all 0.2s;
        }

        .btn-edit-resource:hover {
            background-color: #3498db;
            color: white;
            transform: scale(1.1);
        }

        /* --- MODAL --- */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 550px;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            color: #aaa;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
        }
        .close:hover { color: #2c3e50; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #34495e; }
        .form-group input[type="text"], .form-group textarea {
            width: 100%;
            padding: 12px;
            box-sizing: border-box;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        .form-group input[type="text"]:focus, .form-group textarea:focus {
            border-color: #3498db;
            outline: none;
        }

        .users-checklist {
            max-height: 200px;
            overflow-y: auto;
            border: 2px solid #ecf0f1;
            padding: 15px;
            border-radius: 8px;
            background: #f9f9f9;
        }

        .btn-submit {
            background-color: #27ae60;
            color: white;
            padding: 14px;
            border: none;
            width: 100%;
            cursor: pointer;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            margin-top: 10px;
            transition: background 0.2s;
        }
        .btn-submit:hover { background-color: #219150; }

        .btn-delete-trigger {
            background-color: transparent;
            color: #e74c3c;
            padding: 12px;
            border: 2px solid #e74c3c;
            width: 100%;
            cursor: pointer;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-delete-trigger:hover {
            background-color: #e74c3c;
            color: white;
        }

        /* Confirmation Modal */
        .confirm-buttons { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; }
        .btn-confirm-yes { background-color: #e74c3c; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-confirm-no { background-color: #ecf0f1; color: #2c3e50; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: bold; }

    </style>
    <script src="../public/js/dashboard-main.js"></script>
</head>
<body>

<!-- En-tête -->
<header class="top-menu">
    <div class="logo"><h1>StudTraj</h1></div>
    <div class="user-info">
        <div class="user-profile">
            <div class="user-avatar">
                <?= htmlspecialchars($initials) ?>
            </div>
            <span class="user-name"><?= htmlspecialchars($user_firstname) ?> <?= htmlspecialchars($user_lastname) ?></span>
        </div>
        <a href="/index.php?action=logout" class="btn-logout">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Déconnexion
        </a>
    </div>
</header>

<div class="dashboard-container">
    <main class="main-content">
        <h2 style="padding: 30px 0 10px; color: #2c3e50;">Mes Ressources</h2>

        <!-- Barre d'outils (Filtres + Bouton Créer) -->
        <div class="filter-section">
            <div class="search-group">
                <input type="text" id="searchBar" placeholder="Rechercher par nom..." onkeyup="filterResources()">
                <select id="filterType" onchange="filterResources()">
                    <option value="all">Toutes les ressources</option>
                    <option value="owner">Mes créations</option>
                    <option value="shared">Partagées avec moi</option>
                </select>
            </div>

            <button onclick="openResourceModal('create')" class="btn-create-resource">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Nouvelle Ressource
            </button>
        </div>

        <!-- Grille des cartes -->
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
                                    title="Modifier">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                        <?php endif; ?>

                        <a href="/index.php?action=dashboard&resource_id=<?= $resId ?>"
                           class="resource-link-wrapper">
                            <?php if (!empty($resImg)) : ?>
                                <img src="/images/<?= htmlspecialchars($resImg) ?>"
                                     class="resource-card-image" alt="Image">
                            <?php else : ?>
                                <div class="resource-card-image"
                                     style="background:#eee; display:flex; align-items:center;
                                            justify-content:center; color:#95a5a6; font-size:3em;">
                                    📚
                                </div>
                            <?php endif; ?>

                            <div class="resource-card-content">
                                <h3><?= htmlspecialchars($resName) ?></h3>
                                <p><?= htmlspecialchars(substr($resDesc, 0, 100)) ?><?= strlen($resDesc) > 100 ? '...' : '' ?></p>

                                <div class="card-footer">
                                    <span><?= htmlspecialchars($ownerName) ?></span>
                                    <?php if(!$isOwner): ?>
                                        <span class="badge-shared">Partagé</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div style="text-align:center; width:100%; grid-column: 1 / -1; padding: 40px; color: #7f8c8d;">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="color:#bdc3c7; margin-bottom:10px;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                    <p>Aucune ressource trouvée pour le moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- MODAL PRINCIPAL -->
<div id="resourceModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeResourceModal()">&times;</span>
        <h2 id="modalTitle" style="margin-top:0; color:#2c3e50;">Nouvelle Ressource</h2>

        <form id="resourceForm" action="/index.php?action=save_resource"
              method="POST" enctype="multipart/form-data">
            <input type="hidden" name="resource_id" id="formResourceId" value="">

            <div class="form-group">
                <label>Nom de la ressource</label>
                <input type="text" id="resourceName" name="name" required placeholder="Ex: Cours de Programmation">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea id="resourceDesc" name="description" rows="4" placeholder="De quoi parle ce cours ?"></textarea>
            </div>

            <div class="form-group">
                <label>Image de couverture (optionnel)</label>
                <input type="file" name="image" accept="image/*">
                <p id="currentImageName" style="font-size:0.85em; color:#3498db; margin-top:5px; display:none;"></p>
            </div>

            <div class="form-group">
                <label>Partager avec les collègues</label>
                <div class="users-checklist">
                    <?php if (empty($all_users)): ?>
                        <p style="color:#999; font-style:italic;">Aucun autre utilisateur enregistré.</p>
                    <?php else: ?>
                        <?php foreach ($all_users as $u): ?>
                            <label style="display:flex; align-items:center; margin-bottom:8px; cursor:pointer;">
                                <input type="checkbox" name="shared_users[]" value="<?= $u->id ?>"
                                       class="user-checkbox" style="width:auto; margin-right:10px;">
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
<div id="deleteConfirmModal" class="modal" style="z-index: 1100;">
    <div class="modal-content" style="max-width: 400px; text-align:center;">
        <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#e74c3c" stroke-width="2" style="margin-bottom:15px;">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
        <h3 style="color: #c0392b; margin:0 0 10px 0;">Suppression définitive</h3>
        <p>Attention, cette action est irréversible. Toutes les données liées (exercices, notes) seront perdues.</p>

        <div class="confirm-buttons" style="justify-content:center;">
            <button class="btn-confirm-no" onclick="closeDeleteModal()">Annuler</button>
            <form action="/index.php?action=delete_resource" method="POST">
                <input type="hidden" name="resource_id" id="deleteResourceId" value="">
                <button type="submit" class="btn-confirm-yes">Confirmer la suppression</button>
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

            // Correction affichage Flex
            if (show) {
                card.style.display = "flex";
            } else {
                card.style.display = "none";
            }
        }
    }
</script>
</body>
</html>