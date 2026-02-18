<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - StudTraj</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="container">
                <h1>StudTraj</h1>
                <ul>
                    <li><a href="/">Accueil</a></li>
                    <li><a href="/auth/login">Connexion</a></li>
                    <li><a href="/auth/register">Inscription</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="container">
        <section class="hero">
            <h2>Bienvenue sur StudTraj</h2>
            <p>Plateforme de suivi et d'analyse des exercices de programmation.</p>
            <div class="cta-buttons">
                <a href="/auth/register" class="btn btn-primary">S'inscrire</a>
                <a href="/auth/login" class="btn btn-secondary">Se connecter</a>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> StudTraj. Tous droits réservés.</p>
        </div>
    </footer>
</body>
</html>

