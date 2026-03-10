<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('BASE_URL')) {
    define('BASE_URL', '');
}

$user_firstname = $_SESSION['user_firstname'] ?? 'Utilisateur';
$user_lastname  = $_SESSION['user_lastname']  ?? '';
$initials = strtoupper(substr($user_firstname, 0, 1) . substr($user_lastname, 0, 1));
$title = 'StudTraj - Mes Ressources';

// Fusionner ressources possédées + partagées
$allResources = array_merge($owned_resources ?? [], $shared_resources ?? []);
$allTeachers  = $all_teachers ?? [];

// Message d'erreur passé en GET (ex. validation)
$errorMsg = htmlspecialchars($_GET['error'] ?? '');
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
    <script type="module" src="<?= BASE_URL ?>/public/js/dashboard-main.js"></script>
    <meta name="description" content="Liste des ressources disponibles sur StudTraj.">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
<header class="top-menu">
    <div class="logo">
        <a href="<?= BASE_URL ?>/resources" style="text-decoration:none;color:inherit;">
            <h1>StudTraj</h1>
        </a>
    </div>
    <button class="burger-menu" id="burgerBtn" onclick="toggleBurgerMenu()" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
    <nav class="nav-menu">
    </nav>
    <div class="header-right">
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
            <li><a href="#" onclick="confirmLogout()" class="burger-link burger-logout">Déconnexion</a></li>
        </ul>
    </div>
</nav>

<div class="dashboard-container">
    <main class="main-content">
        <h2 style="padding: 20px 20px 0;">Tableau de bord</h2>

        <?php if ($errorMsg !== '') : ?>
            <div style="margin:12px 20px;padding:12px 16px;background:#ffebee;color:#c62828;
                        border:1px solid #ef9a9a;border-radius:6px;font-size:14px;">
                <?= $errorMsg ?>
            </div>
        <?php endif; ?>

        <!-- Barre filtre + bouton Nouvelle Ressource -->
        <div class="filter-bar">
            <div class="filter-group-left">
                <input class="searchBar" type="text" id="searchBar"
                       placeholder="Rechercher une ressource..." onkeyup="filterResources()">
                <select id="filterType" onchange="filterResources()">
                    <option value="all">Tout voir</option>
                    <option value="owner">Mes créations</option>
                    <option value="shared">Partagées avec moi</option>
                </select>
            </div>
            <button onclick="openResourceModal('create')" class="btn-create-resource">
                <svg style="width:18px;height:18px;" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Nouvelle Ressource
            </button>
        </div>


        <!-- Grille des ressources -->
        <div class="resources-grid" id="resourcesGrid">
            <?php if (!empty($allResources)) : ?>
                <?php foreach ($allResources as $resource) : ?>
                    <?php
                    $isOwner     = $resource->getAccessType() === 'owner';
                    $resId       = (int)$resource->getResourceId();
                    $resName     = $resource->getResourceName();
                    $resDesc     = $resource->getDescription() ?? '';
                    $resImg      = $resource->getImagePath() ?? '';
                    $ownerMail   = $resource->getOwnerMail();
                    $sharedMails = $resource->getSharedMails() ?? '';
                    ?>
                    <div class="resource-card"
                         data-name="<?= htmlspecialchars($resName) ?>"
                         data-owner="<?= htmlspecialchars($ownerMail) ?>"
                         data-access-type="<?= $isOwner ? 'owner' : 'shared' ?>"
                         data-id="<?= $resId ?>"
                         data-description="<?= htmlspecialchars($resDesc) ?>"
                         data-image="<?= htmlspecialchars($resImg) ?>"
                         data-shared-mails="<?= htmlspecialchars($sharedMails) ?>">

                        <?php if ($isOwner) : ?>
                            <button class="btn-edit-resource"
                                    onclick="openResourceModal('edit', this)"
                                    title="Modifier"></button>
                        <?php endif; ?>

                        <a href="<?= BASE_URL ?>/resources/<?= $resId ?>" class="resource-link-wrapper">
                            <?php if ($resImg !== '') : ?>
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($resImg) ?>"
                                     class="resource-card-image"
                                     alt="<?= htmlspecialchars($resName) ?>">
                            <?php else : ?>
                                <div class="resource-card-image"
                                     style="background:#e8eaf6;display:flex;align-items:center;
                                            justify-content:center;color:#9fa8da;font-size:13px;">
                                    Pas d'image
                                </div>
                            <?php endif; ?>
                            <div class="resource-card-content">
                                <h3><?= htmlspecialchars($resName) ?></h3>
                                <?php if ($resDesc !== '') : ?>
                                    <p><?= htmlspecialchars($resDesc) ?></p>
                                <?php endif; ?>
                                <span class="resource-card-owner">
                                    Par : <?= htmlspecialchars($ownerMail) ?>
                                </span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p style="padding:20px;color:#777;">Aucune ressource trouvée.</p>
            <?php endif; ?>
        </div>
    </main>
