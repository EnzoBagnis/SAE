<?php
// Redirection vers le contrôleur admin via le routeur principal
// Cette astuce permet d'utiliser l'URL /admin sans modifier le .htaccess
header('Location: ../index.php?action=admin');
exit;

