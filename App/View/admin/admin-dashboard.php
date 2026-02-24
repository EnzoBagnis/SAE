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
        <a href="<?= BASE_URL ?>/admin/dashboard" class="active">Administration</a>
    </nav>
    <div class="user-info">
        <span>
            <?= htmlspecialchars($_SESSION['prenom'] ?? 'Admin') ?>
            <?= htmlspecialchars($_SESSION['nom'] ?? '') ?>
        </span>
        <a href="<?= BASE_URL ?>/admin/logout" class="btn-logout">Déconnexion</a>
    </div>
</header>

<main class="main-content">
    <section class="data-zone">

        <?php if (isset($_GET['error'])) : ?>
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

        <?php if (isset($_GET['success'])) : ?>
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

        <!-- Tableau des utilisateurs vérifiés -->
        <div id="verified-tab" class="tab-content <?= ($currentTab ?? 'verified') === 'verified' ? 'active' : '' ?>">
            <table class="user-table">
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>

                <?php if (empty($verifiedUsers)) : ?>
                    <tr>
                        <td colspan="4" class="text-center">Aucun utilisateur vérifié</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($verifiedUsers as $user) : ?>
                        <tr>
                            <td><?= htmlspecialchars($user['surname']) ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['mail']) ?></td>
                            <td class="actions">
                                <button class="btn-edit"
                                    onclick="openEditPopup('V',
                                        '<?= htmlspecialchars($user['mail']) ?>',
                                        '<?= htmlspecialchars($user['surname']) ?>',
                                        '<?= htmlspecialchars($user['name']) ?>',
                                        '<?= htmlspecialchars($user['mail']) ?>',
                                        '', '', '')">
                                    Modifier
                                </button>

                                <a href="<?= BASE_URL ?>/admin/delete-user?id=<?=
                                    urlencode($user['mail']) ?>"
                                   class="btn-delete"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                    Supprimer
                                </a>
                                <button class="btn-ban"
                                    onclick="openEditPopup('B',
                                        '<?= htmlspecialchars($user['mail']) ?>',
                                        '', '',
                                        '<?= htmlspecialchars($user['mail']) ?>',
                                        '',
                                        '<?php echo date('Y-m-d'); ?>',
                                        '')">
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
                    <th>Statut</th>
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
                            <td>En attente</td>
                            <td><?= htmlspecialchars($user['surname']) ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['mail']) ?></td>
                            <td class="actions">
                                <button class="btn-edit"
                                    onclick="openEditPopup('P',
                                        '<?= htmlspecialchars($user['mail']) ?>',
                                        '<?= htmlspecialchars($user['surname']) ?>',
                                        '<?= htmlspecialchars($user['name']) ?>',
                                        '<?= htmlspecialchars($user['mail']) ?>',
                                        '<?= htmlspecialchars($user['account_status']) ?>',
                                        '', '')">
                                    Modifier
                                </button>
                                <a href="<?= BASE_URL ?>/admin/delete-user?id=<?=
                                    urlencode($user['mail']) ?>"
                                   class="btn-delete"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                    Supprimer
                                </a>
                                <button class="btn-ban"
                                    onclick="openEditPopup('B',
                                        '<?= htmlspecialchars($user['mail']) ?>',
                                        '', '',
                                        '<?= htmlspecialchars($user['mail']) ?>',
                                        '',
                                        '<?php echo date('Y-m-d'); ?>',
                                        '')">
                                    Bloquer
                                </button>
                                <a href="<?= BASE_URL ?>/admin/validate-user?id=<?=
                                    urlencode($user['mail']) ?>"
                                   class="btn-validate"
                                   onclick="return confirm(
                                       'Êtes-vous sûr de vouloir valider cet utilisateur ?'
                                   )">
                                    Valider
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
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>

                <?php if (empty($blockedUsers)) : ?>
                    <tr>
                        <td colspan="4" class="text-center">Aucun utilisateur bloqué</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($blockedUsers as $user) : ?>
                        <tr>
                            <td><?= htmlspecialchars($user['surname']) ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['mail']) ?></td>
                            <td class="actions">
                                <a href="<?= BASE_URL ?>/admin/unban-user?id=<?=
                                    urlencode($user['mail']) ?>"
                                   class="btn-validate"
                                   onclick="return confirm('Êtes-vous sûr de vouloir débloquer cet utilisateur ?')">
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
    <form id="editUserFormVerifie" class="card" method="POST" action="<?= BASE_URL ?>/admin/edit-user">
        <div class="form-group">
            <label for="idVerifie">Email (identifiant)</label>
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
            <input type="email" id="editEmailVerifie" name="email" readonly>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-submit" name="ok">Valider</button>
            <button type="button" class="btn-cancel" onclick="closeEditPopup()">Annuler</button>
        </div>
    </form>
</div>

<div id="editPending" class="popup-container">
    <h2>Modifier l'utilisateur</h2>
    <form id="editUserFormPending" class="card" method="POST" action="<?= BASE_URL ?>/admin/edit-user">
        <div class="form-group">
            <label for="idPending">Email (identifiant)</label>
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
            <input type="email" id="editEmailPending" name="email" readonly>
        </div>
        <div class="form-group">
            <label for="verifiePending">Statut</label>
            <input type="text" id="verifiePending" name="statut" readonly>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-submit" name="ok">Valider</button>
            <button type="button" class="btn-cancel" onclick="closeEditPopup()">Annuler</button>
        </div>
    </form>
</div>

<div id="editBlocked" class="popup-container">
    <h2>Bloquer l'utilisateur</h2>
    <form id="editUserFormBlocked" class="card" method="POST" action="<?= BASE_URL ?>/admin/ban-user">
        <div class="form-group">
            <label for="idBlocked">Email (identifiant)</label>
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
        <div class="form-group">
            <label for="ban_def">Blocage définitif</label>
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
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.getElementById(tabName + '-tab').classList.add('active');
        fetch('<?= BASE_URL ?>/admin/switch-user');
        event.target.classList.add('active');
    }

    function setValue(id, v, table) {
        const el = document.getElementById(id + table);
        if (el) el.value = v ?? '';
    }

    function openEditPopup(table, id, nom, prenom, email, verifie, date_de_ban, duree_ban) {
        switch (table) {
            case 'V':
                document.getElementById('editVerifie').style.display = 'block';
                table = "Verifie";
                break;
            case 'P':
                document.getElementById('editPending').style.display = 'block';
                table = "Pending";
                break;
            case 'B':
                document.getElementById('editBlocked').style.display = 'block';
                table = "Blocked";
                break;
        }
        if (verifie === '0') {
            verifie = "En attente";
        } else if (verifie === '1') {
            verifie = "Vérifié";
        }
        setValue('table', table, table);
        setValue('verifie', verifie, table);
        setValue('id', id, table);
        setValue('editNom', nom, table);
        setValue('editPrenom', prenom, table);
        setValue('editEmail', email, table);
        setValue('editDateBan', date_de_ban, table);
        setValue('editDureeBan', duree_ban, table);

        document.getElementById('editPopupOverlay').style.display = 'block';
    }

    function closeEditPopup() {
        document.getElementById('editPopupOverlay').style.display = 'none';
        const popups = ['editVerifie', 'editPending', 'editBlocked'];
        popups.forEach(id => {
            const popup = document.getElementById(id);
            if (popup) popup.style.display = 'none';
        });
    }
</script>
</body>
</html>