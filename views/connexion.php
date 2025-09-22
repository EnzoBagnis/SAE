<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>StudTraj - Connexion</title>
    <!-- <link rel="stylesheet" href="formStyle.css"> -->
</head>
<body>

<div class="page-wrap">

    <?php
    // Afficher les messages d'erreur ou de succès
    if (isset($_GET['erreur'])) {
        switch($_GET['erreur']) {
            case 'email_existe_pas':
                echo '<div class="error">Cet email n\'existe pas !</div>';
                break;
            case 'mdp_incorrect':
                echo '<div class="error">Mot de passe incorect</div>';
                break;
            case 'insertion':
                echo '<div class="error">Erreur lors de l\'inscription</div>';
                break;
        }
    }

    ?>

    <form class="card" method="POST" action="../controllers/traitement.php">

        <label for="mail">Votre mail</label>
        <input type="email" id="mail" name="mail" placeholder="Entrez votre mail..." required><br>

        <label for="mdp">Votre mot de passe</label>
        <input type="password" id="mdp" name="mdp" placeholder="Entrez votre mdp..." required><br>

        <button type="submit" class="btn-submit" name="connexion">Me connecter</button><br>

    </form>
</div>
</body>
</html>