<?php
/**
 * Logout script
 * Destroys user session and redirects to login page
 */

// Start session
session_start();

// Destroy all session data
session_destroy();

// Redirect to login page
header("Location: ../views/connexion.php");
exit;
?>