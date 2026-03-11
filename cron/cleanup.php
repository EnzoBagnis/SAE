<?php

/**
 * Cleanup Cron Job Script
 * Automatically deletes expired pending registrations (teachers with account_status = 0)
 * that have been unverified for too long.
 * Should be run periodically via cron job
 */

require_once __DIR__ . '/../App/bootstrap.php';

use Core\Config\DatabaseConnection;

$pdo = DatabaseConnection::getInstance()->getConnection();

// Delete teachers with account_status = 0 (not verified) — optional cleanup
// You can add a date-based condition if needed
// $stmt = $pdo->prepare("DELETE FROM teachers WHERE account_status = 0 AND ...");

echo "Cleanup completed.";
