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
    <div class="logo"><h1>StudTraj</h1></div>
    <button class="burger-menu" id="burgerBtn" onclick="toggleBurgerMenu()" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
    <nav class="nav-menu">
        <a href="<?= BASE_URL ?>/resources" class="active">Ressources</a>
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
            <li><a href="<?= BASE_URL ?>/resources" class="burger-link active">Ressources</a></li>
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
     SECTION RECHERCHE DANS LES RESSOURCES
     ═══════════════════════════════════════ -->
<div class="dashboard-container" style="margin-top:0;">
    <main class="main-content">
        <h2 style="padding:20px 20px 0;">Recherche dans les ressources</h2>

        <!-- ── Barre de recherche globale (Élève / TP) ── -->
        <div style="margin:12px 20px 24px;background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.06);">
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <select id="globalSearchType" style="padding:8px 10px;border:1px solid #ddd;border-radius:4px;font-size:.9em;">
                    <option value="exercises">Travaux Pratiques</option>
                    <option value="students">Élève</option>
                </select>
                <input type="search" id="globalSearchInput"
                       placeholder="Rechercher un TP ou un étudiant par mot-clé…"
                       style="flex:1;min-width:200px;padding:8px 10px;border:1px solid #ddd;border-radius:4px;font-size:.9em;" />
                <button id="globalClearBtn"
                        style="padding:8px 14px;border-radius:4px;border:1px solid #ddd;background:#f5f5f5;font-size:.9em;cursor:pointer;">
                    Effacer
                </button>
            </div>
            <!-- Résultats -->
            <div id="globalSearchResults" style="margin-top:12px;display:none;">
                <!-- Résultats TP -->
                <div id="globalExercisesResults" style="display:none;">
                    <strong style="font-size:.9em;color:#444;">Travaux Pratiques trouvés :</strong>
                    <ul id="globalExercisesList"
                        style="list-style:none;padding:0;margin:8px 0 0;max-height:260px;overflow-y:auto;"></ul>
                </div>
                <!-- Résultats Élèves -->
                <div id="globalStudentsResults" style="display:none;">
                    <strong style="font-size:.9em;color:#444;">Étudiants trouvés :</strong>
                    <ul id="globalStudentsList"
                        style="list-style:none;padding:0;margin:8px 0 0;max-height:260px;overflow-y:auto;"></ul>
                </div>
            </div>
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

<!-- ═══════════════════════════════════════
     MODALE DÉTAIL ÉTUDIANT (recherche globale)
     ═══════════════════════════════════════ -->
