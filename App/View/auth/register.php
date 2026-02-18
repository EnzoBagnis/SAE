<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - StudTraj</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Inscription</h2>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/auth/register">
                <div class="form-group">
                    <label for="first_name">Prénom</label>
                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        value="<?= htmlspecialchars($first_name ?? '') ?>"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="last_name">Nom</label>
                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        value="<?= htmlspecialchars($last_name ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($email ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe (min. 8 caractères)</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        minlength="8"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    S'inscrire
                </button>
            </form>

            <div class="auth-footer">
                <p>Déjà un compte ? <a href="/auth/login">Se connecter</a></p>
            </div>
        </div>
    </div>
</body>
</html>

