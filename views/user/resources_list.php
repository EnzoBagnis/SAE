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

$resources = Resource::getAllAccessibleResources($db, $user_id);

// Récupération de tous les utilisateurs pour la liste de partage
// (On exclut l'utilisateur courant)
$stmt_users = $db->prepare("SELECT id, prenom, nom FROM users WHERE id != :id ORDER BY nom ASC");
$stmt_users->execute([':id' => $user_id]);
$all_users = $stmt_users->fetchAll(PDO::FETCH_OBJ);

?>
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
    <script src="../public/js/dashboard-main.js"></script>

    <meta name="description" content="Gérez et visualisez vos ressources pédagogiques.">
    <meta name="robots" content="noindex, nofollow">

    <style>
        /* Styles Grid et Cartes */
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            padding: 20px;
            max-width: 1200px;
            margin: 20px auto;
        }

        .resource-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            color: inherit;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            display: flex;
            flex-direction: column;
            position: relative; /* Pour positionner le bouton edit */
        }

        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        /* Lien clicable sur toute la carte sauf le bouton edit */
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
            display: block;
        }
        .resource-card-image.placeholder {
            background-image: url('/images/placeholder_resource.jpg');
            background-size: cover;
            background-position: center;
        }

        .resource-card-content {
            padding: 15px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .resource-card-content h3 {
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 1.2em;
            color: #333;
        }

        .resource-card-content p {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 10px;
            flex-grow: 1;
        }

        .resource-card-owner {
            font-size: 0.8em;
            color: #999;
            text-align: right;
        }

        /* Bouton Edit sur la carte */
        .btn-edit-resource {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: background-color 0.2s;
        }
        .btn-edit-resource:hover {
            background-color: #f0f0f0;
        }

        .filter-bar {
            display: flex;
            justify-content: flex-start;
            gap: 10px;
            padding: 20px;
            background-color: #eef;
            border-bottom: 1px solid #ddd;
            flex-wrap: wrap;
        }

        .filter-bar select,
        .filter-bar input[type="text"] {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
        }

        /* Styles spécifiques pour le Modal Formulaire */
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .users-checklist {
            max-height: 150px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 4px;
            background: #fff;
        }
        .checklist-item {
            display: block;
            margin-bottom: 5px;
        }
        .btn-submit {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            width: 100%;
        }
        .btn-submit:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
<!-- Menu du haut -->
<header class="top-menu">
    <div class="logo">
        <h1>StudTraj</h1>
    </div>

    <button class="burger-menu" id="burgerBtn" onclick="toggleBurgerMenu()" aria-label="Menu">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <nav class="nav-menu">
        <a href="/index.php?action=resources_list" class="active">Ressources</a>
    </nav>
    <div class="user-info">
        <!-- Bouton Créer modifié -->
        <button onclick="openResourceModal('create')" class="btn-import-trigger">
            <svg width="20" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Créer une ressource
        </button>
        <span><?= htmlspecialchars($user_firstname ?? '') ?> <?= htmlspecialchars($user_lastname ?? '') ?></span>
        <button onclick="confirmLogout()" class="btn-logout">Déconnexion</button>
    </div>
</header>

<!-- Menu burger mobile -->
<nav class="burger-nav" id="burgerNav">
    <div class="burger-nav-content">
        <div class="burger-user-info">
            <span><?= htmlspecialchars($user_firstname) ?> <?= htmlspecialchars($user_lastname) ?></span>
        </div>
        <ul class="burger-menu-list">
            <li><a href="/index.php?action=dashboard" class="burger-link">Tableau de bord</a></li>
            <li><a href="/index.php?action=resources_list" class="burger-link active">Mes Ressources</a></li>
            <li><a href="/index.php?action=mentions" class="burger-link">Mentions légales</a></li>
            <li><a href="#" onclick="confirmLogout()" class="burger-link burger-logout">Déconnexion</a></li>
        </ul>
    </div>
</nav>

<div class="dashboard-container">
    <main class="main-content">
        <h2 style="padding: 20px 20px 0;">Vos ressources et celles partagées</h2>

        <!-- Filtres -->
        <div class="filter-bar">
            <select id="filterType" onchange="filterResources()">
                <option value="all">Toutes les ressources</option>
                <option value="owner">Mes ressources</option>
                <option value="shared">Ressources partagées</option>
            </select>
            <input type="text" id="searchBar" placeholder="Rechercher..." onkeyup="filterResources()">
            <select id="sortOrder" onchange="filterResources()">
                <option value="name_asc">Trier par nom (A-Z)</option>
                <option value="name_desc">Trier par nom (Z-A)</option>
                <option value="owner_name_asc">Trier par propriétaire (A-Z)</option>
            </select>
        </div>

        <div class="resources-grid" id="resourcesGrid">
            <?php if (!empty($resources)) : ?>
                <?php foreach ($resources as $resource) : ?>
                    <?php
                    $ownerFullName = $resource->owner_firstname . ' ' . $resource->owner_lastname;
                    // On vérifie si l'utilisateur est le propriétaire
                    $isOwner = ($resource->owner_id == $user_id);

                    // Simulation de la récupération des utilisateurs avec qui c'est partagé
                    // Note: Idéalement, votre modèle Resource devrait retourner un tableau d'IDs pour shared_users
                    // Ici on utilise un attribut data vide ou supposé si votre modèle le supporte
                    $sharedWithIds = $resource->shared_with_ids ?? ''; // Ex: "1,4,12"
                    ?>

                    <!-- Modification: Div au lieu de A pour gérer le bouton edit séparément -->
                    <div class="resource-card"
                         data-name="<?= htmlspecialchars($resource->resource_name) ?>"
                         data-owner="<?= htmlspecialchars($ownerFullName) ?>"
                         data-access-type="<?= htmlspecialchars($resource->access_type) ?>"
                         data-id="<?= $resource->resource_id ?>"
                         data-description="<?= htmlspecialchars($resource->description ?? '') ?>"
                         data-shared="<?= htmlspecialchars($sharedWithIds) ?>"
                         data-image="<?= htmlspecialchars($resource->image_path ?? '') ?>">

                        <!-- Bouton Edit (visible seulement si propriétaire) -->
                        <?php if ($isOwner) : ?>
                            <button class="btn-edit-resource" onclick="openResourceModal('edit', this)" title="Modifier">
                                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="color:#555">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                        <?php endif; ?>

                        <!-- Lien englobant le contenu -->
                        <a href="/index.php?action=dashboard&resource_id=<?= $resource->resource_id ?>" class="resource-link-wrapper">
                            <?php if (!empty($resource->image_path)) : ?>
                                <img src="/images/<?= htmlspecialchars($resource->image_path) ?>"
                                     alt="<?= htmlspecialchars($resource->resource_name) ?>"
                                     class="resource-card-image">
                            <?php else : ?>
                                <div class="resource-card-image placeholder"></div>
                            <?php endif; ?>

                            <div class="resource-card-content">
                                <h3><?= htmlspecialchars($resource->resource_name) ?></h3>
                                <p><?= htmlspecialchars($resource->description ?? 'Pas de description.') ?></p>
                                <span class="resource-card-owner">
                                        Par: <?= htmlspecialchars($ownerFullName) ?>
                                    </span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="placeholder-message">
                    Aucune ressource disponible pour le moment.
                </p>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal: Créer / Modifier une ressource -->
<div id="resourceModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeResourceModal()">&times;</span>
        <h2 id="modalTitle">Créer une ressource</h2>

        <!-- Le formulaire pointera vers votre routeur PHP -->
        <form id="resourceForm" action="/index.php?action=save_resource" method="POST" enctype="multipart/form-data">
            <!-- Champ caché pour l'ID si modification -->
            <input type="hidden" name="resource_id" id="formResourceId" value="">

            <div class="form-group">
                <label for="resourceName">Nom de la ressource :</label>
                <input type="text" id="resourceName" name="name" required placeholder="Ex: Cours de Maths">
            </div>

            <div class="form-group">
                <label for="resourceDesc">Description :</label>
                <textarea id="resourceDesc" name="description" rows="3" placeholder="Description du contenu..."></textarea>
            </div>

            <div class="form-group">
                <label for="resourceImage">Image (optionnel) :</label>
                <input type="file" id="resourceImage" name="image" accept="image/*">
                <p id="currentImageName" style="font-size:0.8em; color:#666; display:none;"></p>
            </div>

            <div class="form-group">
                <label>Partager avec :</label>
                <div class="users-checklist">
                    <?php foreach ($all_users as $u): ?>
                        <label class="checklist-item">
                            <input type="checkbox" name="shared_users[]" value="<?= $u->id ?>" class="user-checkbox">
                            <?= htmlspecialchars($u->prenom . ' ' . $u->nom) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="modalSubmitBtn">Créer la ressource</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<script>
    // --- Gestion du Modal Création / Edition ---

    function openResourceModal(mode, btnElement = null) {
        const modal = document.getElementById('resourceModal');
        const title = document.getElementById('modalTitle');
        const submitBtn = document.getElementById('modalSubmitBtn');
        const form = document.getElementById('resourceForm');

        // Reset du formulaire
        form.reset();
        document.getElementById('formResourceId').value = '';
        document.getElementById('currentImageName').style.display = 'none';

        // Décocher toutes les checkboxes
        document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);

        if (mode === 'edit' && btnElement) {
            // Mode Modification
            title.textContent = 'Modifier la ressource';
            submitBtn.textContent = 'Mettre à jour';

            // Récupération des données depuis la carte parente
            const card = btnElement.closest('.resource-card');
            const id = card.dataset.id;
            const name = card.dataset.name;
            const desc = card.dataset.description;
            const image = card.dataset.image;
            // On suppose que data-shared contient des IDs séparés par virgule: "1,5,10"
            const shared = card.dataset.shared ? card.dataset.shared.split(',') : [];

            // Remplissage des champs
            document.getElementById('formResourceId').value = id;
            document.getElementById('resourceName').value = name;
            document.getElementById('resourceDesc').value = desc;

            if (image) {
                const imgText = document.getElementById('currentImageName');
                imgText.textContent = "Image actuelle : " + image;
                imgText.style.display = 'block';
            }

            // Cocher les utilisateurs avec qui c'est partagé
            shared.forEach(userId => {
                const cb = document.querySelector(`.user-checkbox[value="${userId}"]`);
                if (cb) cb.checked = true;
            });

        } else {
            // Mode Création
            title.textContent = 'Nouvelle Ressource';
            submitBtn.textContent = 'Créer la ressource';
        }

        modal.style.display = "block";
    }

    function closeResourceModal() {
        document.getElementById('resourceModal').style.display = "none";
    }

    // --- Fonctions existantes (Filtres, Burger, Logout) ---

    function filterResources() {
        const searchText = document.getElementById('searchBar').value.toLowerCase();
        const filterType = document.getElementById('filterType').value;
        const sortOrder = document.getElementById('sortOrder').value;
        const grid = document.getElementById('resourcesGrid');
        let cards = Array.from(grid.getElementsByClassName('resource-card'));

        cards.forEach(card => {
            const name = card.dataset.name.toLowerCase();
            const owner = card.dataset.owner.toLowerCase();
            const accessType = card.dataset.accessType; // 'owner' ou 'shared'

            // Logique simplifiée pour accessType car PHP envoie le type réel
            // Si accessType n'est pas dans le dataset, on le déduit (optionnel)
            // Ici on suppose que le backend envoie "owner" si c'est à moi, "shared" sinon

            // Si accessType est null dans le data, on peut le déduire :
            // let actualAccess = (owner.includes("<?= strtolower($user_lastname) ?>")) ? 'owner' : 'shared';

            const matchesSearch = name.includes(searchText) || owner.includes(searchText);

            // Filtre Type : 'all', 'owner', 'shared'
            // Pour que cela fonctionne parfaitement, assurez-vous que $resource->access_type
            // renvoie bien 'owner' ou 'shared' depuis le PHP.
            // Sinon on peut utiliser la classe ou le nom du propriétaire pour filtrer.

            let matchesType = true;
            if(filterType === 'owner') {
                // Vérification simple via le nom ou un attribut data
                matchesType = (card.dataset.accessType === 'owner');
            } else if (filterType === 'shared') {
                matchesType = (card.dataset.accessType === 'shared');
            }

            if (matchesSearch && matchesType) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });

        cards.sort((a, b) => {
            const nameA = a.dataset.name.toLowerCase();
            const nameB = b.dataset.name.toLowerCase();
            const ownerA = a.dataset.owner.toLowerCase();
            const ownerB = b.dataset.owner.toLowerCase();

            if (sortOrder === 'name_asc') return nameA.localeCompare(nameB);
            if (sortOrder === 'name_desc') return nameB.localeCompare(nameA);
            if (sortOrder === 'owner_name_asc') return ownerA.localeCompare(ownerB);
            return 0;
        });

        cards.forEach(card => grid.appendChild(card));
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Events listeners
        const filterTypeElement = document.getElementById('filterType');
        if (filterTypeElement) filterTypeElement.addEventListener('change', filterResources);

        const searchBarElement = document.getElementById('searchBar');
        if (searchBarElement) searchBarElement.addEventListener('keyup', filterResources);

        const sortOrderElement = document.getElementById('sortOrder');
        if (sortOrderElement) sortOrderElement.addEventListener('change', filterResources);

        // Fermeture modal au clic en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('resourceModal');
            if (event.target == modal) {
                closeResourceModal();
            }
        }

        filterResources();
    });

    function toggleBurgerMenu() {
        const burgerNav = document.getElementById('burgerNav');
        burgerNav.classList.toggle('active');
        document.getElementById('burgerBtn').classList.toggle('open');
    }

    function confirmLogout() {
        if (confirm("Voulez-vous vraiment vous déconnecter ?")) {
            window.location.href = "/index.php?action=logout";
        }
    }
</script>
</body>
</html>