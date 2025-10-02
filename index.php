<?php


if (isset($_GET['action']) && $_GET['action'] !== '') {
    $action = $_GET['action'];
    if ($action === 'index') {
        header("Location: index.html");
    } elseif ($action === 'inscription') {
        header("Location: views/formulaire.php");
    }elseif ($action === 'acceuil') {
        header("Location: views/acceuil.php");
    }elseif ($action === 'connexion') {
        header("Location: views/connexion.php");
    }elseif ($action === 'acceuil2') {
        header("Location: views/page2.php");
    }elseif ($action === 'verifmail') {
        header("Location: views/verificationmail.php");
    } else {
        header("Location: index.html");
    }

}
else {
        header("Location: index.html");
    }

