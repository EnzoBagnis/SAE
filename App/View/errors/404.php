<!DOCTYPE html>
<html lang="fr">
<head>
    <?php
    if (!defined('BASE_URL')) {
        define('BASE_URL', '');
    }
    ?>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/favicon.ico">
    <title>404 - Page introuvable - StudTraj</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/dashboard.css">
    <meta name="robots" content="noindex, nofollow">
    <style>
        .error-page {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 60vh;
            text-align: center;
            padding: 40px 20px;
        }
        .error-page .error-code {
            font-size: 6em;
            font-weight: bold;
            color: #dee2e6;
            margin: 0;
            line-height: 1;
        }
        .error-page h2 { color: #333; margin: 10px 0 20px; }
        .error-page p { color: #666; margin-bottom: 30px; }
        .error-page a {
            display: inline-block;
            padding: 10px 24px;
            background: #007bff;
            color: #fff;
            border-radius: 4px;
            text-decoration: none;
            font-size: 1em;
        }
        .error-page a:hover { background: #0056b3; }
    </style>
</head>
<body>
<div class="error-page">
    <p class="error-code">404</p>
    <h2>Page introuvable</h2>
    <p>La ressource que vous recherchez n'existe pas ou vous n'avez pas les droits pour y accéder.</p>
    <a href="<?= BASE_URL ?>/index.php?action=resources_list">&larr; Retour aux ressources</a>
</div>
</body>
</html>

