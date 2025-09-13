<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>StudTraj - Inscription</title>
    <!-- <link rel="stylesheet" href="formStyle.css"> -->
</head>
<body>
<div class="page-wrap">
    <form class="card" method="POST" action="traitement.php">

        <label for="nom">Votre nom</label>
        <input type="text" id="nom" name="nom" placeholder="Entrez votre nom..." required><br>

        <label for="prenom">Votre prénom</label>
        <input type="text" id="prenom" name="prenom" placeholder="Entrez votre prénom..." required><br>

        <label for="mail">Votre mail</label>
        <input type="email" id="mail" name="mail" placeholder="Entrez votre mail..." required><br>

        <label for="mdp">Votre mot de passe</label>
        <input type="password" id="mdp" name="mdp" placeholder="Entrez votre mdp..." required><br>

        <button type="submit" class="btn-submit" name="ok">M'inscrire</button><br>

    </form>
</div>
</body>
</html>
