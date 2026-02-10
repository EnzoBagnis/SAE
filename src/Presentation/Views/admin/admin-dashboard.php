<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Panel Admin - StudTraj') ?></title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/favicon.ico">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin.css">
</head>
<body>

<!-- Bandeau supérieur -->
<header class="top-menu">
    <div class="logo">
        <h1>StudTraj</h1>
    </div>
    <nav class="nav-menu">
        <a href="<?= BASE_URL ?>/index.php?action=admin" class="active">Administration</a>
    </nav>
    <div class="user-info">
        <span>
            <?= htmlspecialchars($_SESSION['prenom'] ?? 'Admin') ?>
            <?= htmlspecialchars($_SESSION['nom'] ?? '') ?>
        </span>
        <a href="<?= BASE_URL ?>/index.php?action=adminLogout" class="btn-logout">Déconnexion</a>
    </div>
</header>

<main class="main-content">
    <section class="data-zone">

        <?php if (isset($_GET['error'])) : ?>
            <div class="alert alert-error">
                <?php
                $errors = [
                    'cannot_delete_self' => 'Vous ne pouvez pas supprimer votre propre compte',
                    'cannot_ban_self' => 'Vous ne pouvez pas bloquer votre propre compte',
                    'delete_failed' => 'Échec de la suppression de l\'utilisateur',
                    'ban_failed' => 'Échec du blocage de l\'utilisateur',
                    'missing_data' => 'Données manquantes pour bloquer l\'utilisateur',
                    'user_not_found' => 'Utilisateur introuvable'
                ];
                echo htmlspecialchars($errors[$_GET['error']] ?? 'Une erreur est survenue');
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])) : ?>
            <div class="alert alert-success">
                <?php
                $success = [
                    'deleted' => 'Utilisateur supprimé avec succès',
                    'banned' => 'Utilisateur bloqué avec succès',
                    'unbanned' => 'Utilisateur débloqué avec succès',
                    'validated' => 'Utilisateur validé avec succès'
                ];
                echo htmlspecialchars($success[$_GET['success']] ?? 'Opération réussie');
                ?>
            </div>
        <?php endif; ?>

        <h2>Gestion des utilisateurs</h2>

        <!-- Onglets de filtrage -->
        <div class="tabs">
            <button class="tab-btn <?= ($currentTab ?? 'verified') === 'verified' ? 'active' : '' ?>"
                    onclick="showTab('verified')">
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
        <!-- Barre de recherche
        <div class="search-container">
            <form action="search.php" method="GET" class="search-bar">
                <input type="text" name="query" placeholder="Rechercher quelque chose..." id="searchInput">
                <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>
         -->








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
                </thead>
                <tbody>

                <?php if (empty($verifiedUsers)) : ?>
                    <tr>
                        <?php echo"<script>console.log(" . json_encode($verifiedUsers) . ");</script>" ?>
                        <td colspan="5" class="text-center">Aucun utilisateur vérifié</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($verifiedUsers as $user) : ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['nom']) ?></td>
                            <td><?= htmlspecialchars($user['prenom']) ?></td>
                            <td><?= htmlspecialchars($user['mail']) ?></td>
                            <td class="actions">
                                <button class="btn-edit"
                                    onclick="openEditPopup('V',
                                        '<?= htmlspecialchars($user['id']) ?>',
                                        '<?= htmlspecialchars($user['nom']) ?>',
                                        '<?= htmlspecialchars($user['prenom']) ?>',
                                        '<?= htmlspecialchars($user['mail']) ?>',
                                        '', '', '')">
                                    Modifier
                                </button>

                                <a href="<?= BASE_URL ?>/index.php?action=adminDeleteUser&table=V&id=<?=
                                    urlencode($user['id']) ?>"
                                   class="btn-delete"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                    Supprimer
                                </a>
                                <button class="btn-ban"
                                    onclick="openBanPopup('V',
                                        '<?= htmlspecialchars($user['id']) ?>',
                                        '<?= htmlspecialchars($user['mail']) ?>')">
                                    Bloquer
                                </button>
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
                    <th>Vérifé</th>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($pendingUsers)) : ?>
                    <tr>
                        <td colspan="5" class="text-center">Aucun utilisateur en attente</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($pendingUsers as $user) : ?>
                        <tr>
                            <td><?php switch ($user['verifie']) {
                                case 0:
                                    echo "Demande envoyée";
                                    break;
                                case 1:
                                    echo "Email vérifié";
                                    break;
                                } ?></td>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['nom']) ?></td>
                            <td><?= htmlspecialchars($user['prenom']) ?></td>
                            <td><?= htmlspecialchars($user['mail']) ?></td>
                            <td class="actions">
                                <button class="btn-edit"
                                    onclick="openEditPopup('P',
                                        '<?= htmlspecialchars($user['id']) ?>',
                                        '<?= htmlspecialchars($user['nom']) ?>',
                                        '<?= htmlspecialchars($user['prenom']) ?>',
                                        '<?= htmlspecialchars($user['mail']) ?>',
                                        '<?= htmlspecialchars($user['verifie']) ?>',
                                        '', '')">
                                    Modifier
                                </button>
                                <a href="<?= BASE_URL ?>/index.php?action=adminDeleteUser&table=P&id=<?=
                                    urlencode($user['id']) ?>"
                                   class="btn-delete"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                    Supprimer
                                </a>
                                <button class="btn-ban"
                                    onclick="openBanPopup('P',
                                        '<?= htmlspecialchars($user['id']) ?>',
                                        '<?= htmlspecialchars($user['mail']) ?>')">
                                    Bloquer
                                </button>
                                <?php if ($user['verifie'] == 1) : ?>
                                    <a href="<?= BASE_URL ?>/index.php?action=adminValidUser&id=<?=
                                        urlencode($user['id']) ?>"
                                       class="btn-validate"
                                       onclick="return confirm(
                                           'Êtes-vous sûr de vouloir valider cet utilisateur ?'
                                       )">
                                        Valider
                                    </a>
                                <?php endif; ?>
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
                    <th>Email</th>
                    <th>Date de ban</th>
                    <!--<th>Durée de bannissement</th>-->
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>

                <?php if (empty($blockedUsers)) : ?>
                    <tr>
                        <?php echo"<script>console.log(" . json_encode($blockedUsers) . ");</script>" ?>
                        <td colspan="5" class="text-center">Aucun utilisateur vérifié</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($blockedUsers as $user) : ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['mail']) ?></td>
                            <td><?= htmlspecialchars($user['date_de_ban']) ?></td>
                            <!--<td><?php if ($user['ban_def'] == 1) : ?>
                                Permanent
                                    <?php else : ?>
                                        <?= htmlspecialchars($user['duree_ban']) ?>
                                    <?php endif; ?>
                            </td>-->
                            <td class="actions">
                                <button class="btn-edit"
                                    onclick="openEditPopup('B',
                                        '<?= htmlspecialchars($user['id']) ?>',
                                        '', '',
                                        '<?= htmlspecialchars($user['mail']) ?>',
                                        '',
                                        '<?= htmlspecialchars($user['date_de_ban']) ?>',
                                        '<?= htmlspecialchars($user['duree_ban']) ?>')">
                                    Modifier
                                </button>

                                <a href="<?= BASE_URL ?>/index.php?action=adminDeleteUser&table=B&id=<?=
                                    urlencode($user['mail']) ?>"
                                   class="btn-delete"
                                   onclick="return confirm('Êtes-vous sûr débloquer cet utilisateur ?')">
                                Débloquer
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>
</main>

