<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>StudTraj - Page2</title>
    <!-- <link rel="stylesheet" href="formStyle.css"> -->
</head>
<body>
<!-- CECI EST UNE PAGE DE TEST POUR VOIR SI LES CONNEXIONS SONT TOUJOURS EFFECTIVES -->

<?php
    session_start();
    echo '<div class="succes">Bienvenue ' . $_SESSION['nom'] . ' ' . $_SESSION['prenom'] . '</div>';

?>