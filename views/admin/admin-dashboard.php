<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Panel Admin - StudTraj') ?></title>
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="stylesheet" href="/SAE/public/css/admin.css">
</head>
<body>

<!-- Bandeau supérieur -->
<header class="top-menu">
    <div class="logo">
        <h1>StudTraj</h1>
    </div>
    <nav class="nav-menu">
        <a href="/index.php?action=dashboard">Tableau de bord</a>
        <a href="/index.php?action=admin" class="active">Administration</a>
        <a href="#">Plan du site</a>
        <a href="#">Mentions légales</a>
    </nav>
    <div class="user-info">
        <span><?= htmlspecialchars($_SESSION['prenom'] ?? 'Admin') ?> <?= htmlspecialchars($_SESSION['nom'] ?? '') ?></span>
        <a href="/index.php?action=logout" class="btn-logout">Déconnexion</a>
    </div>
</header>

<main class="main-content">
    <section class="data-zone">

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                $errors = [
                    'cannot_delete_self' => 'Vous ne pouvez pas supprimer votre propre compte',
                    'delete_failed' => 'Échec de la suppression de l\'utilisateur',
                    'user_not_found' => 'Utilisateur introuvable'
                ];
                echo htmlspecialchars($errors[$_GET['error']] ?? 'Une erreur est survenue');
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                if ($_GET['success'] === 'deleted') {
                    echo 'Utilisateur supprimé avec succès';
                }
                ?>
            </div>
        <?php endif; ?>

        <h2>Gestion des utilisateurs</h2>

        <!-- Onglets de filtrage -->
        <div class="tabs">
            <button class="tab-btn <?= ($currentTab ?? 'verified') === 'verified' ? 'active' : '' ?>"
                    onclick="window.location.href='index.php?action=adminSVU';">
                Utilisateurs vérifiés (<?= count($verifiedUsers ?? []) ?>)
            </button>
            <button class="tab-btn <?= ($currentTab ?? '') === 'pending' ? 'active' : '' ?>"
                    onclick="showTab('pending')">
                En attente de vérification (<?= count($pendingUsers ?? []) ?>)
            </button>
            <button class="tab-btn <?= ($currentTab ?? '') === 'blocked' ? 'active' : '' ?>"
                    onclick="showTab('blocked')">
                Utilisateurs bloqués (<?= count($blockedUsers ?? []) ?>)
            </button>
        </div>

        <!-- Tableau des utilisateurs vérifiés -->
        <div id="verified-tab" class="tab-content <?= ($currentTab ?? 'verified') === 'verified' ? 'active' : '' ?>">
            <table class="user-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
                <script>console.log("rr");</script>
                </thead>
                <tbody>

                <?php if (empty($verifiedUsers)): ?>
                    <tr>
                        <?php echo"<script>console.log(" . json_encode($verifiedUsers) . ");</script>" ?>
                        <td colspan="5" class="text-center">Aucun utilisateur vérifié</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($verifiedUsers as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['nom']) ?></td>
                            <td><?= htmlspecialchars($user['prenom']) ?></td>
                            <td><?= htmlspecialchars($user['mail']) ?></td>
                            <td class="actions">
                                <button class="btn-edit" onclick="openEditPopup('<?= htmlspecialchars($user['id']) ?>', '<?= htmlspecialchars($user['nom']) ?>', '<?= htmlspecialchars($user['prenom']) ?>', '<?= htmlspecialchars($user['mail']) ?>')">Modifier</button>

                                <a href="index.php?action=adminDeleteUser&id=<?= urlencode($user['id']) ?>"
                                   class="btn-delete"
                                   <!-- onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')"> -->
                                    Supprimer
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Tableau des utilisateurs en attente -->
        <div id="pending-tab" class="tab-content <?= ($currentTab ?? '') === 'pending' ? 'active' : '' ?>">
            <table class="user-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($pendingUsers)): ?>
                    <tr>
                        <td colspan="5" class="text-center">Aucun utilisateur en attente</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pendingUsers as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['nom']) ?></td>
                            <td><?= htmlspecialchars($user['prenom']) ?></td>
                            <td><?= htmlspecialchars($user['mail']) ?></td>
                            <td class="actions">
                                <a href="/index.php?action=admin_edit&id=<?= urlencode($user['id']) ?>"
                                   class="btn-edit">Modifier</a>
                                <a href="/index.php?action=admin_delete&id=<?= urlencode($user['id']) ?>"
                                   class="btn-delete"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                    Supprimer
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Tableau des utilisateurs bloqués -->
        <div id="blocked-tab" class="tab-content <?= ($currentTab ?? '') === 'blocked' ? 'active' : '' ?>">
            <table class="user-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td colspan="5" class="text-center">Fonctionnalité à venir</td>
                </tr>
                </tbody>
            </table>
        </div>

    </section>
</main>

<!-- Overlay + Popup -->
<div id="editPopupOverlay" class="popup-overlay" onclick="closeEditPopup()"></div>

<div id="editPopup" class="popup-container">
    <h2>Modifier l'utilisateur</h2>
    <form id="editUserForm" class="card" method="POST" action="index.php?action=adminEditUser">
        <div class="form-group">
            <label for="id">id</label>
            <input type="text" id="id" name="id" readonly>
        </div>
        <div class="form-group">
            <label for="editNom">Nom</label>
            <input type="text" id="editNom" name="nom" required>
        </div>

        <div class="form-group">
            <label for="editPrenom">Prénom</label>
            <input type="text" id="editPrenom" name="prenom" required>
        </div>

        <div class="form-group">
            <label for="editEmail">Email</label>
            <input type="email" id="editEmail" name="email" required>
        </div>
        <button type="submit" class="btn-submit" name="ok">Valider</button>

        <div class="form-actions">
            <button type="button" class="btn-cancel" onclick="closeEditPopup()">Annuler</button>
        </div>
    </form>
</div>

<script>
    function showTab(tabName) {
        // Cacher tous les contenus
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });

        // Désactiver tous les boutons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Afficher le contenu sélectionné
        document.getElementById(tabName + '-tab').classList.add('active');
        fetch('index.php?action=adminSU');

        // Activer le bouton sélectionné
        event.target.classList.add('active');
    }
    function test() {
        console.log("test");
    }

    function openEditPopup(id, nom, prenom, email) {
        document.getElementById('id').value = id;
        document.getElementById('editNom').value = nom;
        document.getElementById('editPrenom').value = prenom;
        document.getElementById('editEmail').value = email;

        document.getElementById('editPopupOverlay').style.display = 'block';
        document.getElementById('editPopup').style.display = 'block';
        console.log("test");
        console.log(nom, prenom, email);
    }

    function closeEditPopup() {
        document.getElementById('editPopupOverlay').style.display = 'none';
        document.getElementById('editPopup').style.display = 'none';
    }

</script>

</body>
</html>