<!-- Overlay + Popup -->
<div id="editPopupOverlay" class="popup-overlay" onclick="closeEditPopup()"></div>

<div id="editVerifie" class="popup-container">
    <h2>Modifier l'utilisateur</h2>
    <form id="editUserForm" class="card" method="POST" action="<?= BASE_URL ?>/index.php?action=adminEditUser">
        <div class="form-group">
            <label for="idVerifie">id</label>
            <input type="text" id="idVerifie" name="id" readonly>
        </div>
            <div class="form-group">
                <label for="editNomVerifie">Nom</label>
                <input type="text" id="editNomVerifie" name="nom" required>
            </div>
        <div class="form-group">
            <label for="editPrenomVerifie">Prénom</label>
            <input type="text" id="editPrenomVerifie" name="prenom" required>
        </div>
        <div class="form-group">
            <label for="editEmailVerifie">Email</label>
            <input type="email" id="editEmailVerifie" name="email" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-submit" name="ok">Valider</button>
            <button type="button" class="btn-cancel" onclick="closeEditPopup()">Annuler</button>
        </div>
    </form>
</div>

<div id="editPending" class="popup-container">
    <h2>Modifier l'utilisateur</h2>
    <form id="editUserForm" class="card" method="POST" action="<?= BASE_URL ?>/index.php?action=adminEditUser">
        <div class="form-group">
            <label for="idPending">id</label>
            <input type="text" id="idPending" name="id" readonly>
        </div>
        <div class="form-group">
            <label for="editNomPending">Nom</label>
            <input type="text" id="editNomPending" name="nom" required>
        </div>
        <div class="form-group">
            <label for="editPrenomPending">Prénom</label>
            <input type="text" id="editPrenomPending" name="prenom" required>
        </div>
        <div class="form-group">
            <label for="editEmailPending">Email</label>
            <input type="email" id="editEmailPending" name="email" required>
        </div>
        <div class="form-group">
            <label for="verifiePending">Verifie</label>
            <input type="int" id="verifiePending" name="nom" readonly>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-submit" name="ok">Valider</button>
            <button type="button" class="btn-cancel" onclick="closeEditPopup()">Annuler</button>
        </div>
    </form>
