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

// Récupération des ressources via le Modèle
$resources = Resource::getAllAccessibleResources($db, $user_id);

// --- RECUPERATION DES PARTENAIRES (Table 'utilisateur') ---
$all_users = [];
try {
    // Correction ici : on utilise la table 'utilisateur' au lieu de 'users'
    $stmt_users = $db->prepare("SELECT id, prenom, nom FROM utilisateur WHERE id != :id ORDER BY nom ASC");
    $stmt_users->execute([':id' => $user_id]);
    $all_users = $stmt_users->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    // Si la table 'utilisateur' n'existe pas non plus, on laisse la liste vide sans planter
    // Vous pouvez tester 'users' ou 'compte' ici si besoin
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
        /* CSS Dashboard */
        .resources-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; padding: 20px; max-width: 1200px; margin: 20px auto; }
        .resource-card { background-color: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); position: relative; display: flex; flex-direction: column; transition: transform 0.2s; }
        .resource-card:hover { transform: translateY(-5px); box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        .resource-link-wrapper { text-decoration: none; color: inherit; display: flex; flex-direction: column; flex-grow: 1; }
        .resource-card-image { width: 100%; height: 180px; object-fit: cover; background-color: #f0f0f0; }
        .resource-card-content { padding: 15px; }
        .resource-card-owner { font-size: 0.8em; color: #999; display:block; text-align: right; margin-top:10px; }

        /* Bouton Edit (Crayon) */
        .btn-edit-resource { position: absolute; top: 10px; right: 10px; background: white; border: 1px solid #ddd; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 10; }
        .btn-edit-resource:hover { background-color: #f0f0f0; }

        /* Filtres */
        .filter-bar { padding: 20px; background: #eef; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .filter-bar input, .filter-bar select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }

        /* Modal */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 500px; border-radius: 8px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: black; }

        /* Formulaire */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"], .form-group textarea { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .users-checklist { max-height: 150px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; background: #fafafa; }
        .btn-submit { background-color: #4CAF50; color: white; padding: 12px; border: none; width: 100%; cursor: pointer; border-radius: 4px; font-size: 16px; margin-top: 10px; }
        .btn-submit:hover { background-color: #45a049; }
    </style>
    <script src="../public/js/dashboard-main.js"></script>
</head>
<body>
<header class="top-menu">
    <div class="logo"><h1>StudTraj</h1></div>
    <div class="user-info">
        <!-- Bouton pour ouvrir le modal -->
        <button onclick="openResourceModal('create')" style="cursor:pointer; padding:5px 10px;">
            ➕ Créer une ressource
        </button>
        <span style="margin: 0 10px;"><?= htmlspecialchars($user_firstname) ?></span>
        <a href="/index.php?action=logout" style="color:white; text-decoration:none;">Déconnexion</a>
    </div>
</header>

<div class="dashboard-container">
    <main class="main-content">
        <h2 style="padding: 20px 20px 0;">Tableau de bord</h2>

        <!-- Barre de recherche et filtres -->
        <div class="filter-bar">
            <input type="text" id="searchBar" placeholder="Rechercher..." onkeyup="filterResources()">
            <select id="filterType" onchange="filterResources()">
                <option value="all">Tout voir</option>
                <option value="owner">Mes créations</option>
                <option value="shared">Partagées avec moi</option>
            </select>
        </div>

        <div class="resources-grid" id="resourcesGrid">
            <?php if (!empty($resources)) : ?>
                <?php foreach ($resources as $resource) : ?>
                    <?php
                    // 1. CORRECTION NOM COLONNE PROPRIETAIRE
                    // On vérifie plusieurs noms possibles pour éviter l'erreur "Undefined property"
                    $creatorId = $resource->id_createur
                        ?? $resource->owner_id
                        ?? $resource->id_utilisateur
                        ?? 0;

                    // 2. CORRECTION NOM COLONNES RESSOURCE
                    $resId = $resource->resource_id ?? $resource->id;
                    $resName = $resource->resource_name ?? $resource->titre ?? 'Sans titre';
                    $resDesc = $resource->description ?? '';
                    $resImg = $resource->image_path ?? $resource->image ?? '';

                    // Est-ce que c'est ma ressource ?
                    $isOwner = ($creatorId == $user_id);

                    // Nom du propriétaire pour l'affichage
                    $ownerName = ($resource->owner_firstname ?? '') . ' ' . ($resource->owner_lastname ?? '');
                    if (trim($ownerName) == '') $ownerName = "Utilisateur $creatorId";
                    ?>

                    <div class="resource-card"
                         data-name="<?= htmlspecialchars($resName) ?>"
                         data-owner="<?= htmlspecialchars($ownerName) ?>"
                         data-access-type="<?= $isOwner ? 'owner' : 'shared' ?>"
                         data-id="<?= $resId ?>"
                         data-description="<?= htmlspecialchars($resDesc) ?>"
                         data-image="<?= htmlspecialchars($resImg) ?>">

                        <?php if ($isOwner) : ?>
                            <button class="btn-edit-resource" onclick="openResourceModal('edit', this)" title="Modifier">✏️</button>
                        <?php endif; ?>

                        <a href="/index.php?action=dashboard&resource_id=<?= $resId ?>" class="resource-link-wrapper">
                            <?php if(!empty($resImg)) : ?>
                                <img src="/images/<?= htmlspecialchars($resImg) ?>" class="resource-card-image" alt="Image">
                            <?php else : ?>
                                <div class="resource-card-image" style="background:#eee; display:flex; align-items:center; justify-content:center; color:#777;">
                                    Pas d'image
                                </div>
                            <?php endif; ?>

                            <div class="resource-card-content">
                                <h3><?= htmlspecialchars($resName) ?></h3>
                                <p><?= htmlspecialchars($resDesc) ?></p>
                                <span class="resource-card-owner">Par: <?= htmlspecialchars($ownerName) ?></span>
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

<!-- MODAL (Formulaire de Création / Edition) -->
<div id="resourceModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeResourceModal()">&times;</span>
        <h2 id="modalTitle">Nouvelle Ressource</h2>

        <!-- ACTION DU FORMULAIRE : pointe vers save_resource -->
        <form id="resourceForm" action="/index.php?action=save_resource" method="POST" enctype="multipart/form-data">
            <!-- Champ caché ID (vide en création, rempli en édition) -->
            <input type="hidden" name="resource_id" id="formResourceId" value="">

            <div class="form-group">
                <label>Nom de la ressource :</label>
                <input type="text" id="resourceName" name="name" required placeholder="Ex: Cours de PHP">
            </div>

            <div class="form-group">
                <label>Description :</label>
                <textarea id="resourceDesc" name="description" rows="3" placeholder="Description courte..."></textarea>
            </div>

            <div class="form-group">
                <label>Image :</label>
                <input type="file" name="image" accept="image/*">
                <p id="currentImageName" style="font-size:0.8em; color:#666; display:none;"></p>
            </div>

            <div class="form-group">
                <label>Partager avec :</label>
                <div class="users-checklist">
                    <?php if (empty($all_users)): ?>
                        <p style="color:#999; font-style:italic;">Aucun autre utilisateur trouvé (vérifiez la table 'utilisateur').</p>
                    <?php else: ?>
                        <?php foreach ($all_users as $u): ?>
                            <label style="display:block; margin-bottom:5px; cursor:pointer;">
                                <input type="checkbox" name="shared_users[]" value="<?= $u->id ?>" class="user-checkbox">
                                <?= htmlspecialchars($u->prenom . ' ' . $u->nom) ?>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="modalSubmitBtn">Enregistrer la ressource</button>
        </form>
    </div>
</div>

<script>
    // Gestion ouverture/fermeture du Modal
    function openResourceModal(mode, btn = null) {
        const modal = document.getElementById('resourceModal');
        const form = document.getElementById('resourceForm');
        const title = document.getElementById('modalTitle');
        const btnSubmit = document.getElementById('modalSubmitBtn');
        const hiddenId = document.getElementById('formResourceId');

        // Reset du formulaire
        form.reset();
        hiddenId.value = '';
        document.getElementById('currentImageName').style.display = 'none';
        // Décocher cases
        document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);

        if (mode === 'edit' && btn) {
            // Mode Modification
            title.textContent = "Modifier la ressource";
            btnSubmit.textContent = "Mettre à jour";

            // On récupère les infos stockées dans les data-attributes de la carte
            const card = btn.closest('.resource-card');
            hiddenId.value = card.dataset.id;
            document.getElementById('resourceName').value = card.dataset.name;
            document.getElementById('resourceDesc').value = card.dataset.description;

            if(card.dataset.image) {
                const p = document.getElementById('currentImageName');
                p.textContent = "Image actuelle : " + card.dataset.image;
                p.style.display = 'block';
            }

            // Note : Pour pré-cocher les utilisateurs partagés, il faudrait que le PHP
            // renvoie la liste des IDs dans un data-attribute (ex: data-shared="1,5").
            // Si non disponible, les cases resteront vides à l'édition.
        } else {
            // Mode Création
            title.textContent = "Nouvelle Ressource";
            btnSubmit.textContent = "Créer la ressource";
        }

        modal.style.display = "block";
    }

    function closeResourceModal() {
        document.getElementById('resourceModal').style.display = "none";
    }

    // Fermer si clic en dehors
    window.onclick = function(event) {
        if (event.target == document.getElementById('resourceModal')) {
            closeResourceModal();
        }
    }

    // Filtre JS simple
    function filterResources() {
        let input = document.getElementById('searchBar').value.toLowerCase();
        let type = document.getElementById('filterType').value;
        let cards = document.getElementsByClassName('resource-card');

        for (let card of cards) {
            let name = card.dataset.name.toLowerCase();
            let access = card.dataset.accessType;
            let show = true;

            if (!name.includes(input)) show = false;
            if (type !== 'all' && access !== type) show = false;

            card.style.display = show ? "flex" : "none";
        }
    }
</script>
</body>
</html>