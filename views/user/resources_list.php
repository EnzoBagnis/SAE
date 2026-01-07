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
    <link rel="stylesheet" href="/public/css/footer.css">
    <script src="../public/js/dashboard-main.js"></script>
</head>
<body>
<header class="top-menu">
    <div class="logo"><h1>StudTraj</h1></div>
    <div class="user-info">
        <!-- Affichage Profil -->
        <div class="user-profile">
            <div class="user-avatar">
                <?= htmlspecialchars($initials) ?>
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
                <input class="searchBar"type="text"id="searchBar"placeholder="Rechercher..."onkeyup="filterResources()">
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
                        data-shared-users="<?= htmlspecialchars($resource->shared_user_ids ?? '') ?>">

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

            <div class="form-group">
                <label>Description :</label>
                <textarea id="resourceDesc" name="description" rows="3"></textarea>
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

        // 1. On décoche tout par défaut
        const checkboxes = document.querySelectorAll('.user-checkbox');
        checkboxes.forEach(cb => cb.checked = false);

        if (mode === 'edit' && btn) {
            document.getElementById('modalTitle').textContent = "Modifier la ressource";
            document.getElementById('modalSubmitBtn').textContent = "Mettre à jour";
            deleteBtn.style.display = 'block';

            const card = btn.closest('.resource-card');
            hiddenId.value = card.dataset.id;
            document.getElementById('resourceName').value = card.dataset.name;
            document.getElementById('resourceDesc').value = card.dataset.description;

            // 2. ON COCHE LES UTILISATEURS DÉJÀ PARTAGÉS
            if (card.dataset.sharedUsers) {
                // On transforme la chaîne "1,5,8" en tableau ["1", "5", "8"]
                const sharedIds = card.dataset.sharedUsers.split(',');

                checkboxes.forEach(cb => {
                    if (sharedIds.includes(cb.value)) {
                        cb.checked = true;
                    }
                });
            }

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
<?php include __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>