</div>

<div id="editBlocked" class="popup-container">
    <h2>Bloquer l'utilisateur</h2>
    <form id="editUserForm" class="card" method="POST" action="<?= BASE_URL ?>/index.php?action=adminBanUser">
        <input type="hidden" id="tableBlocked" name="table" value="">
        <div class="form-group">
            <label for="idBlocked">ID</label>
            <input type="text" id="idBlocked" name="id" readonly>
        </div>
        <div class="form-group">
            <label for="editEmailBlocked">Email</label>
            <input type="email" id="editEmailBlocked" name="email" readonly>
        </div>
        <div class="form-group">
            <label for="editDateBanBlocked">Date de ban</label>
            <input type="text" id="editDateBanBlocked" name="date_de_ban" readonly>
        </div>
        <!-- Possibilité de modifier la durée de ban dans le futur
        <div class="form-group">
            <label for="editDureeBanBlocked">Durée de ban</label>
            <input type="int" id="editDureeBanBlocked" name="duree_de_ban" required>
        </div>
        -->
        <div class="form-group">
            <label for="ban_def">Bloquage définitif</label>
            <input type="checkbox" id="ban_def" name="ban_def" value="1" checked disabled>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-submit" name="ok">Valider</button>
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

    function setValue(id, v, table) {
        const el = document.getElementById(id + table);
        if (el) el.value = v ?? '';
    }

    function openEditPopup(table, id, nom, prenom, email, verifie, date_de_ban, duree_ban) {
        let tableName = '';
        switch (table) {
            case 'V':
                document.getElementById('editVerifie').style.display = 'block';
                tableName = "Verifie";
                break;
            case 'P':
                document.getElementById('editPending').style.display = 'block';
                tableName = "Pending";
                break;
            case 'B':
                document.getElementById('editBlocked').style.display = 'block';
                tableName = "Blocked";
                break;
        }
        switch (verifie) {
            case '0':
                verifie = "Demande envoyée";
                break;
            case '1':
                verifie = "Email vérifié";
                break;
        }
        setValue('table', table, tableName);
        setValue('verifie', verifie, tableName);
        setValue('id', id, tableName);
        setValue('editNom', nom, tableName);
        setValue('editPrenom', prenom, tableName);
        setValue('editEmail', email, tableName);
        setValue('editDateBan', date_de_ban, tableName);
        setValue('editDureeBan', duree_ban, tableName);

        document.getElementById('editPopupOverlay').style.display = 'block';
        //document.getElementById('editPopup').style.display = 'block';
        console.log("test");
        console.log(nom, prenom, email);
    }

    function openBanPopup(sourceTable, id, email) {
        // Open the ban popup with the source table information
        document.getElementById('editBlocked').style.display = 'block';
        document.getElementById('editPopupOverlay').style.display = 'block';

        // Set the values
        document.getElementById('tableBlocked').value = sourceTable; // V or P
        document.getElementById('idBlocked').value = id;
        document.getElementById('editEmailBlocked').value = email;

        // Set current date as ban date
        const dateDuJour = new Date().toISOString().split('T')[0];
        document.getElementById('editDateBanBlocked').value = dateDuJour;

        console.log("Opening ban popup - Source table:", sourceTable, "ID:", id, "Email:", email);
    }

    function closeEditPopup() {
        document.getElementById('editPopupOverlay').style.display = 'none';
        const popups = ['editVerifie', 'editPending', 'editBlocked', 'blockUser'];
        popups.forEach(id => {
            const popup = document.getElementById(id);
            if (popup) popup.style.display = 'none';
        });
    }

    // Récupère la date locale au format ISO (AAAA-MM-JJTHH:mm:ss...)
    // Puis on ne garde que les 10 premiers caractères (AAAA-MM-JJ)
    const dateDuJour = new Date().toISOString().split('T')[0];

    document.getElementById('date_fixe').textContent = dateDuJour;
</script>
</body>
</html>