<div id="globalStudentModal" class="modal" style="display:none;z-index:250;">
    <div class="modal-content" style="max-width:800px;">
        <span class="close" onclick="closeGlobalStudentModal()">&times;</span>
        <h3 id="globalStudentModalTitle" style="margin-top:0;">Détails étudiant</h3>
        <div id="globalStudentModalBody">Chargement…</div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const BASE = '<?= BASE_URL ?>';

    // ── helpers ──────────────────────────────────────────────────────────────
    function debounce(fn, ms) {
        let t;
        return function (...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
    }

    function liItem(text, onClick) {
        const li = document.createElement('li');
        li.style.cssText = 'padding:8px 10px;border-bottom:1px solid #f0f0f0;font-size:.9em;cursor:' + (onClick ? 'pointer' : 'default');
        li.textContent = text;
        if (onClick) {
            li.addEventListener('click', onClick);
            li.style.color = '#007bff';
        }
        return li;
    }

    function showEmpty(ul, msg) {
        ul.innerHTML = '';
        ul.appendChild(liItem(msg));
    }

    // ── DOM ──────────────────────────────────────────────────────────────────
    const inputEl     = document.getElementById('globalSearchInput');
    const typeEl      = document.getElementById('globalSearchType');
    const clearBtn    = document.getElementById('globalClearBtn');
    const resultsDiv  = document.getElementById('globalSearchResults');
    const exDiv       = document.getElementById('globalExercisesResults');
    const exList      = document.getElementById('globalExercisesList');
    const stDiv       = document.getElementById('globalStudentsResults');
    const stList      = document.getElementById('globalStudentsList');

    const modal       = document.getElementById('globalStudentModal');
    const modalTitle  = document.getElementById('globalStudentModalTitle');
    const modalBody   = document.getElementById('globalStudentModalBody');

    // ── search ───────────────────────────────────────────────────────────────
    async function doSearch() {
        const q    = (inputEl.value || '').trim().toLowerCase();
        const type = typeEl.value;

        if (!q) {
            resultsDiv.style.display = 'none';
            exDiv.style.display = 'none';
            stDiv.style.display = 'none';
            return;
        }

        resultsDiv.style.display = 'block';

        if (type === 'exercises') {
            stDiv.style.display  = 'none';
            exDiv.style.display  = 'block';
            exList.innerHTML     = '<li style="color:#888;padding:8px;">Chargement…</li>';

            try {
                const resp = await fetch(`${BASE}/api/dashboard/exercises`);
                if (!resp.ok) throw new Error('API error');
                const json = await resp.json();
                const exercises = (json.data && json.data.exercises) ? json.data.exercises : [];

                const matches = exercises.filter(e => {
                    const hay = ((e.funcname || '') + ' ' + (e.exo_name || '') + ' ' + (e.extention || '')).toLowerCase();
                    return hay.includes(q);
                });

                exList.innerHTML = '';
                if (!matches.length) {
                    showEmpty(exList, 'Aucun TP trouvé pour ce mot-clé.');
                } else {
                    matches.forEach(e => {
                        const id   = e.exercise_id || e.exercice_id;
                        const name = e.funcname || e.exo_name || 'TP sans titre';
                        const li   = liItem(name, () => {
                            window.location.href = `${BASE}/exercises/${id}`;
                        });
                        exList.appendChild(li);
                    });
                }
            } catch (err) {
                showEmpty(exList, 'Erreur lors du chargement des exercices.');
                console.error(err);
            }

        } else {
            // students
            exDiv.style.display  = 'none';
            stDiv.style.display  = 'block';
            stList.innerHTML     = '<li style="color:#888;padding:8px;">Chargement…</li>';

            try {
                const resp = await fetch(`${BASE}/api/dashboard/students?page=1&perPage=100000`);
                if (!resp.ok) throw new Error('API error');
                const json = await resp.json();
                const students = (json.data && json.data.students) ? json.data.students : [];

                const matches = students.filter(s =>
                    (s.title || s.identifier || s.id || '').toLowerCase().includes(q)
                );

                stList.innerHTML = '';
                if (!matches.length) {
                    showEmpty(stList, 'Aucun étudiant trouvé pour ce mot-clé.');
                } else {
                    matches.forEach(s => {
                        const label = s.title || s.identifier || s.id;
                        const sid   = s.id || s.identifier || s.title;
                        const li    = liItem(label, () => openStudentDetail(sid));
                        stList.appendChild(li);
                    });
                }
            } catch (err) {
                showEmpty(stList, 'Erreur lors du chargement des étudiants.');
                console.error(err);
            }
        }
    }

    const debouncedSearch = debounce(doSearch, 300);

    inputEl.addEventListener('input', debouncedSearch);
    typeEl.addEventListener('change', debouncedSearch);
    clearBtn.addEventListener('click', () => { inputEl.value = ''; doSearch(); });

    // ── student detail modal ─────────────────────────────────────────────────
    async function openStudentDetail(studentId) {
        modal.style.display  = 'flex';
        modalTitle.textContent = `Étudiant : ${studentId}`;
        modalBody.innerHTML  = '<p style="color:#888;">Chargement…</p>';

        try {
            const resp = await fetch(`${BASE}/api/dashboard/student/${encodeURIComponent(studentId)}`);
            if (!resp.ok) throw new Error('API error');
            const json = await resp.json();

            if (!json.success) {
                modalBody.innerHTML = '<p style="color:#888;">Aucune donnée disponible.</p>';
                return;
            }

            const attempts = json.data.attempts || [];
            if (!attempts.length) {
                modalBody.innerHTML = '<p style="color:#666;">Aucune tentative enregistrée pour cet étudiant.</p>';
                return;
            }

            let html = '<ul style="list-style:none;padding:0;max-height:420px;overflow:auto;">';
            attempts.forEach(a => {
                const ok = a.correct ? '✅' : '❌';
                html += `<li style="padding:8px 0;border-bottom:1px solid #f0f0f0;">
                            ${ok} <strong>${a.exercice_name || 'Exercice'}</strong>
                            ${a.ressource_name ? `<small style="color:#888;"> — ${a.ressource_name}</small>` : ''}
                         </li>`;
            });
            html += '</ul>';
            modalBody.innerHTML = html;
        } catch (err) {
            console.error(err);
            modalBody.innerHTML = '<p style="color:#e74c3c;">Erreur lors du chargement.</p>';
        }
    }

    window.closeGlobalStudentModal = function () {
        modal.style.display = 'none';
        modalBody.innerHTML = '';
    };

    modal.addEventListener('click', e => { if (e.target === modal) window.closeGlobalStudentModal(); });

})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>
