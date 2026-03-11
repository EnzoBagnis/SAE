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
    <title>500 - Erreur interne - StudTraj</title>
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
        }
        .error-page h1 {
            color: #495057;
            margin: 20px 0 10px;
        }
        .error-page p {
            color: #6c757d;
            max-width: 500px;
        }
        .error-page a.btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 24px;
            background: #007bff;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
        }
        .error-page a.btn:hover { background: #0056b3; }
    </style>
</head>
<body>
<div class="error-page">
    <p class="error-code">500</p>
    <h1>Erreur interne du serveur</h1>
    <p>Une erreur inattendue s'est produite. Nos équipes ont été notifiées.
        Veuillez réessayer dans quelques instants.</p>
    <a href="<?= BASE_URL ?>/resources" class="btn">← Retour aux ressources</a>
</div>
</body>
</html>

