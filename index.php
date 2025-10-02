<?php


if (isset($_GET['action']) && $_GET['action'] !== '') {
    $action = $_GET['action'];
    if ($action === 'index') {
        header("Location: index.html");
    } elseif ($action === 'inscription') {
        header("Location: views/formulaire.php");
    } else {
        header("Location: index.html");
    }
else {
        header("Location: index.html");
    }
}

