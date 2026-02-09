<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'En attente de validation - StudTraj') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/favicon.ico">
</head>
<body>
    <div class="page-wrap">
        <a href="<?= BASE_URL ?>/index.php" class="back-arrow" title="Retour à l'accueil">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </a>

        <div class="card" style="text-align: center;">
            <div class="logo">
                <img src="<?= BASE_URL ?>/images/favicon.ico" alt="StudTraj Logo" style="max-height: 60px; margin-bottom: 20px;">
                <h2>StudTraj</h2>
            </div>

            <div class="message-box success" style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                <h3>Email vérifié !</h3>
                <p>Votre adresse email a été confirmée avec succès.</p>
            </div>

            <div class="verification-content">
                <div class="pending-icon" style="margin: 20px 0; color: #f0ad4e;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-clock">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>

                <h3 style="color: #f0ad4e;">En attente de validation</h3>
                <p style="margin-bottom: 15px;">Votre compte est actuellement en attente de validation par un administrateur.</p>
                <p style="margin-bottom: 25px;">Vous recevrez un email une fois votre compte activé pour pouvoir vous connecter.</p>

                <div class="actions">
                    <a href="<?= BASE_URL ?>/index.php?action=login" class="btn btn-primary" style="display: inline-block; text-decoration: none;">Retour à la connexion</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
