<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - StudTraj</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="container">
                <h1>StudTraj</h1>
                <ul>
                    <li><a href="/dashboard">Tableau de bord</a></li>
                    <li><a href="/resources">Ressources</a></li>
                    <li><a href="/exercises">Exercices</a></li>
                    <li><a href="/auth/logout">Déconnexion</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="container">
        <h2>Tableau de bord</h2>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Mes Ressources</h3>
                <p>Gérez vos ressources pédagogiques</p>
                <a href="/resources" class="btn btn-primary">Voir les ressources</a>
            </div>

            <div class="dashboard-card">
                <h3>Exercices</h3>
                <p>Consultez les exercices disponibles</p>
                <a href="/exercises" class="btn btn-primary">Voir les exercices</a>
            </div>

            <div class="dashboard-card">
                <h3>Statistiques</h3>
                <p>Suivez vos progrès</p>
                <a href="/stats" class="btn btn-primary">Voir les statistiques</a>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> StudTraj. Tous droits réservés.</p>
        </div>
    </footer>
</body>
</html>

