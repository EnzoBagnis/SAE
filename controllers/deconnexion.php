<?php

session_start();
session_destroy();
header("Location: ../views/connexion.php");
exit;
?>