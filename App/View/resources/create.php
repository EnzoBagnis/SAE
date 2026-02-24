<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('BASE_URL')) {
    define('BASE_URL', '');
}

$user_firstname = $_SESSION['user_firstname'] ?? ($_SESSION['name'] ?? 'Utilisateur');
$user_lastname  = $_SESSION['user_lastname'] ?? ($_SESSION['surname'] ?? '');
$initials = strtoupper(substr($user_firstname, 0, 1) . substr($user_lastname, 0, 1));
$title = 'StudTraj - Nouvelle Ressource';
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
    <meta name="robots" content="noindex, nofollow">
    <style>
        .create-container {
            max-width: 620px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,.09);
            padding: 36px 40px;
        }
        .create-container h2 {
            margin: 0 0 24px;
            font-size: 22px;
            color: #1a237e;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 20px;
        }
        .form-group label {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group textarea {
            padding: 10px 14px;
            border: 1px solid #cdd4e0;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
            transition: border-color .2s;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #2196f3;
        }
        .form-group textarea { resize: vertical; min-height: 90px; }
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 10px;
        }
        .btn-submit {
            padding: 10px 24px;
            background: #2196f3;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-submit:hover { background: #1976d2; }
        .btn-cancel {
            padding: 10px 24px;
            background: #f5f5f5;
            color: #333;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .btn-cancel:hover { background: #e0e0e0; }
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .required { color: #e53935; }
    </style>
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

<div class="dashboard-container">
    <main class="main-content">
        <div class="create-container">
            <h2>+ Nouvelle Ressource</h2>

            <?php if (!empty($error)) : ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>/resources/create">
                <div class="form-group">
                    <label for="resource_name">Nom de la ressource <span class="required">*</span></label>
                    <input type="text" id="resource_name" name="resource_name"
                           placeholder="Ex : TP Algorithmes"
                           value="<?= htmlspecialchars($_POST['resource_name'] ?? '') ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"
                              placeholder="Décrivez brièvement cette ressource..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="image_path">URL de l'image <small style="color:#888;">(optionnel)</small></label>
                    <input type="text" id="image_path" name="image_path"
                           placeholder="https://exemple.com/image.png"
                           value="<?= htmlspecialchars($_POST['image_path'] ?? '') ?>">
                </div>

                <div class="form-actions">
                    <a href="<?= BASE_URL ?>/resources" class="btn-cancel">Annuler</a>
                    <button type="submit" class="btn-submit">Créer la ressource</button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>

