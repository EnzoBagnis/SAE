<?php

namespace Controllers\Auth;

/**
 * LogoutController - Handles user logout
 */
class LogoutController
{
    /**
     * Logout user and destroy session
     */
    public function logout()
    {
        session_start();
        session_destroy();

        header('Location: /index.php?action=login');
        exit;
    }
}
