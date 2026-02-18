<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>En attente d'approbation - StudTraj</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card text-center">
            <div class="success-icon">✓</div>
            <h2>Inscription réussie !</h2>

            <div class="alert alert-success">
                <?= htmlspecialchars($message ?? 'Veuillez vérifier votre email pour le code de vérification.') ?>
            </div>

            <p>Un code de vérification a été envoyé à votre adresse email.</p>
            <p>Veuillez vérifier votre boîte de réception et suivre les instructions.</p>

            <div class="auth-footer">
                <p><a href="/auth/login">Retour à la connexion</a></p>
            </div>
        </div>
    </div>
</body>
</html>