</div>


<!-- ═══════════════════════════════════════
     MODAL PRINCIPAL (Création / Édition)
     ═══════════════════════════════════════ -->
<div id="resourceModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeResourceModal()">&times;</span>
        <h2 id="modalTitle">Nouvelle Ressource</h2>

        <!-- Le formulaire pointe vers /resources (POST = store) par défaut ;
             en mode édition, l'action est remplacée en JS. -->
        <form id="resourceForm" action="<?= BASE_URL ?>/resources"
              method="POST" enctype="multipart/form-data">

            <input type="hidden" name="resource_id" id="formResourceId" value="">

            <div class="form-group">
                <label for="resourceName">Nom :</label>
                <input type="text" id="resourceName" name="name" required>
            </div>

            <div class="form-group">
                <label for="resourceDesc">Description :</label>
                <textarea id="resourceDesc" name="description" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label>Image :</label>
                <input type="file" name="image" accept="image/*">
                <p id="currentImageName" style="font-size:.8em;color:#666;display:none;"></p>
            </div>

            <?php if (!empty($allTeachers)) : ?>
            <div class="form-group">
                <label>Partager avec :</label>
                <input type="text" id="teacherSearch" placeholder="Filtrer les noms..."
                       style="width:100%;padding:8px;margin-bottom:10px;
                              border:1px solid #ddd;border-radius:4px;"
                       onkeyup="filterTeachersInModal()">
                <div class="users-checklist" id="teachersChecklist"
                     style="max-height:150px;overflow-y:auto;border:1px solid #eee;
                            padding:10px;border-radius:4px;">
                    <?php foreach ($allTeachers as $t) : ?>
                        <label class="user-item" style="display:block;margin-bottom:5px;">
                            <input type="checkbox" name="shared_teachers[]"
                                   value="<?= htmlspecialchars($t['mail']) ?>"
                                   class="teacher-checkbox">
                            <span class="user-name">
                                <?= htmlspecialchars(($t['name'] ?? '') . ' ' . ($t['surname'] ?? '')) ?>
                                <small style="color:#888;">(<?= htmlspecialchars($t['mail']) ?>)</small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn-submit" id="modalSubmitBtn">Créer la ressource</button>

            <button type="button" id="btnDeleteResource" class="btn-delete-trigger"
                    onclick="confirmDelete()" style="display:none;">
                🗑 Supprimer cette ressource
            </button>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════
     MODAL CONFIRMATION SUPPRESSION
     ═══════════════════════════════════════ -->
<div id="deleteConfirmModal" class="modal" style="z-index:200;">
    <div class="modal-content" style="max-width:400px;border-color:#f44336;">
        <h3 style="color:#f44336;margin-top:0;">⚠ Confirmation</h3>
        <p>Êtes-vous sûr de vouloir supprimer cette ressource ?</p>
        <p style="font-size:.9em;color:#666;">Cela supprimera définitivement :</p>
        <ul style="font-size:.9em;color:#666;margin-bottom:15px;">
            <li>La ressource</li>
            <li>Tous les exercices liés</li>
            <li>Toutes les tentatives des étudiants</li>
        </ul>
        <div class="confirm-buttons">
            <button class="btn-confirm-no" onclick="closeDeleteModal()">Annuler</button>
            <form id="deleteForm" action="" method="POST" style="display:inline;">
                <button type="submit" class="btn-confirm-yes">Oui, supprimer</button>
            </form>
        </div>
    </div>
</div>

<script>
// ─── Ouvrir la modale ────────────────────────────────────────────────────────
function openResourceModal(mode, btn = null) {
    const modal      = document.getElementById('resourceModal');
    const form       = document.getElementById('resourceForm');
    const hiddenId   = document.getElementById('formResourceId');
    const deleteBtn  = document.getElementById('btnDeleteResource');
    const submitBtn  = document.getElementById('modalSubmitBtn');
    const baseUrl    = '<?= BASE_URL ?>';

    // Reset
    form.reset();
    hiddenId.value = '';
    document.getElementById('currentImageName').style.display = 'none';
    if (document.getElementById('teacherSearch')) {
        document.getElementById('teacherSearch').value = '';
        filterTeachersInModal();
    }
    document.querySelectorAll('.teacher-checkbox').forEach(cb => cb.checked = false);

    if (mode === 'edit' && btn) {
        const card = btn.closest('.resource-card');
        const resId = card.dataset.id;

        document.getElementById('modalTitle').textContent = 'Modifier la ressource';
        submitBtn.textContent = 'Mettre à jour';
        deleteBtn.style.display = 'block';

        // Action = route update
        form.action = baseUrl + '/resources/' + resId + '/update';
        hiddenId.value = resId;

        document.getElementById('resourceName').value = card.dataset.name;
        document.getElementById('resourceDesc').value = card.dataset.description;

        // Image courante
        if (card.dataset.image) {
            const p = document.getElementById('currentImageName');
            p.textContent = 'Image actuelle : ' + card.dataset.image;
            p.style.display = 'block';
        }

        // Cocher les partages existants
        if (card.dataset.sharedMails) {
            const mails = card.dataset.sharedMails.split(',');
            document.querySelectorAll('.teacher-checkbox').forEach(cb => {
                cb.checked = mails.includes(cb.value.trim());
            });
        }
    } else {
        document.getElementById('modalTitle').textContent = 'Nouvelle Ressource';
        submitBtn.textContent = 'Créer la ressource';
        deleteBtn.style.display = 'none';
        form.action = baseUrl + '/resources';
    }

    modal.style.display = 'block';
}

// ─── Fermer la modale ────────────────────────────────────────────────────────
function closeResourceModal() {
    document.getElementById('resourceModal').style.display = 'none';
}

// ─── Demande confirmation de suppression ─────────────────────────────────────
function confirmDelete() {
    const resId   = document.getElementById('formResourceId').value;
    const baseUrl = '<?= BASE_URL ?>';
    document.getElementById('deleteForm').action = baseUrl + '/resources/' + resId + '/delete';
    document.getElementById('resourceModal').style.display = 'none';
    document.getElementById('deleteConfirmModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteConfirmModal').style.display = 'none';
    document.getElementById('resourceModal').style.display = 'block';
}

// ─── Fermer en cliquant en dehors ────────────────────────────────────────────
window.onclick = function (event) {
    const resModal = document.getElementById('resourceModal');
    const delModal = document.getElementById('deleteConfirmModal');
    if (event.target === resModal) closeResourceModal();
    if (event.target === delModal) closeDeleteModal();
};

// ─── Filtrage des ressources (barre de recherche) ────────────────────────────
function filterResources() {
    const searchText = document.getElementById('searchBar').value.toLowerCase();
    const filterType = document.getElementById('filterType').value;
    const cards      = Array.from(document.getElementsByClassName('resource-card'));

    cards.forEach(card => {
        const name       = card.dataset.name.toLowerCase();
        const owner      = card.dataset.owner.toLowerCase();
        const accessType = card.dataset.accessType;

        const matchesSearch = name.includes(searchText) || owner.includes(searchText);
        const matchesType   = (filterType === 'all' || accessType === filterType);

        card.style.display = (matchesSearch && matchesType) ? 'flex' : 'none';
    });
}

// ─── Filtrage des enseignants dans la modale ─────────────────────────────────
function filterTeachersInModal() {
    const input = document.getElementById('teacherSearch')?.value.toLowerCase() ?? '';
    document.querySelectorAll('.user-item').forEach(item => {
        const name = item.querySelector('.user-name').textContent.toLowerCase();
        item.style.display = name.includes(input) ? 'block' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>
